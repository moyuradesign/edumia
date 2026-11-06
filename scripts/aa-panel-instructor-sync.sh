#!/usr/bin/env bash
# Safe GitHub Main -> aaPanel sync for the Admin Instructor Management milestone.
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
BACKUPS='/root/edumia-instructor-sync-backups'
BASE_SHA='9cbd579442bd902a6d6e45adba92b94af628db78'
# Exact Git blob hashes from the 2026-11-06 read-only live reconciliation.
# They permit ONLY known missing/altered instructor and routing source.
APPROVED_LIVE_CONTROLLER='5318ae7bf46231d7b8ee000d1304101b4f0bfae6'
APPROVED_LIVE_APPLICATION_VIEW='005b033aeb05e12a76b91b24280bcaa53bf50b7b'
APPROVED_LIVE_ROUTES='755492dd287615f6fdd6ec607006c109e4be48c2'
# The live site predates the instructor-directory view; it may be absent.
# Classify that view as new so it is only created when missing, and removed
# on rollback. Never overwrite an existing unknown version.
EXISTING='app/Http/Controllers/Admin/Users/InstructorApplicationController.php app/Services/Identity/InstructorApplicationService.php resources/views/admin/users/instructor-applications.blade.php routes/admin.php'
ADDED='resources/views/admin/users/instructors.blade.php app/Services/Identity/InstructorManagementService.php resources/views/admin/users/instructor-assignments.blade.php'
FILES="$EXISTING $ADDED"
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
  bash scripts/aa-panel-instructor-sync.sh --check --sha FULL40HEX
  bash scripts/aa-panel-instructor-sync.sh --apply --sha FULL40HEX
  bash scripts/aa-panel-instructor-sync.sh --rollback /root/edumia-instructor-sync-backups/BACKUP-NAME
Optional: --source /root/edumia-github --app /path/to/active/laravel

Before use:
  gh auth status
  gh repo clone moyuradesign/edumia /root/edumia-github -- --branch main

This is a targeted Instructor Management sync, not a whole-repository deployment.
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
        echo 'Backup contents do not match the four protected original files.' >&2; return 1;
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

verify_instructor_routes() {
    (
        cd "$APP"
        runuser -u "$WEB_USER" -- "$PHP" artisan route:list --json
    ) > "$STAGE/registered-routes.json"

    "$PHP" -r '
        $rows = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rows)) {
            fwrite(STDERR, "BLOCKED: Unable to parse registered routes.\n");
            exit(1);
        }
        $found = [];
        foreach ($rows as $item) {
            if (is_array($item) && is_string($item["name"] ?? null)) {
                $found[$item["name"]] = $item;
            }
        }
        $required = [
            "admin.instructors.index" => ["GET", "admin/instructors"],
            "admin.instructors.assignments" => ["GET", "admin/instructors/{user}/assignments"],
            "admin.instructors.status" => ["PATCH", "admin/instructors/{user}/status"],
            "admin.instructors.assignments.reassign" => ["POST", "admin/instructors/{user}/assignments/{course}"],
        ];
        $problems = [];
        foreach ($required as $name => [$verb, $uri]) {
            $item = $found[$name] ?? null;
            if (! $item) {
                $problems[] = "Missing instructor route: ".$name;
            } elseif (! in_array($verb, explode("|", (string) ($item["method"] ?? "")), true)
                || trim((string) ($item["uri"] ?? ""), "/") !== $uri) {
                $problems[] = "Incorrect instructor route method or URI: ".$name;
            }
        }
        if ($problems !== []) {
            foreach ($problems as $problem) {
                fwrite(STDERR, "BLOCKED: ".$problem.PHP_EOL);
            }
            $visible = array_keys(array_filter($found,
                static fn ($item, $name) => str_contains($name, "instructor"),
                ARRAY_FILTER_USE_BOTH));
            fwrite(STDERR, "Visible instructor routes: ".(
                $visible === [] ? "(none)" : implode(", ", $visible)
            ).PHP_EOL);
            exit(1);
        }
        echo "EDUMIA_INSTRUCTOR_ROUTES=PASS (4 registered routes)".PHP_EOL;
    ' "$STAGE/registered-routes.json"
}

if [ "$MODE" = '--rollback' ]; then
    case "$ROLLBACK" in
        "$BACKUPS"/*) ;;
        *) echo 'Rollback path is not inside the protected Instructor Management backup directory.' >&2; exit 1 ;;
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

STAGE="$(mktemp -d /root/.edumia-instructor-sync.XXXXXX)"
mkdir -p "$STAGE/new" "$STAGE/base"
git -C "$SOURCE" archive "$SHA" -- $FILES | tar -xf - -C "$STAGE/new"
git -C "$SOURCE" archive "$BASE_SHA" -- $EXISTING | tar -xf - -C "$STAGE/base"
for relative in $FILES; do
    [ -s "$STAGE/new/$relative" ] && [ ! -L "$STAGE/new/$relative" ] || {
        echo "Pinned source missing file: $relative" >&2; exit 1;
    }
done
for relative in app/Http/Controllers/Admin/Users/InstructorApplicationController.php \
                app/Services/Identity/InstructorApplicationService.php \
                app/Services/Identity/InstructorManagementService.php routes/admin.php; do
    "$PHP" -l "$STAGE/new/$relative" >/dev/null
done
# Construct a deterministic production-specific routes variant. The live
# routing differences were audited as four missing lines (admin redirect
# comment + route, and two media routes) plus the instructor directory route.
# Do not restore the unrelated routes merely to enable instructor features.
cat > "$STAGE/observed-route-omissions.txt" <<'ROUTE_OMISSIONS'
        // Canonical administrator entrance; preserve /dashboard as the existing overview.
        Route::get('/', fn () => redirect()->route('dashboard'))->name('index');
        Route::get('/media/{media}/preview', [MediaController::class, 'preview'])->name('media.preview');
        Route::put('/media/{media}', [MediaController::class, 'update'])->name('media.update');
ROUTE_OMISSIONS

mkdir -p "$STAGE/variant" "$STAGE/base-variant"
# Every omission must be present exactly once in the audited GitHub source.
while IFS= read -r omitted; do
    for original in "$STAGE/base/routes/admin.php" "$STAGE/new/routes/admin.php"; do
        matches="$(grep -Fxc -- "$omitted" "$original" || true)"
        [ "$matches" = 1 ] || {
            echo "BLOCKED: Route reconciliation source has changed: $omitted" >&2
            exit 1
        }
    done
done < "$STAGE/observed-route-omissions.txt"
grep -Fvxf "$STAGE/observed-route-omissions.txt" \
    "$STAGE/base/routes/admin.php" > "$STAGE/base-variant/routes-admin.php"
grep -Fvxf "$STAGE/observed-route-omissions.txt" \
    "$STAGE/new/routes/admin.php" > "$STAGE/variant/routes-admin.php"
directory_route="        Route::get('/instructors', [InstructorApplicationController::class, 'faculty'])->name('instructors.index');"
[ "$(grep -Fxc -- "$directory_route" "$STAGE/base-variant/routes-admin.php" || true)" = 1 ] || {
    echo 'BLOCKED: Instructor directory baseline has changed.' >&2
    exit 1
}
grep -Fvx -- "$directory_route" "$STAGE/base-variant/routes-admin.php" \
    > "$STAGE/base-variant/without-directory.php"
[ "$(git -C "$SOURCE" hash-object "$STAGE/base-variant/without-directory.php")" = "$APPROVED_LIVE_ROUTES" ] || {
    echo 'BLOCKED: Audited live route variant cannot be reproduced.' >&2
    exit 1
}
"$PHP" -l "$STAGE/variant/routes-admin.php" >/dev/null

# Stop before any write if a live file differs from both the approved GitHub
# sources and the exact, previously audited production variant.
route_mode='canonical'
for relative in $EXISTING; do
    live_hash="$(git -C "$SOURCE" hash-object "$APP/$relative")"
    baseline_hash="$(git -C "$SOURCE" hash-object "$STAGE/base/$relative")"
    target_hash="$(git -C "$SOURCE" hash-object "$STAGE/new/$relative")"

    if [ "$relative" = 'routes/admin.php' ]; then
        reconciled_hash="$(git -C "$SOURCE" hash-object "$STAGE/variant/routes-admin.php")"
        if [ "$live_hash" = "$APPROVED_LIVE_ROUTES" ] ||
           [ "$live_hash" = "$reconciled_hash" ]; then
            route_mode='audited-production'
            cp -- "$STAGE/variant/routes-admin.php" "$STAGE/new/routes/admin.php"
        elif [ "$live_hash" != "$baseline_hash" ] &&
             [ "$live_hash" != "$target_hash" ]; then
            echo "BLOCKED: Unknown live route changes: $relative" >&2
            exit 1
        fi
    elif [ "$live_hash" != "$baseline_hash" ] &&
         [ "$live_hash" != "$target_hash" ]; then
        approved=''
        case "$relative" in
            app/Http/Controllers/Admin/Users/InstructorApplicationController.php)
                approved="$APPROVED_LIVE_CONTROLLER" ;;
            resources/views/admin/users/instructor-applications.blade.php)
                approved="$APPROVED_LIVE_APPLICATION_VIEW" ;;
        esac
        if [ -z "$approved" ] || [ "$live_hash" != "$approved" ]; then
            echo "BLOCKED: Live source differs from approved baseline: $relative" >&2
            echo 'Inspect and reconcile those local changes before deploying.' >&2
            exit 1
        fi
        echo "AUDITED VARIANT:  $relative"
    fi
done
echo "Route profile:    $route_mode (unrelated live routes preserved)"
for relative in $ADDED; do
    if [ -L "$APP/$relative" ] ||
       { [ -e "$APP/$relative" ] && ! cmp -s "$APP/$relative" "$STAGE/new/$relative"; }; then
        echo "BLOCKED: Unexpected existing new-file path: $relative" >&2
        exit 1
    fi
done

echo '========== EDUMIA GITHUB CLI INSTRUCTOR SYNC =========='
echo "Mode:             $MODE"
echo "Repository:       $REPOSITORY"
echo "Pinned commit:    $SHA"
echo "Active Laravel:   $APP"
echo 'Scope:            Instructor administration and assignments only'
changes=0
for relative in $FILES; do
    if cmp -s "$STAGE/new/$relative" "$APP/$relative"; then
        echo "MATCH:            $relative"
    else
        echo "UPDATE:           $relative"
        changes=$((changes + 1))
    fi
done
echo "Files to update:  $changes of 7"
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
    temporary="$(mktemp "$directory/.edumia-instructor-sync.XXXXXX")"
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
STEP='validating instructor route registration'
verify_instructor_routes
STEP='recording deployed file checksums'
(cd "$APP" && sha256sum $FILES) > "$BACKUP/deployed.sha256"
APPLY_STARTED=0
echo "DEPLOYMENT SUCCESS: Instructor Management upgraded from GitHub commit $SHA"
echo "Protected rollback: $BACKUP"
echo 'No .env, database, media, storage, Nginx, or PHP-FPM changes.'
