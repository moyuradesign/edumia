# Edumia — Instructor Management and aaPanel synchronization

## Release scope

The instructor release upgrades the existing application queue, reviewer
attribution, instructor directory and status changes. It also adds a guarded
transfer for courses that are in draft/pending, have never been published,
and have no enrollments or order items. Transfers to suspended accounts or
non-instructors are refused and all changes are audited.

No database migrations or new CSS/JS build are required.

## GitHub CLI workflow

Run from aaPanel with GitHub CLI authenticated. This script uses
`/root/edumia-github` as its separate source checkout and updates **only**
the seven instructor files on the approved list. It is not a complete
`git pull` of the production website.

~~~bash
SOURCE=/root/edumia-github
SHA=PINNED_40_CHARACTER_GITHUB_MAIN_SHA
git -C "$SOURCE" fetch origin main
git -C "$SOURCE" merge --ff-only origin/main

bash "$SOURCE/scripts/aa-panel-instructor-sync.sh" --check --sha "$SHA"
# Only when GitHub QA succeeds and --check reports CHECK PASSED:
bash "$SOURCE/scripts/aa-panel-instructor-sync.sh" --apply --sha "$SHA"
bash "$SOURCE/scripts/aa-panel-instructor-sync.sh" --check --sha "$SHA"
~~~

The sync rejects live files differing from the approved pre-upgrade baseline.
It validates effective release-local Blade cache permissions as the PHP-FPM
user, then creates a private backup of four original files and records which
of the three new files were actually created. It updates nothing when checking.
Apply clears and rebuilds views as `www`, not root.

## Rollback

Copy the exact backup path printed by the apply command:

~~~bash
bash /root/edumia-github/scripts/aa-panel-instructor-sync.sh \
  --rollback /root/edumia-instructor-sync-backups/EXACT_BACKUP_NAME
~~~

Rollback restores only the four backed-up files and removes only files marked
created by that apply. A failed apply also attempts to restore source files.
It never touches database tables, `.env`, customer payments, accounts,
shared storage, uploaded media, or the active release symlink.

## Acceptance

As an admin, test reviewer and status filters on
`/admin/instructor-applications`, confirm reviewer name, ID and recorded note;
test suspension and restoration with a required reason on
`/admin/instructors`; open instructor assignments and reassign an
unenrolled, unpaid, unpublished draft to another active instructor.
Check that published/enrolled/ordered courses cannot be reassigned.
Confirm `/dashboard`, `/instructor`, `/account`, and
`/admin/courses` remain healthy.

Do not use this transfer control for published, purchased, or learner-
enrolled courses. Those require a separate financial/learner migration review.

## Route cache refresh

The sync clears Laravel's route cache as `www` after a successful file swap
and again after rollback, so new instructor assignment and status endpoints
are not hidden by a previous release's cached route table. It also checks
that the new assignment route is visible before reporting deployment success.
The read-only `--check` mode never clears routes or Blade caches.

## Existing production directory reconciliation (2026-11-06)

The installed release can lack `resources/views/admin/users/instructors.blade.php`
even though that template exists at the approved GitHub baseline. The live
`--check` reported this path missing. The synchronizer now explicitly treats
**only this instructor-directory template** as a potentially created file:
if missing, the upgrade creates it; if already identical to the pinned version,
it leaves it unchanged; if a different live version exists, it **blocks** rather
than overwriting it. The protected backup still includes the four required
original files, and the `created.txt` rollback manifest records the directory
template alongside the two new instructor features, removing only files the
upgrade actually created.

If another required file is missing or differs from the approved baseline,
`--check` stops without writing to the production application. Do not copy
unrelated project files to bypass that guard.
