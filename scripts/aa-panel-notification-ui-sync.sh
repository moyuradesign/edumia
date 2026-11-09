#!/usr/bin/env bash
# Edumia notifications UI and Vite build: guarded aaPanel release.
set -Eeuo pipefail
umask 077
SOURCE=/root/edumia-github
APP=/www/wwwroot/edumia-releases/edumia-blog-b4a7ba6c-Tc5UKAOs
PHP=/www/server/php/85/bin/php
OWNER=www
REPO=moyuradesign/edumia
SHA=e74131110dddab4e5277a93d8a3acd5ada164885
PRIOR=3b6c5f7d13871727153ad9683292a06539b96bd1
BASE=48ed8a95a9acf722df3282b638c2f08b4a8b4c7a
QUALITY_RUN=42627826178
QA_RUN=42627826183
BACKUPS=/root/edumia-notification-ui-backups
EXISTING='app/Services/Identity/InstructorApplicationService.php app/Services/Support/TicketService.php app/Http/Controllers/Auth/PasswordController.php config/mail.php routes/auth.php resources/views/partials/admin-topbar.blade.php resources/views/partials/member-topbar.blade.php'
ADDED='app/Models/NotificationSetting.php app/Notifications/OperationalNotice.php app/Services/Notifications/NotificationService.php app/Http/Controllers/Account/NotificationController.php app/Http/Controllers/Admin/System/MailDeliveryController.php app/Mail/OperationalMail.php app/Jobs/SendOperationalMail.php resources/views/account/notifications/index.blade.php resources/views/account/notifications/preferences.blade.php resources/views/admin/system/mail-delivery.blade.php resources/views/emails/operational.blade.php config/notifications.php config/queue.php'
FILES="$EXISTING $ADDED resources/css/admin/app.css"
MODE= STEP=preflight STAGE= BACKUP= APPLY_STARTED=0
if (( $# > 0 )); then MODE="$1"; fi
case "$MODE" in
 --check|--apply) (( $# == 1 )) || exit 2 ;;
 --rollback) (( $# == 2 )) || exit 2 ;;
 *) echo 'Usage: script --check | --apply | --rollback EXACT_BACKUP_PATH' >&2; exit 2 ;;
esac
[[ "$(id -u)" == 0 && -x "$PHP" && -d "$APP" && ! -L "$APP" &&
  -f "$APP/artisan" && -f "$APP/vendor/autoload.php" && -f "$APP/.env" ]] || {
 echo 'BLOCKED: Expected live Edumia application/PHP not found.' >&2; exit 1;
}
id "$OWNER" >/dev/null
for exe in gh git flock tar sha256sum; do command -v "$exe" >/dev/null; done
mkdir -p -m 0700 "$BACKUPS"
exec 9>"$BACKUPS/.lock"
flock -n 9 || { echo 'BLOCKED: Another release is active.' >&2; exit 1; }
cleanup() { [[ -z "$STAGE" || ! -d "$STAGE" ]] || rm -rf -- "$STAGE"; }
trap cleanup EXIT

guard_runtime() {
 (cd "$APP" && runuser -u "$OWNER" -- "$PHP" -r '
  require "vendor/autoload.php"; $app=require "bootstrap/app.php";
  $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
  $expected=base_path("bootstrap/cache/views");
  if(config("view.compiled")!==$expected || !is_dir($expected)
    || is_link($expected) || !is_writable($expected)) {
    fwrite(STDERR,"BLOCKED: Unsafe Blade cache".PHP_EOL);exit(1);
  }
  if((bool)config("notifications.transactional_mail_enabled",false)
    || (bool)config("notifications.smtp_test_enabled",false)) {
    fwrite(STDERR,"BLOCKED: Outbound mail/test gate must remain disabled".PHP_EOL);exit(1);
  }
  echo "EDUMIA_RUNTIME_GUARDS=PASS".PHP_EOL;
 ')
}
refresh_cache() {
 (cd "$APP" &&
  runuser -u "$OWNER" -- "$PHP" artisan route:clear &&
  runuser -u "$OWNER" -- "$PHP" artisan view:clear &&
  runuser -u "$OWNER" -- "$PHP" artisan view:cache)
}
verify_routes() {
 (cd "$APP" && runuser -u "$OWNER" -- "$PHP" artisan route:list --json) > "$STAGE/routes.json"
 "$PHP" -r '
  $routes=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);
  $seen=[];foreach($routes as $r)if(is_string($r["name"]??null))$seen[$r["name"]]=$r;
  foreach([
   "account.notifications.index"=>["GET","account/notifications"],
   "account.notifications.read-all"=>["PATCH","account/notifications/read-all"],
   "account.notifications.read"=>["PATCH","account/notifications/{notification}/read"],
   "account.notifications.preferences.edit"=>["GET","account/notifications/preferences"],
   "account.notifications.preferences.update"=>["PUT","account/notifications/preferences"],
   "admin.notifications.delivery"=>["GET","admin/notifications/delivery"],
   "admin.notifications.delivery.test"=>["POST","admin/notifications/delivery/test"]
  ] as $name=>[$method,$uri]){
   $r=$seen[$name]??null;
   if(!$r || !in_array($method,explode("|",(string)($r["method"]??"")),true)
    || trim((string)($r["uri"]??""),"/")!==$uri
    || (str_starts_with($name,"admin.") &&
       !in_array("role:admin",(array)($r["middleware"]??[]),true))){
    fwrite(STDERR,"BLOCKED: Missing notification route ".$name.PHP_EOL);exit(1);
   }
  }
  echo "EDUMIA_NOTIFICATION_ROUTES=PASS".PHP_EOL;
 ' "$STAGE/routes.json"
}
check_backup() {
 local item
 [[ -n "$BACKUP" && -d "$BACKUP" && ! -L "$BACKUP" &&
    -s "$BACKUP/originals.tar.gz" && -f "$BACKUP/originals.txt" &&
    -f "$BACKUP/created.txt" && -f "$BACKUP/asset-files.txt" &&
    -f "$BACKUP/asset-sha256.txt" ]] || return 1
 [[ "$(sha256sum "$BACKUP/originals.tar.gz" | cut -d' ' -f1)" == "$(cat "$BACKUP/originals.sha256")" ]] || return 1
 diff -u <(tar -tzf "$BACKUP/originals.tar.gz" | LC_ALL=C sort) \
    <(LC_ALL=C sort "$BACKUP/originals.txt") >/dev/null || return 1
 while IFS= read -r item; do
  [[ -z "$item" ]] && continue
  case " $FILES public/build/manifest.json " in *" $item "*) ;; *) return 1 ;; esac
 done < "$BACKUP/originals.txt"
 while IFS= read -r item; do
  [[ -z "$item" ]] && continue
  case " $ADDED " in *" $item "*) ;; *) return 1 ;; esac
 done < "$BACKUP/created.txt"
 while IFS= read -r item; do
  [[ -z "$item" ]] && continue
  [[ "$item" == public/build/assets/* && "$item" != *..* ]] || return 1
 done < "$BACKUP/asset-files.txt"
}
restore() {
 local item actual expected
 check_backup
 tar -xzf "$BACKUP/originals.tar.gz" -C "$APP"
 while IFS= read -r item; do [[ -z "$item" ]] || rm -f -- "$APP/$item"; done < "$BACKUP/created.txt"
 while IFS= read -r item; do
  [[ -z "$item" || ! -f "$APP/$item" ]] && continue
  expected="$(awk -v file="$item" '$2==file {print $1}' "$BACKUP/asset-sha256.txt")"
  actual="$(sha256sum "$APP/$item" | cut -d' ' -f1)"
  if [[ -n "$expected" && "$actual" == "$expected" ]]; then rm -f -- "$APP/$item"; fi
 done < "$BACKUP/asset-files.txt"
 refresh_cache
}
failure() {
 local status="$1"
 trap - ERR INT TERM
 if (( BASH_SUBSHELL > 0 )); then return "$status"; fi
 echo "FAILED: $STEP (exit $status)" >&2
 if (( APPLY_STARTED == 1 )); then
  APPLY_STARTED=0
  echo "AUTOMATIC_RESTORE_FROM=$BACKUP" >&2
  restore || echo 'RESTORE INCOMPLETE: Inspect protected backup before retry.' >&2
 fi
 exit "$status"
}
trap 'failure "$?"' ERR
trap 'failure 130' INT
trap 'failure 143' TERM

guard_runtime
if [[ "$MODE" == --rollback ]]; then
 BACKUP="$2"
 case "$BACKUP" in "$BACKUPS"/*) ;; *) echo 'BLOCKED: Unknown backup path.' >&2;exit 1;; esac
 check_backup
 [[ -f "$BACKUP/deployed.sha256" ]] || { echo 'BLOCKED: Incomplete release backup.' >&2;exit 1; }
 (cd "$APP" && sha256sum --quiet --check "$BACKUP/deployed.sha256") || {
  echo 'BLOCKED: Source/assets changed since release.' >&2;exit 1;
 }
 STAGE="$(mktemp -d /root/.edumia-notification-ui-rollback.XXXXXXXX)"
 restore
 echo 'ROLLBACK COMPLETE: Original source and Vite manifest restored.'
 exit 0
fi

echo '========== VERIFY APPROVED GITHUB AND CI =========='
gh auth status >/dev/null 2>&1
[[ -d "$SOURCE/.git" && -z "$(git -C "$SOURCE" status --porcelain)" ]] || {
 echo 'BLOCKED: Source checkout missing or dirty.' >&2;exit 1;
}
case "$(git -C "$SOURCE" remote get-url origin)" in
 *github.com*moyuradesign/edumia|*github.com*moyuradesign/edumia.git) ;;
 *) echo 'BLOCKED: Unexpected source repository.' >&2;exit 1;;
esac
git -C "$SOURCE" fetch -q origin main
[[ "$(git -C "$SOURCE" rev-parse HEAD)" == "$(git -C "$SOURCE" rev-parse origin/main)" ]] || {
 echo 'BLOCKED: Fast-forward your clean source checkout first.' >&2;exit 1;
}
git -C "$SOURCE" cat-file -e "$SHA^{commit}"
git -C "$SOURCE" cat-file -e "$PRIOR^{commit}"
git -C "$SOURCE" cat-file -e "$BASE^{commit}"
git -C "$SOURCE" merge-base --is-ancestor "$SHA" origin/main
for run in "$QUALITY_RUN" "$QA_RUN"; do
 payload="$(gh api "repos/moyuradesign/edumia/actions/runs/$run" --jq '[.head_sha,.status,.conclusion] | join("|")')"
 [[ "$payload" == "$SHA|completed|success" ]] || {
  echo "BLOCKED: Required CI failed or still running: $run" >&2;exit 1;
 }
 echo "APPROVED_CI=$run"
done

STEP='downloading exact tested build'
STAGE="$(mktemp -d /root/.edumia-notification-ui.XXXXXXXX)"
mkdir -p "$STAGE/source" "$STAGE/download" "$STAGE/snapshot"
git -C "$SOURCE" archive "$SHA" -- $FILES | tar -xf - -C "$STAGE/source"
gh run download "$QA_RUN" --repo "$REPO" --name edumia-integrated-qa --dir "$STAGE/download"
ARTIFACT="$STAGE/download/edumia-integrated-qa.tar.gz"
[[ -s "$ARTIFACT" ]] || { echo 'BLOCKED: CI archive not found.' >&2;exit 1; }
STEP='validating exact tested source and build'
tar -tzf "$ARTIFACT" > "$STAGE/list.txt"
if grep -E '(^/|(^|/)\.\.(/|$)|^\./|^\.env$|^installer/authorize.txt$)' "$STAGE/list.txt"; then
 echo 'BLOCKED: Unsafe CI archive contents.' >&2;exit 1
fi
grep -Fxq QA_BUILD.txt "$STAGE/list.txt"
grep -Fxq public/build/manifest.json "$STAGE/list.txt"
tar -xzf "$ARTIFACT" -C "$STAGE/snapshot" --wildcards QA_BUILD.txt 'public/build/*' $FILES
grep -Fxq "SOURCE_SHA=$SHA" "$STAGE/snapshot/QA_BUILD.txt"
for item in $FILES; do
 [[ -f "$STAGE/snapshot/$item" && ! -L "$STAGE/snapshot/$item" ]] || exit 1
 cmp -s "$STAGE/source/$item" "$STAGE/snapshot/$item" || {
  echo "BLOCKED: Artifact/source mismatch: $item" >&2;exit 1;
 }
 case "$item" in *.php) "$PHP" -l "$STAGE/source/$item" >/dev/null;; esac
done
"$PHP" -r '
 $root=$argv[1];$m=json_decode(file_get_contents("$root/manifest.json"),true,512,JSON_THROW_ON_ERROR);
 foreach(["resources/css/frontend/app.css","resources/css/admin/app.css",
  "resources/js/frontend/app.js","resources/js/admin/app.js"] as $src) {
  $asset=$m[$src]["file"]??null;
  if(!is_string($asset) || !preg_match("~^assets/[A-Za-z0-9._/-]+$~D",$asset)
    || !is_file("$root/$asset") || is_link("$root/$asset")) exit(1);
 }
 echo "CI_BUILD=VERIFIED".PHP_EOL;
' "$STAGE/snapshot/public/build"
[[ -f "$APP/public/build/manifest.json" && ! -L "$APP/public/build" &&
  ! -L "$APP/public/build/assets" && ! -L "$APP/public/build/manifest.json" ]] || {
 echo 'BLOCKED: Existing Vite build invalid.' >&2;exit 1;
}
echo '========== READ-ONLY PRODUCTION DRIFT =========='
: > "$STAGE/originals.txt"
: > "$STAGE/created.txt"
count=0
for item in $FILES; do
 target="$(git -C "$SOURCE" hash-object "$STAGE/source/$item")"
 if [[ -e "$APP/$item" || -L "$APP/$item" ]]; then
  [[ -f "$APP/$item" && ! -L "$APP/$item" ]] || {
   echo "BLOCKED: Unexpected file type: $item" >&2;exit 1;
  }
  echo "$item" >> "$STAGE/originals.txt"
  live="$(git -C "$SOURCE" hash-object "$APP/$item")"
  if [[ "$live" == "$target" ]]; then echo "MATCH: $item";continue;fi
  allowed=0
  old="$(git -C "$SOURCE" rev-parse "$PRIOR:$item" 2>/dev/null || true)"
  [[ "$old" == "$live" ]] && allowed=1
  case " $EXISTING " in *" $item "*)
   old="$(git -C "$SOURCE" rev-parse "$BASE:$item" 2>/dev/null || true)"
   [[ "$old" == "$live" ]] && allowed=1;;
  esac
  case "$item:$live" in
   app/Services/Notifications/NotificationService.php:9383b6ef7c25e63bb77d7ec7ce16d305d20830aa|\
   app/Http/Controllers/Admin/System/MailDeliveryController.php:73b889cd2330738639bfd8933f2becede8c95772|\
   resources/views/admin/system/mail-delivery.blade.php:b7af83ca1bf7a0e34a61ef5ff159d8619729f9ef|\
   config/notifications.php:01e88cd4820dae23a5903aab2121b5376aaf967c) allowed=1;;
  esac
  (( allowed == 1 )) || {
   echo "BLOCKED: Unknown production source drift: $item ($live)" >&2;exit 1;
  }
  echo "APPROVED_UPDATE: $item"
 else
  case " $ADDED " in *" $item "*) ;; *)
   echo "BLOCKED: Required file missing: $item" >&2;exit 1;;
  esac
  echo "$item" >> "$STAGE/created.txt"
  echo "APPROVED_NEW_FILE: $item"
 fi
 count=$((count+1))
done
echo public/build/manifest.json >> "$STAGE/originals.txt"
(cd "$STAGE/snapshot" && find public/build/assets -type f -print | LC_ALL=C sort) > "$STAGE/assets.txt"
[[ -s "$STAGE/assets.txt" ]] || { echo 'BLOCKED: No compiled assets.' >&2;exit 1; }
: > "$STAGE/new-assets.txt"
while IFS= read -r item; do
 [[ "$item" == public/build/assets/* && "$item" != *..* &&
   -f "$STAGE/snapshot/$item" && ! -L "$STAGE/snapshot/$item" ]] || exit 1
 if [[ -e "$APP/$item" || -L "$APP/$item" ]]; then
  [[ -f "$APP/$item" && ! -L "$APP/$item" ]] &&
   cmp -s "$APP/$item" "$STAGE/snapshot/$item" || {
   echo "BLOCKED: Existing compiled asset differs: $item" >&2;exit 1;
  }
 else echo "$item" >> "$STAGE/new-assets.txt";fi
done < "$STAGE/assets.txt"
echo "APPROVED_SOURCE_CHANGES=$count"
echo "NEW_HASHED_ASSETS=$(wc -l < "$STAGE/new-assets.txt")"
if [[ "$MODE" == --check ]]; then
 echo 'CHECK PASSED: Production unchanged.'
 exit 0
fi
STEP='backing up source and Vite manifest'
BACKUP="$(mktemp -d "$BACKUPS/$(date -u +%Y%m%dT%H%M%SZ)-XXXXXXXX")"
cp "$STAGE/originals.txt" "$BACKUP/originals.txt"
cp "$STAGE/created.txt" "$BACKUP/created.txt"
cp "$STAGE/new-assets.txt" "$BACKUP/asset-files.txt"
tar -czf "$BACKUP/originals.tar.gz" -C "$APP" -T "$BACKUP/originals.txt"
sha256sum "$BACKUP/originals.tar.gz" | cut -d' ' -f1 > "$BACKUP/originals.sha256"
(cd "$STAGE/snapshot" && sha256sum $(cat "$STAGE/new-assets.txt")) > "$BACKUP/asset-sha256.txt"
check_backup
echo "PROTECTED_BACKUP=$BACKUP"
APPLY_STARTED=1
STEP='installing new Vite hashed assets'
while IFS= read -r item; do
 [[ -z "$item" ]] && continue
 [[ ! -e "$APP/$item" && ! -L "$APP/$item" ]] || exit 1
 dir="$(dirname "$APP/$item")"
 [[ -d "$dir" && ! -L "$dir" ]] || exit 1
 tmp="$(mktemp "$dir/.edumia-ui.XXXXXXXX")"
 cp "$STAGE/snapshot/$item" "$tmp"
 chmod 0644 "$tmp"
 chown "$OWNER:$OWNER" "$tmp"
 mv -n "$tmp" "$APP/$item"
 [[ ! -e "$tmp" ]] || { rm -f "$tmp";echo 'BLOCKED: Concurrent asset write.' >&2;exit 1; }
done < "$STAGE/new-assets.txt"
STEP='installing approved Laravel source'
for item in $FILES; do
 if [[ -f "$APP/$item" ]] && cmp -s "$APP/$item" "$STAGE/source/$item";then continue;fi
 dir="$(dirname "$APP/$item")"
 [[ -d "$dir" && ! -L "$dir" ]] || { echo "BLOCKED: Unsafe source directory: $dir" >&2;exit 1; }
 tmp="$(mktemp "$dir/.edumia-ui.XXXXXXXX")"
 cp "$STAGE/source/$item" "$tmp"
 chmod 0644 "$tmp"
 if [[ -f "$APP/$item" ]];then chown --reference="$APP/$item" "$tmp"
 else chown "$OWNER:$OWNER" "$tmp";fi
 mv -f "$tmp" "$APP/$item"
done
STEP='switching compiled manifest'
tmp="$(mktemp "$APP/public/build/.edumia-manifest.XXXXXXXX")"
cp "$STAGE/snapshot/public/build/manifest.json" "$tmp"
chmod 0644 "$tmp"
chown --reference="$APP/public/build/manifest.json" "$tmp"
mv -f "$tmp" "$APP/public/build/manifest.json"
STEP='rebuilding Laravel caches'
refresh_cache
STEP='verifying notification route guards'
verify_routes
STEP='verifying installed content'
for item in $FILES;do cmp -s "$APP/$item" "$STAGE/source/$item";done
cmp -s "$APP/public/build/manifest.json" "$STAGE/snapshot/public/build/manifest.json"
while IFS= read -r item;do cmp -s "$APP/$item" "$STAGE/snapshot/$item";done < "$STAGE/assets.txt"
guard_runtime
STEP='recording deployed file hashes'
(cd "$APP" && sha256sum $FILES public/build/manifest.json $(cat "$STAGE/new-assets.txt")) > "$BACKUP/deployed.sha256"
APPLY_STARTED=0
echo "DEPLOYMENT_SUCCESS=YES"
echo "APPROVED_SOURCE_SHA=$SHA"
echo "ROLLBACK_BACKUP=$BACKUP"
echo 'MYSQL_ENV_UPLOADS_VENDOR_AND_OUTBOUND_MAIL=UNCHANGED'
echo 'Next: authenticated desktop and mobile visual acceptance.'
