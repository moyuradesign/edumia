#!/usr/bin/env bash
# Edumia General Settings / Roles & Permissions targeted aaPanel synchronization.
# Only the seven approved source files; never touch app runtime configuration,
# uploaded media, Nginx, PHP-FPM, database, or unrelated Laravel modules.
set -Eeuo pipefail
umask 077

REPOSITORY='moyuradesign/edumia'
SOURCE='/root/edumia-github'
APP='/www/wwwroot/edumia-releases/edumia-blog-b4a7ba6c-Tc5UKAOs'
PHP='/www/server/php/85/bin/php'
WEB_USER='www'
BASE_SHA='b50554f8bd2377e62395ca1e9a017568bacadeb2'
BACKUPS='/root/edumia-governance-sync-backups'
FILES='app/Http/Requests/Admin/GeneralSettingsRequest.php app/Http/Controllers/Admin/System/GeneralSettingsController.php resources/views/admin/system/settings/general.blade.php app/Services/Identity/RoleManagementService.php app/Http/Requests/Admin/RoleRequest.php app/Http/Controllers/Admin/Users/RoleController.php resources/views/admin/users/roles/index.blade.php'
MODE=''
SHA=''
ROLLBACK=''
STAGE=''
BACKUP=''
APPLY_STARTED=0
STEP='preflight'

usage() {
    cat <<'HELP'
Usage:
  bash scripts/aa-panel-governance-sync.sh --check --sha FULL40HEX
  bash scripts/aa-panel-governance-sync.sh --apply --sha FULL40HEX
  bash scripts/aa-panel-governance-sync.sh --rollback /root/edumia-governance-sync-backups/BACKUP-NAME

Requires root, authenticated GitHub CLI, PHP 8.5, the accepted Edumia
installation, and a separate /root/edumia-github Main checkout.
This script does not deploy CSS/JS or modify routes/migrations.
HELP
}
while [ "$#" -gt 0 ]; do
    case "$1" in
        --check|--apply) [ -z "$MODE" ] || exit 2; MODE="$1"; shift ;;
        --rollback) [ -z "$MODE" ] && [ "$#" -ge 2 ] || exit 2; MODE='--rollback'; ROLLBACK="$2"; shift 2 ;;
        --sha) [ "$#" -ge 2 ] || exit 2; SHA="$2"; shift 2 ;;
        --help|-h) usage; exit 0 ;;
        *) usage >&2; exit 2 ;;
    esac
done
[ -n "$MODE" ] && [ "$(id -u)" -eq 0 ] || { usage >&2; exit 2; }
[ -x "$PHP" ] && [ -f "$APP/artisan" ] && [ -f "$APP/vendor/autoload.php" ] || {
    echo 'BLOCKED: Active Edumia release or PHP runtime missing.' >&2
    exit 1
}
id "$WEB_USER" >/dev/null

verify_view_cache() {
    (
        cd "$APP"
        runuser -u "$WEB_USER" -- "$PHP" -r '
            require "vendor/autoload.php";
            $app = require "bootstrap/app.php";
            $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            $expected = base_path("bootstrap/cache/views");
            $actual = config("view.compiled");
            if ($actual !== $expected || !is_dir($expected)
                || is_link($expected) || realpath($expected) !== $expected
                || !is_writable($expected)) {
                fwrite(STDERR, "EDUMIA_VIEW_CACHE=FAIL".PHP_EOL);
                exit(1);
            }
            echo "EDUMIA_VIEW_CACHE=PASS".PHP_EOL;
        '
    )
}
verify_view_cache

cleanup() {
    if [ -n "$STAGE" ] && [ -d "$STAGE" ]; then
        rm -rf -- "$STAGE"
    fi
}
on_error() {
    local status="$1"
    trap - ERR
    # ERR traps are inherited into subshells with set -E. Restore only once,
    # from the parent shell, never from nested output/process substitutions.
    if (( BASH_SUBSHELL > 0 )); then
        return "$status"
    fi
    echo "DEPLOYMENT STOPPED: $STEP (exit $status)." >&2
    if [ "$APPLY_STARTED" -eq 1 ] && [ -n "$BACKUP" ] && [ -f "$BACKUP/files.tar.gz" ]; then
        APPLY_STARTED=0
        echo 'APPLY FAILED: restoring protected seven-file pre-sync backup.' >&2
        tar -xzf "$BACKUP/files.tar.gz" -C "$APP" || echo 'RESTORE ERROR: Manual review is required.' >&2
        (cd "$APP" && runuser -u "$WEB_USER" -- "$PHP" artisan view:clear) || true
        (cd "$APP" && runuser -u "$WEB_USER" -- "$PHP" artisan view:cache) || true
    fi
    cleanup
    exit "$status"
}
trap cleanup EXIT
trap 'on_error "$?"' ERR

validate_archive() {
    local archive="$1"
    [ -s "$archive" ] || { echo 'BLOCKED: Backup archive not found.' >&2; return 1; }
    local expected actual
    expected="$(printf '%s\n' $FILES | LC_ALL=C sort)"
    actual="$(tar -tzf "$archive" | LC_ALL=C sort)"
    [ "$expected" = "$actual" ] || {
        echo 'BLOCKED: Backup archive does not contain the seven approved files.' >&2
        return 1
    }
}
refresh_views() {
    (
        cd "$APP"
        runuser -u "$WEB_USER" -- "$PHP" artisan view:clear
        runuser -u "$WEB_USER" -- "$PHP" artisan view:cache
        verify_view_cache
    )
}
verify_governance_routes() {
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
            "admin.settings.edit" => ["GET", "admin/settings/general"],
            "admin.settings.update" => ["PUT", "admin/settings/general"],
            "admin.roles.index" => ["GET", "admin/roles"],
            "admin.roles.store" => ["POST", "admin/roles"],
            "admin.roles.update" => ["PUT", "admin/roles/{role}"],
            "admin.roles.members" => ["POST", "admin/roles/{role}/members"],
        ];
        foreach ($required as $name => [$verb, $uri]) {
            $row = $found[$name] ?? null;
            if (!$row || !in_array($verb, explode("|", (string) ($row["method"] ?? "")), true)
                || trim((string) ($row["uri"] ?? ""), "/") !== $uri) {
                fwrite(STDERR, "BLOCKED: Invalid governance route: ".$name.PHP_EOL);
                exit(1);
            }
        }
        echo "EDUMIA_GOVERNANCE_ROUTES=PASS (6 registered endpoints)".PHP_EOL;
    ' "$STAGE/registered-routes.json"
}

if [ "$MODE" = '--rollback' ]; then
    case "$ROLLBACK" in "$BACKUPS"/*) ;; *)
        echo 'BLOCKED: Rollback path outside protected governance backups.' >&2; exit 1 ;;
    esac
    [ -d "$ROLLBACK" ] && [ ! -L "$ROLLBACK" ] || exit 1
    [ -f "$ROLLBACK/files.tar.gz" ] && [ -f "$ROLLBACK/files.sha256" ] && [ -f "$ROLLBACK/deployed.sha256" ] || {
        echo 'BLOCKED: Rollback requires a completed deployment manifest.' >&2; exit 1;
    }
    actual_hash="$(sha256sum "$ROLLBACK/files.tar.gz" | cut -d ' ' -f1)"
    [ "$actual_hash" = "$(cat "$ROLLBACK/files.sha256")" ] || {
        echo 'BLOCKED: Protected backup hash mismatch.' >&2; exit 1;
    }
    validate_archive "$ROLLBACK/files.tar.gz"
    (cd "$APP" && sha256sum -c --quiet "$ROLLBACK/deployed.sha256") || {
        echo 'BLOCKED: Production files changed after deployment; inspect before rollback.' >&2
        exit 1
    }
    STEP='restoring original governance source'
    tar -xzf "$ROLLBACK/files.tar.gz" -C "$APP"
    STEP='rebuilding original Blade cache'
    refresh_views
    echo 'ROLLBACK COMPLETE: Governance source restored.'
    exit 0
fi

[[ "$SHA" =~ ^[0-9a-f]{40}$ ]] || { echo 'BLOCKED: Exact 40-digit commit SHA required.' >&2; exit 2; }
command -v gh >/dev/null && command -v git >/dev/null || exit 1
gh auth status >/dev/null 2>&1 || { echo 'BLOCKED: GitHub CLI not authenticated.' >&2; exit 1; }
[ -d "$SOURCE/.git" ] || { echo 'BLOCKED: GitHub checkout missing.' >&2; exit 1; }
case "$(git -C "$SOURCE" remote get-url origin)" in
    *github.com*moyuradesign/edumia|*github.com*moyuradesign/edumia.git) ;;
    *) echo 'BLOCKED: GitHub origin mismatch.' >&2; exit 1 ;;
esac
gh repo view "$REPOSITORY" --json nameWithOwner --jq '.nameWithOwner' | grep -Fxq "$REPOSITORY" || {
    echo 'BLOCKED: GitHub repository authorization failed.' >&2; exit 1;
}
git -C "$SOURCE" fetch -q origin main
git -C "$SOURCE" cat-file -e "$SHA^{commit}"
git -C "$SOURCE" cat-file -e "$BASE_SHA^{commit}"
git -C "$SOURCE" merge-base --is-ancestor "$BASE_SHA" "$SHA" || {
    echo 'BLOCKED: Release does not descend from accepted Media Library baseline.' >&2; exit 1;
}
git -C "$SOURCE" merge-base --is-ancestor "$SHA" refs/remotes/origin/main || {
    echo 'BLOCKED: Pinned release is not on GitHub Main.' >&2; exit 1;
}
STAGE="$(mktemp -d /root/.edumia-governance-sync.XXXXXXXX)"
mkdir -p "$STAGE/base" "$STAGE/new"
git -C "$SOURCE" archive "$BASE_SHA" -- $FILES | tar -xf - -C "$STAGE/base"
git -C "$SOURCE" archive "$SHA" -- $FILES | tar -xf - -C "$STAGE/new"

missing=0
drift=0
changes=0
echo '========== EDUMIA GOVERNANCE SYNC =========='
echo "Mode:             $MODE"
echo "Repository:       $REPOSITORY"
echo "Pinned commit:    $SHA"
echo "Active Laravel:   $APP"
for file in $FILES; do
    if [ ! -f "$APP/$file" ] || [ -L "$APP/$file" ]; then
        echo "BLOCKED: Required source file missing/symlinked: $file" >&2
        missing=1
        continue
    fi
    [ -s "$STAGE/base/$file" ] && [ -s "$STAGE/new/$file" ] || {
        echo "BLOCKED: Approved GitHub source missing file: $file" >&2; exit 1;
    }
    case "$file" in *.php) "$PHP" -l "$STAGE/new/$file" >/dev/null ;; esac
    live_hash="$(git -C "$SOURCE" hash-object "$APP/$file")"
    base_hash="$(git -C "$SOURCE" hash-object "$STAGE/base/$file")"
    new_hash="$(git -C "$SOURCE" hash-object "$STAGE/new/$file")"
    if [ "$live_hash" != "$base_hash" ] && [ "$live_hash" != "$new_hash" ]; then
        echo "DRIFT: $file: $live_hash"
        drift=1
    fi
    if cmp -s "$APP/$file" "$STAGE/new/$file"; then
        echo "MATCH:            $file"
    else
        echo "UPDATE:           $file"
        changes=$((changes + 1))
    fi
done
echo "Files to update:  $changes of 7"
[ "$missing" -eq 0 ] && [ "$drift" -eq 0 ] || {
    echo 'BLOCKED: Existing governance code differs from approved release; no files changed.' >&2
    exit 1
}
if [ "$MODE" = '--check' ]; then
    echo 'CHECK PASSED: Production source unchanged.'
    exit 0
fi
if [ "$changes" -eq 0 ]; then
    echo 'ALREADY INSTALLED: Seven source files match the pinned release.'
    exit 0
fi

STEP='creating protected seven-file backup'
mkdir -p -m 0700 "$BACKUPS"
BACKUP="$(mktemp -d "$BACKUPS/$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX")"
tar -czf "$BACKUP/files.tar.gz" -C "$APP" $FILES
validate_archive "$BACKUP/files.tar.gz"
sha256sum "$BACKUP/files.tar.gz" | cut -d ' ' -f1 > "$BACKUP/files.sha256"
printf 'Source commit: %s\nActive installation: %s\n' "$SHA" "$APP" > "$BACKUP/source.txt"
echo "Protected backup: $BACKUP"

APPLY_STARTED=1
STEP='replacing approved governance files'
for file in $FILES; do
    if cmp -s "$APP/$file" "$STAGE/new/$file"; then continue; fi
    directory="$(dirname "$APP/$file")"
    temp="$(mktemp "$directory/.edumia-governance-sync.XXXXXXXX")"
    cp -- "$STAGE/new/$file" "$temp"
    chown --reference="$APP/$file" "$temp"
    chmod 0644 "$temp"
    mv -f -- "$temp" "$APP/$file"
done
STEP='refreshing Blade views without changing routes'
refresh_views
STEP='validating existing governance routes'
verify_governance_routes
STEP='recording deployed source checksums'
(cd "$APP" && sha256sum $FILES) > "$BACKUP/deployed.sha256"
APPLY_STARTED=0
echo "DEPLOYMENT SUCCESS: Governance upgraded from GitHub commit $SHA"
echo "Protected rollback: $BACKUP"
echo 'No .env, database, uploads, storage, routes, Nginx, PHP-FPM, or compiled frontend assets changed.'
