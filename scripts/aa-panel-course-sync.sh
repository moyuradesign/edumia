#!/usr/bin/env bash
# Safe GitHub Main -> aaPanel sync for the Admin Course Management milestone.
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
BACKUPS='/root/edumia-course-sync-backups'
FILES='app/Http/Controllers/Admin/Courses/CourseController.php resources/views/admin/courses/index.blade.php'
MODE=''
SHA=''
ROLLBACK=''
STAGE=''
BACKUP=''
APPLY_STARTED=0

usage() {
    cat <<'HELP'
Usage:
  bash scripts/aa-panel-course-sync.sh --check --sha FULL40HEX
  bash scripts/aa-panel-course-sync.sh --apply --sha FULL40HEX
  bash scripts/aa-panel-course-sync.sh --rollback /root/edumia-course-sync-backups/BACKUP-NAME
Optional: --source /root/edumia-github --app /path/to/active/laravel

Before use:
  gh auth status
  gh repo clone moyuradesign/edumia /root/edumia-github -- --branch main

This is a targeted Course Management sync, not a whole-repository deployment.
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
[ -f "$APP/config/view.php" ] && [ -f "$APP/scripts/view-cache-preflight.php" ] || {
    echo 'Release-local Blade cache hardening must be installed first.' >&2; exit 1;
}
[ ! -L "$APP/bootstrap/cache/views" ] || { echo 'View cache must not be a symlink.' >&2; exit 1; }
runuser -u "$WEB_USER" -- "$PHP" "$APP/scripts/view-cache-preflight.php" --root="$APP" >/dev/null || {
    echo 'View cache permissions/preflight failed; no files changed.' >&2; exit 1;
}
for relative in $FILES; do
    [ -f "$APP/$relative" ] && [ ! -L "$APP/$relative" ] || {
        echo "Missing or symlinked live file: $relative" >&2; exit 1;
    }
done

clean_up() {
    if [ -n "$STAGE" ] && [ -d "$STAGE" ]; then
        rm -rf -- "$STAGE"
    fi
}
on_error() {
    status="$1"
    trap - ERR
    if [ "$APPLY_STARTED" -eq 1 ] && [ -n "$BACKUP" ] && [ -f "$BACKUP/files.tar.gz" ]; then
        echo 'APPLY FAILED: Restoring protected pre-sync source backup.' >&2
        tar -xzf "$BACKUP/files.tar.gz" -C "$APP" || true
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
    expected="$(printf '%s\n' $FILES | LC_ALL=C sort)"
    [ "$actual" = "$expected" ] || {
        echo 'Backup contents do not match the two approved source files.' >&2; return 1;
    }
}

refresh_views() {
    (
        cd "$APP"
        runuser -u "$WEB_USER" -- "$PHP" artisan view:clear
        runuser -u "$WEB_USER" -- "$PHP" artisan view:cache
        runuser -u "$WEB_USER" -- "$PHP" scripts/view-cache-preflight.php --root="$APP"
    )
}

if [ "$MODE" = '--rollback' ]; then
    case "$ROLLBACK" in
        "$BACKUPS"/*) ;;
        *) echo 'Rollback path is not inside the protected Course Management backup directory.' >&2; exit 1 ;;
    esac
    [ -d "$ROLLBACK" ] && [ ! -L "$ROLLBACK" ] || exit 1
    [ -f "$ROLLBACK/files.tar.gz" ] && [ -f "$ROLLBACK/files.sha256" ] || exit 1
    expected_hash="$(cat "$ROLLBACK/files.sha256")"
    actual_hash="$(sha256sum "$ROLLBACK/files.tar.gz" | cut -d ' ' -f1)"
    [ "$expected_hash" = "$actual_hash" ] || { echo 'Rollback backup hash mismatch.' >&2; exit 1; }
    validate_archive "$ROLLBACK/files.tar.gz"
    tar -xzf "$ROLLBACK/files.tar.gz" -C "$APP"
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
git -C "$SOURCE" merge-base --is-ancestor "$SHA" refs/remotes/origin/main || {
    echo 'Pinned commit is not an ancestor of origin/main.' >&2; exit 1;
}

STAGE="$(mktemp -d /root/.edumia-course-sync.XXXXXX)"
git -C "$SOURCE" archive "$SHA" -- $FILES | tar -xf - -C "$STAGE"
for relative in $FILES; do
    [ -s "$STAGE/$relative" ] && [ ! -L "$STAGE/$relative" ] || {
        echo "Pinned source missing file: $relative" >&2; exit 1;
    }
done
"$PHP" -l "$STAGE/app/Http/Controllers/Admin/Courses/CourseController.php" >/dev/null

echo '========== EDUMIA GITHUB CLI COURSE SYNC =========='
echo "Mode:             $MODE"
echo "Repository:       $REPOSITORY"
echo "Pinned commit:    $SHA"
echo "Active Laravel:   $APP"
echo 'Scope:            Admin Course Management only'
changes=0
for relative in $FILES; do
    if cmp -s "$STAGE/$relative" "$APP/$relative"; then
        echo "MATCH:            $relative"
    else
        echo "UPDATE:           $relative"
        changes=$((changes + 1))
    fi
done
echo "Files to update:  $changes of 2"
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
tar -czf "$BACKUP/files.tar.gz" -C "$APP" $FILES
validate_archive "$BACKUP/files.tar.gz"
sha256sum "$BACKUP/files.tar.gz" | cut -d ' ' -f1 > "$BACKUP/files.sha256"
printf 'Source commit: %s\nApp: %s\n' "$SHA" "$APP" > "$BACKUP/source.txt"
echo "Protected backup: $BACKUP"

APPLY_STARTED=1
for relative in $FILES; do
    if cmp -s "$STAGE/$relative" "$APP/$relative"; then
        continue
    fi
    directory="$(dirname "$APP/$relative")"
    temporary="$(mktemp "$directory/.edumia-course-sync.XXXXXX")"
    cp -- "$STAGE/$relative" "$temporary"
    chown --reference="$APP/$relative" "$temporary"
    chmod 0644 "$temporary"
    mv -f -- "$temporary" "$APP/$relative"
done
refresh_views
APPLY_STARTED=0
echo "DEPLOYMENT SUCCESS: Course Management upgraded from GitHub commit $SHA"
echo "Protected rollback: $BACKUP"
echo 'No .env, database, media, storage, Nginx, or PHP-FPM changes.'
