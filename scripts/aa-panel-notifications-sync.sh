#!/usr/bin/env bash
# Edumia Notifications targeted deployment: no migration or live SMTP activation.
set -Eeuo pipefail
umask 077
SOURCE=/root/edumia-github
APP=/www/wwwroot/edumia-releases/edumia-blog-b4a7ba6c-Tc5UKAOs
PHP=/www/server/php/85/bin/php
WEB_USER=www
BASE=48ed8a95a9acf722df3282b638c2f08b4a8b4c7a
BACKUPS=/root/edumia-notifications-sync-backups
EXISTING='app/Services/Identity/InstructorApplicationService.php app/Services/Support/TicketService.php app/Http/Controllers/Auth/PasswordController.php config/mail.php routes/auth.php resources/views/partials/admin-topbar.blade.php resources/views/partials/member-topbar.blade.php'
ADDED='app/Models/NotificationSetting.php app/Notifications/OperationalNotice.php app/Services/Notifications/NotificationService.php app/Http/Controllers/Account/NotificationController.php app/Http/Controllers/Admin/System/MailDeliveryController.php app/Mail/OperationalMail.php app/Jobs/SendOperationalMail.php resources/views/account/notifications/index.blade.php resources/views/account/notifications/preferences.blade.php resources/views/admin/system/mail-delivery.blade.php resources/views/emails/operational.blade.php config/notifications.php config/queue.php'
FILES="$EXISTING $ADDED"
MODE='' SHA='' ROLLBACK='' BACKUP='' STAGE='' STEP=preflight
APPLY_STARTED=0

usage() {
 echo 'Usage: aa-panel-notifications-sync.sh --check|--apply --sha FULL40HEX'
 echo '       aa-panel-notifications-sync.sh --rollback /root/edumia-notifications-sync-backups/EXACT_BACKUP'
}
while (( $# > 0 )); do
 case "$1" in
  --check|--apply) [[ -z "$MODE" ]] || exit 2; MODE="$1"; shift ;;
  --rollback) [[ -z "$MODE" && $# -ge 2 ]] || exit 2; MODE=--rollback; ROLLBACK="$2"; shift 2 ;;
  --sha) [[ $# -ge 2 ]] || exit 2; SHA="$2"; shift 2 ;;
  --help|-h) usage; exit 0 ;;
  *) usage >&2; exit 2 ;;
 esac
done
[[ -n "$MODE" && $(id -u) == 0 ]] || { usage >&2; exit 2; }
[[ -x "$PHP" && -f "$APP/artisan" && -f "$APP/vendor/autoload.php" ]] || {
 echo 'BLOCKED: Active Laravel release or PHP is missing.' >&2; exit 1;
}
id "$WEB_USER" >/dev/null
cleanup() { [[ -z "$STAGE" || ! -d "$STAGE" ]] || rm -rf -- "$STAGE"; }
on_error() {
 local status="$1"
 trap - ERR
 if (( BASH_SUBSHELL > 0 )); then return "$status"; fi
 echo "FAILED: $STEP (exit $status)" >&2
 if (( APPLY_STARTED == 1 )) && [[ -s "$BACKUP/originals.tar.gz" ]]; then
  APPLY_STARTED=0
  echo "RESTORING protected originals: $BACKUP" >&2
  tar -xzf "$BACKUP/originals.tar.gz" -C "$APP" || echo 'RESTORE FAILED: Manual recovery needed.' >&2
  while IFS= read -r item; do [[ -z "$item" ]] || rm -f -- "$APP/$item"; done < "$BACKUP/created.txt"
  (cd "$APP" && runuser -u "$WEB_USER" -- "$PHP" artisan route:clear) || true
  (cd "$APP" && runuser -u "$WEB_USER" -- "$PHP" artisan view:clear) || true
 fi
 exit "$status"
}
trap cleanup EXIT
trap 'on_error "$?"' ERR

verify_cache() {
 (cd "$APP" && runuser -u "$WEB_USER" -- "$PHP" -r '
 require "vendor/autoload.php";
 $app = require "bootstrap/app.php";
 $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
 $expected = base_path("bootstrap/cache/views");
 if (config("view.compiled") !== $expected || !is_dir($expected)
     || is_link($expected) || !is_writable($expected)) {
   fwrite(STDERR, "EDUMIA_VIEW_CACHE=FAIL".PHP_EOL); exit(1);
 }
 echo "EDUMIA_VIEW_CACHE=PASS".PHP_EOL;
 ')
}
verify_cache
refresh_cache() {
 (cd "$APP" &&
  runuser -u "$WEB_USER" -- "$PHP" artisan route:clear &&
  runuser -u "$WEB_USER" -- "$PHP" artisan view:clear &&
  runuser -u "$WEB_USER" -- "$PHP" artisan view:cache)
 verify_cache
}
in_scope() { printf '%s\n' $FILES | grep -Fxq -- "$1"; }
verify_backup() {
 local directory="$1" item
 [[ -s "$directory/originals.tar.gz" && -f "$directory/originals.txt" && -f "$directory/created.txt" ]] || return 1
 [[ "$(tar -tzf "$directory/originals.tar.gz" | LC_ALL=C sort)" == "$(LC_ALL=C sort "$directory/originals.txt")" ]] || return 1
 while IFS= read -r item; do [[ -z "$item" ]] || in_scope "$item" || return 1; done < "$directory/originals.txt"
 while IFS= read -r item; do [[ -z "$item" ]] || in_scope "$item" || return 1; done < "$directory/created.txt"
}
verify_routes() {
 (cd "$APP" && runuser -u "$WEB_USER" -- "$PHP" artisan route:list --json) > "$STAGE/routes.json"
 "$PHP" -r '
 $list=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);
 if (!is_array($list)) exit(1);
 $found=[];
 foreach ($list as $r) if (is_string($r["name"]??null)) $found[$r["name"]]=$r;
 $expected=[
 "account.notifications.index"=>["GET","account/notifications"],
 "account.notifications.read-all"=>["PATCH","account/notifications/read-all"],
 "account.notifications.read"=>["PATCH","account/notifications/{notification}/read"],
 "account.notifications.preferences.edit"=>["GET","account/notifications/preferences"],
 "account.notifications.preferences.update"=>["PUT","account/notifications/preferences"],
 "admin.notifications.delivery"=>["GET","admin/notifications/delivery"],
 "admin.notifications.delivery.test"=>["POST","admin/notifications/delivery/test"]];
 foreach ($expected as $name=>[$verb,$uri]) {
  $route=$found[$name]??null;
  if (!$route || !in_array($verb,explode("|",(string)($route["method"]??"")),true)
      || trim((string)($route["uri"]??""),"/")!==$uri) {
   fwrite(STDERR,"BLOCKED: Missing notification route ".$name.PHP_EOL);exit(1);
  }
 }
 foreach (["admin.notifications.delivery","admin.notifications.delivery.test"] as $name) {
  if (!in_array("role:admin",(array)($found[$name]["middleware"]??[]),true)) {
   fwrite(STDERR,"BLOCKED: Admin route lacks role guard.".PHP_EOL);exit(1);
  }
 }
 echo "EDUMIA_NOTIFICATION_ROUTES=PASS (7 protected endpoints)".PHP_EOL;
 ' "$STAGE/routes.json"
}

if [[ "$MODE" == --rollback ]]; then
 case "$ROLLBACK" in "$BACKUPS"/*) ;; *) echo 'BLOCKED: Unknown backup path.' >&2;exit 1;; esac
 [[ -d "$ROLLBACK" && ! -L "$ROLLBACK" && -f "$ROLLBACK/deployed.sha256" ]] || exit 1
 verify_backup "$ROLLBACK"
 [[ "$(sha256sum "$ROLLBACK/originals.tar.gz" | cut -d' ' -f1)" == "$(cat "$ROLLBACK/originals.sha256")" ]] || exit 1
 (cd "$APP" && sha256sum -c --quiet "$ROLLBACK/deployed.sha256") || {
  echo 'BLOCKED: Source changed after deployment.' >&2;exit 1;
 }
 tar -xzf "$ROLLBACK/originals.tar.gz" -C "$APP"
 while IFS= read -r item; do [[ -z "$item" ]] || rm -f -- "$APP/$item"; done < "$ROLLBACK/created.txt"
 refresh_cache
 echo 'ROLLBACK COMPLETE: Original source restored.'
 exit 0
fi

[[ "$SHA" =~ ^[0-9a-f]{40}$ ]] || { echo 'BLOCKED: Full SHA required.' >&2;exit 2; }
command -v git >/dev/null && command -v gh >/dev/null || exit 1
gh auth status >/dev/null 2>&1 || { echo 'BLOCKED: GitHub CLI not authenticated.' >&2;exit 1; }
[[ -d "$SOURCE/.git" ]] || exit 1
case "$(git -C "$SOURCE" remote get-url origin)" in
 *github.com*moyuradesign/edumia|*github.com*moyuradesign/edumia.git) ;;
 *) echo 'BLOCKED: GitHub remote mismatch.' >&2;exit 1;;
esac
git -C "$SOURCE" fetch -q origin main
git -C "$SOURCE" cat-file -e "$BASE^{commit}"
git -C "$SOURCE" cat-file -e "$SHA^{commit}"
git -C "$SOURCE" merge-base --is-ancestor "$BASE" "$SHA"
git -C "$SOURCE" merge-base --is-ancestor "$SHA" refs/remotes/origin/main
STAGE="$(mktemp -d /root/.edumia-notifications.XXXXXXXX)"
mkdir -p "$STAGE/previous" "$STAGE/new"
git -C "$SOURCE" archive "$BASE" -- $EXISTING | tar -xf - -C "$STAGE/previous"
git -C "$SOURCE" archive "$SHA" -- $FILES | tar -xf - -C "$STAGE/new"
: > "$STAGE/originals.txt"
: > "$STAGE/created.txt"
changes=0 drift=0
echo "========== EDUMIA NOTIFICATIONS SYNC =========="
echo "Mode: $MODE"
echo "Source SHA: $SHA"
echo "Active app: $APP"
for item in $FILES; do
 [[ -s "$STAGE/new/$item" ]] || { echo "BLOCKED: GitHub source missing: $item" >&2;exit 1; }
 case "$item" in *.php) "$PHP" -l "$STAGE/new/$item" >/dev/null;; esac
 target="$(git -C "$SOURCE" hash-object "$STAGE/new/$item")"
 if [[ -e "$APP/$item" || -L "$APP/$item" ]]; then
  if [[ ! -f "$APP/$item" || -L "$APP/$item" ]]; then echo "DRIFT: $item (not regular)";drift=1;continue;fi
  printf '%s\n' "$item" >> "$STAGE/originals.txt"
  live="$(git -C "$SOURCE" hash-object "$APP/$item")"
  if [[ -f "$STAGE/previous/$item" ]]; then
   previous="$(git -C "$SOURCE" hash-object "$STAGE/previous/$item")"
   if [[ "$live" != "$previous" && "$live" != "$target" ]]; then echo "DRIFT: $item: $live";drift=1;fi
  elif [[ "$live" != "$target" ]]; then
   expected_prior=''
   case "$item" in
    app/Services/Notifications/NotificationService.php)
     expected_prior='9383b6ef7c25e63bb77d7ec7ce16d305d20830aa' ;;
    app/Http/Controllers/Admin/System/MailDeliveryController.php)
     expected_prior='73b889cd2330738639bfd8933f2becede8c95772' ;;
    resources/views/admin/system/mail-delivery.blade.php)
     expected_prior='b7af83ca1bf7a0e34a61ef5ff159d8619729f9ef' ;;
    config/notifications.php)
     expected_prior='01e88cd4820dae23a5903aab2121b5376aaf967c' ;;
   esac

   if [[ -n "$expected_prior" && "$live" == "$expected_prior" ]]; then
    echo "KNOWN PRIOR GITHUB VERSION: $item"
   else
    echo "DRIFT: New path differs: $item: $live"
    drift=1
   fi
  fi
  if [[ "$live" == "$target" ]]; then echo "MATCH: $item";else echo "UPDATE: $item";changes=$((changes+1));fi
 elif [[ -f "$STAGE/previous/$item" ]]; then
  echo "DRIFT: Baseline file missing: $item";drift=1
 else
  printf '%s\n' "$item" >> "$STAGE/created.txt"
  echo "ADD: $item";changes=$((changes+1))
 fi
done
echo "Files to update: $changes of 20"
(( drift == 0 )) || { echo 'BLOCKED: Unknown production source differences.' >&2;exit 1; }
if [[ "$MODE" == --check ]]; then echo 'CHECK PASSED: Production source unchanged.';exit 0;fi
if (( changes == 0 )); then echo 'ALREADY INSTALLED: Source matches.';exit 0;fi
if [[ -f "$APP/.env" ]] && grep -Eiq '^[[:space:]]*EDUMIA_TRANSACTIONAL_MAIL_ENABLED[[:space:]]*=[[:space:]]*(true|1)[[:space:]]*$' "$APP/.env"; then
 echo 'BLOCKED: First release requires transactional email disabled.' >&2;exit 1
fi
STEP='creating source backup'
mkdir -p -m 0700 "$BACKUPS"
BACKUP="$(mktemp -d "$BACKUPS/$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX")"
cp "$STAGE/originals.txt" "$BACKUP/originals.txt"
cp "$STAGE/created.txt" "$BACKUP/created.txt"
tar -czf "$BACKUP/originals.tar.gz" -C "$APP" $(cat "$STAGE/originals.txt")
verify_backup "$BACKUP"
sha256sum "$BACKUP/originals.tar.gz" | cut -d' ' -f1 > "$BACKUP/originals.sha256"
printf 'commit=%s\n' "$SHA" > "$BACKUP/commit.txt"
echo "Protected backup: $BACKUP"
APPLY_STARTED=1
STEP='installing approved PHP and Blade source'
for item in $FILES; do
 if [[ -f "$APP/$item" ]] && cmp -s "$APP/$item" "$STAGE/new/$item"; then continue; fi
 dir="$(dirname "$APP/$item")"
 # umask 077 protects backups, but PHP-FPM must be able to traverse each
 # newly created application directory. Do not chmod existing directories.
 current="$APP"
 for part in $(dirname "$item" | tr '/' ' '); do
  current="$current/$part"
  if [[ ! -d "$current" ]]; then
   mkdir -- "$current"
   chmod 0755 "$current"
   chown "$WEB_USER:$WEB_USER" "$current"
  fi
 done
 tmp="$(mktemp "$dir/.edumia-notifications.XXXXXXXX")"
 cp "$STAGE/new/$item" "$tmp"
 if [[ -f "$APP/$item" ]]; then chown --reference="$APP/$item" "$tmp";else chown "$WEB_USER:$WEB_USER" "$tmp";fi
 chmod 0644 "$tmp"
 mv -f "$tmp" "$APP/$item"
done
STEP='rebuilding Laravel route and Blade cache'
refresh_cache
STEP='checking notification route guards'
verify_routes
STEP='recording deployed fingerprints'
(cd "$APP" && sha256sum $FILES) > "$BACKUP/deployed.sha256"
APPLY_STARTED=0
echo "DEPLOYMENT SUCCESS: Notifications installed from GitHub commit $SHA"
echo "Protected rollback: $BACKUP"
echo 'Email remains disabled until independent provider approval, protected env gates and queue worker setup.'
echo 'No DB migrations, .env, uploaded files, services or compiled assets were changed.'
