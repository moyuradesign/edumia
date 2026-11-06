# Edumia — General Settings and Role Governance Hardening

## Scope
This milestone strengthens the existing Laravel 13 General Settings and
Roles & Permissions workspaces, not new modules. It leaves the delegated
permission allowlist unchanged: knowledge.manage, media.manage and
support.tickets.manage. Admin permissions are not delegated.

Settings: normalize submitted text; explicit unchecked registration form
state; preserve existing language/timezone validation; accessible form
feedback and paginated settings.audit history with actor attribution.

Roles: reserved-name protection, canonical role slugs, idempotent grants,
non-admin active-user restriction on new assignments, revocation from
suspended accounts, before/after role definition and membership audits.
The existing primary user roles never change on custom role assignment.
Permission matrix, member ID lookup and role change history reuse existing
admin styles. No migration or frontend asset compilation is needed.

## GitHub Main and production installation

After BOTH PHP 8.3/8.5 quality and integrated browser/independent customer
installation QA have passed for the exact GitHub Main SHA:

    SOURCE=/root/edumia-github
    SHA=PINNED_APPROVED_40_CHARACTER_GITHUB_MAIN_SHA
    git -C "$SOURCE" fetch origin main
    git -C "$SOURCE" merge --ff-only origin/main
    bash "$SOURCE/scripts/aa-panel-governance-sync.sh" --check --sha "$SHA"

The read-only check ensures the seven existing source files match the
accepted Media Library production baseline or the new release. Unknown
production changes are reported as DRIFT and block any write. Never bypass
DRIFT, modify the live files manually, or use --apply after a blocked check.

After CHECK PASSED:

    bash "$SOURCE/scripts/aa-panel-governance-sync.sh" --apply --sha "$SHA"
    bash "$SOURCE/scripts/aa-panel-governance-sync.sh" --check --sha "$SHA"

Apply archives the seven original files under
/root/edumia-governance-sync-backups, performs atomic file replacement,
refreshes release-local Blade cache as www, verifies six existing
settings/roles routes via Laravel route-list JSON, and records deployed
file hashes. No routes, migrations, .env, database, media, PHP-FPM, Nginx,
CSS, or JavaScript are replaced.

## Rollback

Only use the exact backup directory reported by a successful apply:

    bash /root/edumia-github/scripts/aa-panel-governance-sync.sh --rollback /root/edumia-governance-sync-backups/EXACT_BACKUP_NAME

Rollback checks the archive integrity and refuses to overwrite later
production customizations. A failed deploy attempts one source restore
and does not write a completed deployment manifest.

## Browser acceptance

With an administrator account, verify settings save and validation,
invalid email/timezone/locale feedback, registration toggle, and settings
audit history. Check roles matrix, searching eligible accounts, grant and
revoke of disposable test staff roles, and the actor/previous/current
values in role audit history. A student/staff member with media.manage
must remain denied General Settings, custom role management, and other
admin-only routes. Confirm desktop/mobile and light/dark rendering,
then retain protected backups and document acceptance.
