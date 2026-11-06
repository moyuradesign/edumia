#!/usr/bin/env bash
# Safe GitHub Main -> aaPanel sync for the Admin Media Library milestone.
# Source: pinned Git revision fetched from authenticated GitHub CLI checkout.
# Does NOT sync runtime storage, .env, vendor, migrations, or other modules.
set -Eeuo pipefail
umask 077

DOMAIN='edumia.tonsamith.com'
REPOSITORY='moyuradesign/edumia'
APP='/www/wwwroot/edumia-releases/edumia-blog-b4a7ba6c-Tc5UKAOs'
PHP='/www/server/php/85/bin/php'
WEB_USER='www'
SOURCE='/root/edumia-github'
BACKUPS='/root/edumia-media-sync-backups'
BASE_SHA='c9743af64b7410b61f8bff1eacfa3cad93fcd841'
# Exact Git blob hashes from the 2026-11-06 read-only live reconciliation.
# They permit ONLY known missing/altered instructor and routing source.
APPROVED_LIVE_ROUTES='cf6c635c9e59620b4b45322fc9f93e574ba50f96'
# Approved six-file Media Library source scope.
EXISTING='app/Http/Controllers/Admin/Content/MediaController.php app/Http/Controllers/Admin/Content/BlogController.php app/Services/Content/BlogPublishingService.php resources/views/admin/content/media/index.blade.php resources/views/admin/content/blog/form.blade.php routes/admin.php'
ADDED=''
FILES="$EXISTING"
MODE=''
SHA=''
ROLLBACK=''
STAGE=''
BACKUP=''
APPLY_STARTED=0
STEP='read-only preflight'

usage() {
    cat <<'HELP'
Usage:
  bash scripts/aa-panel-media-sync.sh --check --sha FULL40HEX
  bash scripts/aa-panel-media-sync.sh --apply --sha FULL40HEX
  bash scripts/aa-panel-media-sync.sh --rollback /root/edumia-media-sync-backups/BACKUP-NAME
Optional: --source /root/edumia-github --app /path/to/active/laravel

Before use:
  gh auth status
  gh repo clone moyuradesign/edumia /root/edumia-github -- --branch main

This is a targeted Media Library sync, not a whole-repository deployment.
HELP
}

while [ "$#" -gt 0 ]; do
    case "$1" in
        --check|--apply) [ -z "$MODE" ] || { echo 'Choose one mode' >&2; exit 2; }; MODE="$1"; shift ;;
        --rollback) [ -z "$MODE" ] && [ "$#" -ge 2 ] || exit 2; MODE='--rollback'; ROLLBACK="$2"; shift 2 ;;
        --sha) [ "$#" -ge 2 ] || exit 2; SHA="$2"; shift 2 ;;
        --source) [ "$#" -ge 2 ] || exit 2; SOURCE="$2"; shift 2 ;;
        --app) [ "$#" -ge 2 ] || exit 2; APP="$2"; shift 2 ;;
        --help|-h) usage; exit 0 ;;
        *) echo "Unknown option: $1" >&2; usage; exit 2 ;;
    esac
done

[ -n "$MODE" ] || { usage; exit 2; }
[ "$(id -u)" -eq 0 ] || { echo 'Run as root in aaPanel terminal.' >&2; exit 1; }
[ -x "$PHP" ] || { echo "PHP runtime missing: $PHP" >&2; exit 1; }
id "$WEB_USER" >/dev/null || { echo 'PHP-FPM web user is missing.' >&2; exit 1; }
[ -f "$APP/vendor/autoload.php" ] && [ -f "$APP/artisan" ] || {
    echo 'Not an installed Laravel application.' >&2; exit 1;
}
# Verify the *effective* runtime configuration as PHP-FPM, not the presence
# of any optional source diagnostic file. The prior guarded cache hardening
# installs a working config and release-local view path but may omit scripts/.
# This check is read-only and works with either source or cached configuration.
verify_release_view_cache() {
    (
        cd "$APP"
        runuser -u "$WEB_USER" -- "$PHP" -r '
            require "vendor/autoload.php";
            $app = require "bootstrap/app.php";
            $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            $expected = base_path("bootstrap/cache/views");
            $actual = config("view.compiled");
            $fail = static function (string $message): void {
                fwrite(STDERR, "EDUMIA_VIEW_CACHE=FAIL ".$message.PHP_EOL);
                exit(1);
            };
            if (! is_string($actual) || $actual !== $expected) {
                $fail("Compiled views are not configured for the active release.");
            }
            if (! is_dir($expected) || is_link($expected)
                || realpath($expected) !== $expected || ! is_writable($expected)) {
                $fail("Release-local compiled views must be a real, writable directory.");
            }
            foreach (glob($expected."/*.php") ?: [] as $file) {
                if (! is_readable($file)) {
                    $fail("Compiled Blade file cannot be read by PHP-FPM.");
                }
            }
            echo "EDUMIA_VIEW_CACHE=PASS".PHP_EOL;
        '
    )
}
verify_release_view_cache
# Check every required original before stopping, to report all layout gaps.
missing=0
for relative in $EXISTING; do
    if [ ! -f "$APP/$relative" ] || [ -L "$APP/$relative" ]; then
        echo "BLOCKED: Required live file missing or symlinked: $relative" >&2
        missing=1
    fi
done
[ "$missing" -eq 0 ] || {
    echo 'No files changed. Review the live release layout before applying.' >&2
    exit 1
}

clean_up() {
    if [ -n "$STAGE" ] && [ -d "$STAGE" ]; then
        rm -rf -- "$STAGE"
    fi
}
on_error() {
    local status="$1"
    trap - ERR
    # set -E inherits ERR traps inside subshells. Leave rollback exclusively
    # to the parent process, which also owns the backup and state variables.
    if (( BASH_SUBSHELL > 0 )); then
        return "$status"
    fi
    echo "DEPLOYMENT STOPPED during: $STEP (exit $status)." >&2
    if [ "$APPLY_STARTED" -eq 1 ] && [ -n "$BACKUP" ] && [ -f "$BACKUP/files.tar.gz" ]; then
        APPLY_STARTED=0
        echo 'APPLY FAILED: Restoring protected pre-sync source backup.' >&2
        tar -xzf "$BACKUP/files.tar.gz" -C "$APP" || true
        for added in $ADDED; do
            if grep -Fxq "$added" "$BACKUP/created.txt" 2>/dev/null; then
                rm -f -- "$APP/$added" || true
            fi
        done
        (cd "$APP" && runuser -u "$WEB_USER" -- "$PHP" artisan route:clear) || true
        (cd "$APP" && runuser -u "$WEB_USER" -- "$PHP" artisan view:clear) || true
    fi
    clean_up
    exit "$status"
}
trap clean_up EXIT
trap 'on_error "$?"' ERR

validate_archive() {
    archive="$1"
    [ -s "$archive" ] || { echo 'Rollback archive missing.' >&2; return 1; }
    actual="$(tar -tzf "$archive" | LC_ALL=C sort)"
    expected="$(printf '%s\n' $EXISTING | LC_ALL=C sort)"
    [ "$actual" = "$expected" ] || {
        echo 'Backup contents do not match six protected source files.' >&2; return 1;
    }
}

refresh_views() {
    (
        cd "$APP"
        # New instructor routes must not be hidden by a previous release's cache.
        # Do not run these commands as root.
        runuser -u "$WEB_USER" -- "$PHP" artisan route:clear
        runuser -u "$WEB_USER" -- "$PHP" artisan view:clear
        runuser -u "$WEB_USER" -- "$PHP" artisan view:cache
        verify_release_view_cache
    )
}

verify_media_routes() {
    (
        cd "$APP"
        runuser -u "$WEB_USER" -- "$PHP" artisan route:list --json
    ) > "$STAGE/registered-routes.json"
    "$PHP" -r '
        $rows = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($rows)) { exit(1); }
        $found = [];
        foreach ($rows as $row) {
            if (is_array($row) && is_string($row["name"] ?? null)) {
                $found[$row["name"]] = $row;
            }
        }
        $required = [
            "admin.media.preview" => ["GET", "admin/media/{media}/preview"],
            "admin.media.update" => ["PUT", "admin/media/{media}"],
            "admin.blogs.media.preview" => ["GET", "admin/blog-images/{media}"],
        ];
        foreach ($required as $name => [$method, $uri]) {
            $row = $found[$name] ?? null;
            if (!$row || !in_array($method, explode("|", (string)($row["method"] ?? "")), true)
                || trim((string)($row["uri"] ?? ""), "/") !== $uri) {
                fwrite(STDERR, "BLOCKED: Media route not registered: ".$name.PHP_EOL);
                exit(1);
            }
        }
        echo "EDUMIA_MEDIA_ROUTES=PASS (3 authorized endpoints)".PHP_EOL;
    ' "$STAGE/registered-routes.json"
}

if [ "$MODE" = '--rollback' ]; then
    case "$ROLLBACK" in
        "$BACKUPS"/*) ;;
        *) echo 'Rollback path is not inside the protected Media Library backup directory.' >&2; exit 1 ;;
    esac
    [ -d "$ROLLBACK" ] && [ ! -L "$ROLLBACK" ] || exit 1
    [ -f "$ROLLBACK/files.tar.gz" ] && [ -f "$ROLLBACK/files.sha256" ] && [ -f "$ROLLBACK/created.txt" ] || exit 1
    expected_hash="$(cat "$ROLLBACK/files.sha256")"
    actual_hash="$(sha256sum "$ROLLBACK/files.tar.gz" | cut -d ' ' -f1)"
    [ "$expected_hash" = "$actual_hash" ] || { echo 'Rollback backup hash mismatch.' >&2; exit 1; }
    validate_archive "$ROLLBACK/files.tar.gz"
    [ -f "$ROLLBACK/deployed.sha256" ] || {
        echo 'Rollback refused: deployed file-hash manifest is missing.' >&2; exit 1;
    }
    (cd "$APP" && sha256sum -c --quiet "$ROLLBACK/deployed.sha256") || {
        echo 'Rollback refused: files have changed since this release was deployed.' >&2
        exit 1
    }
    for added in $ADDED; do
        [ ! -L "$APP/$added" ] || { echo 'Symlink at rollback target.' >&2; exit 1; }
    done
    tar -xzf "$ROLLBACK/files.tar.gz" -C "$APP"
    for added in $ADDED; do
        if grep -Fxq "$added" "$ROLLBACK/created.txt"; then
            rm -f -- "$APP/$added"
        fi
    done
    refresh_views
    echo 'ROLLBACK COMPLETE: Source restored without changing .env, database, or media.'
    exit 0
fi

[[ "$SHA" =~ ^[0-9a-f]{40}$ ]] || { echo 'Supply an exact 40-character Git commit with --sha.' >&2; exit 2; }
command -v gh >/dev/null && command -v git >/dev/null || {
    echo 'GitHub CLI and Git must be installed on aaPanel.' >&2; exit 1;
}
gh auth status >/dev/null 2>&1 || { echo 'GitHub CLI is not authenticated. Run gh auth login.' >&2; exit 1; }
[ -d "$SOURCE/.git" ] || { echo "Missing GitHub checkout: $SOURCE" >&2; exit 1; }
remote="$(git -C "$SOURCE" remote get-url origin)"
case "$remote" in
    *github.com*moyuradesign/edumia|*github.com*moyuradesign/edumia.git) ;;
    *) echo 'Checkout origin does not match moyuradesign/edumia.' >&2; exit 1 ;;
esac
gh repo view "$REPOSITORY" --json nameWithOwner --jq '.nameWithOwner' | grep -Fxq "$REPOSITORY" || {
    echo 'GitHub repository authorization could not be verified.' >&2; exit 1;
}
git -C "$SOURCE" fetch -q origin main
git -C "$SOURCE" cat-file -e "$SHA^{commit}" || {
    echo 'Pinned commit was not found in the checkout.' >&2; exit 1;
}
git -C "$SOURCE" cat-file -e "$BASE_SHA^{commit}" || {
    echo 'Approved pre-upgrade baseline is missing.' >&2; exit 1;
}
git -C "$SOURCE" merge-base --is-ancestor "$BASE_SHA" "$SHA" || {
    echo 'Pinned commit does not descend from approved instructor baseline.' >&2; exit 1;
}
git -C "$SOURCE" merge-base --is-ancestor "$SHA" refs/remotes/origin/main || {
    echo 'Pinned commit is not an ancestor of origin/main.' >&2; exit 1;
}

STAGE="$(mktemp -d /root/.edumia-media-sync.XXXXXX)"
mkdir -p "$STAGE/new" "$STAGE/base"
git -C "$SOURCE" archive "$SHA" -- $FILES | tar -xf - -C "$STAGE/new"
git -C "$SOURCE" archive "$BASE_SHA" -- $EXISTING | tar -xf - -C "$STAGE/base"
for relative in $FILES; do
    [ -s "$STAGE/new/$relative" ] && [ ! -L "$STAGE/new/$relative" ] || {
        echo "Pinned source missing file: $relative" >&2; exit 1;
    }
done
for relative in app/Http/Controllers/Admin/Content/MediaController.php \
                app/Http/Controllers/Admin/Content/BlogController.php \
                app/Services/Content/BlogPublishingService.php routes/admin.php; do
    "$PHP" -l "$STAGE/new/$relative" >/dev/null
done
# The accepted instructor deployment omitted admin redirect and two Media
# Library routes. Enable those media routes without restoring admin redirect.
cat > "$STAGE/old-omissions.txt" <<'OLD_ROUTES'
        // Canonical administrator entrance; preserve /dashboard as the existing overview.
        Route::get('/', fn () => redirect()->route('dashboard'))->name('index');
        Route::get('/media/{media}/preview', [MediaController::class, 'preview'])->name('media.preview');
        Route::put('/media/{media}', [MediaController::class, 'update'])->name('media.update');
OLD_ROUTES
cat > "$STAGE/new-omissions.txt" <<'NEW_ROUTES'
        // Canonical administrator entrance; preserve /dashboard as the existing overview.
        Route::get('/', fn () => redirect()->route('dashboard'))->name('index');
NEW_ROUTES
mkdir -p "$STAGE/variant" "$STAGE/base-variant"
while IFS= read -r omitted; do
    for original in "$STAGE/base/routes/admin.php" "$STAGE/new/routes/admin.php"; do
        [[ "$(grep -Fxc -- "$omitted" "$original" || true)" == 1 ]] || {
            echo "BLOCKED: GitHub routes changed beyond the approved media scope." >&2
            exit 1
        }
    done
done < "$STAGE/old-omissions.txt"
grep -Fvxf "$STAGE/old-omissions.txt" "$STAGE/base/routes/admin.php" > "$STAGE/base-variant/routes-admin.php"
grep -Fvxf "$STAGE/new-omissions.txt" "$STAGE/new/routes/admin.php" > "$STAGE/variant/routes-admin.php"
[[ "$(git -C "$SOURCE" hash-object "$STAGE/base-variant/routes-admin.php")" == "$APPROVED_LIVE_ROUTES" ]] || {
    echo "BLOCKED: Cannot reproduce previously approved instructor-release routing." >&2
    exit 1
}
"$PHP" -l "$STAGE/variant/routes-admin.php" >/dev/null

route_mode='canonical'
drift=0
for relative in $EXISTING; do
    live_hash="$(git -C "$SOURCE" hash-object "$APP/$relative")"
    baseline_hash="$(git -C "$SOURCE" hash-object "$STAGE/base/$relative")"
    target_hash="$(git -C "$SOURCE" hash-object "$STAGE/new/$relative")"
    if [[ "$relative" == 'routes/admin.php' ]]; then
        variant_hash="$(git -C "$SOURCE" hash-object "$STAGE/variant/routes-admin.php")"
        if [[ "$live_hash" == "$APPROVED_LIVE_ROUTES" || "$live_hash" == "$variant_hash" ]]; then
            cp "$STAGE/variant/routes-admin.php" "$STAGE/new/routes/admin.php"
            route_mode='audited-production'
        elif [[ "$live_hash" != "$baseline_hash" && "$live_hash" != "$target_hash" ]]; then
            echo "DRIFT: $relative: $live_hash"
            drift=1
        fi
    elif [[ "$live_hash" != "$baseline_hash" && "$live_hash" != "$target_hash" ]]; then
        echo "DRIFT: $relative: $live_hash"
        drift=1
    fi
done
echo "Route profile: $route_mode"
[[ "$drift" == 0 ]] || {
    echo 'BLOCKED: Unknown live Media Library source differences.' >&2
    exit 1
}
for relative in $ADDED; do
    if [ -L "$APP/$relative" ] ||
       { [ -e "$APP/$relative" ] && ! cmp -s "$APP/$relative" "$STAGE/new/$relative"; }; then
        echo "BLOCKED: Unexpected existing new-file path: $relative" >&2
        exit 1
    fi
done

echo '========== EDUMIA GITHUB CLI MEDIA SYNC =========='
echo "Mode:             $MODE"
echo "Repository:       $REPOSITORY"
echo "Pinned commit:    $SHA"
echo "Active Laravel:   $APP"
echo 'Scope:            Media Library, Blog featured images, protected media routes'
changes=0
for relative in $FILES; do
    if cmp -s "$STAGE/new/$relative" "$APP/$relative"; then
        echo "MATCH:            $relative"
    else
        echo "UPDATE:           $relative"
        changes=$((changes + 1))
    fi
done
echo "Files to update:  $changes of 6"
if [ "$MODE" = '--check' ]; then
    echo 'CHECK PASSED: Production source unchanged.'
    exit 0
fi
if [ "$changes" -eq 0 ]; then
    echo 'ALREADY INSTALLED: No production changes necessary.'
    exit 0
fi

mkdir -p -m 0700 "$BACKUPS"
BACKUP="$(mktemp -d "$BACKUPS/$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX")"
tar -czf "$BACKUP/files.tar.gz" -C "$APP" $EXISTING
: > "$BACKUP/created.txt"
for added in $ADDED; do
    if [ ! -e "$APP/$added" ]; then
        printf '%s\n' "$added" >> "$BACKUP/created.txt"
    fi
done
validate_archive "$BACKUP/files.tar.gz"
sha256sum "$BACKUP/files.tar.gz" | cut -d ' ' -f1 > "$BACKUP/files.sha256"
printf 'Source commit: %s\nApp: %s\n' "$SHA" "$APP" > "$BACKUP/source.txt"
echo "Protected backup: $BACKUP"

APPLY_STARTED=1
STEP='installing instructor source files'
for relative in $FILES; do
    if cmp -s "$STAGE/new/$relative" "$APP/$relative"; then
        continue
    fi
    directory="$(dirname "$APP/$relative")"
    temporary="$(mktemp "$directory/.edumia-media-sync.XXXXXX")"
    cp -- "$STAGE/new/$relative" "$temporary"
    if [ -f "$APP/$relative" ]; then
        chown --reference="$APP/$relative" "$temporary"
    else
        chown "$WEB_USER:$WEB_USER" "$temporary"
    fi
    chmod 0644 "$temporary"
    mv -f -- "$temporary" "$APP/$relative"
done
STEP='rebuilding Laravel views and clearing route cache'
refresh_views
STEP='validating media route registration'
verify_media_routes
STEP='recording deployed file checksums'
(cd "$APP" && sha256sum $FILES) > "$BACKUP/deployed.sha256"
APPLY_STARTED=0
echo "DEPLOYMENT SUCCESS: Media Library upgraded from GitHub commit $SHA"
echo "Protected rollback: $BACKUP"
echo 'No .env, database, media, storage, Nginx, or PHP-FPM changes.'
