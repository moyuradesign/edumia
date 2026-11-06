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
user, then creates a private backup of five original files and records which
of the two new files were actually created. It updates nothing when checking.
Apply clears and rebuilds views as `www`, not root.

## Rollback

Copy the exact backup path printed by the apply command:

~~~bash
bash /root/edumia-github/scripts/aa-panel-instructor-sync.sh \
  --rollback /root/edumia-instructor-sync-backups/EXACT_BACKUP_NAME
~~~

Rollback restores only the five backed-up files and removes only files marked
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
