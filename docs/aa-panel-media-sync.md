# Edumia private Media Library release and GitHub CLI synchronization

Scope: Secure private image previews; metadata and audit history in the
library; featured-image preview persistence after form validation; existing
blog featured-image audit values; real browser featured/inline picker
independence. No migrations or asset build are required for these changes.

The installed instructor release purposely omitted Media Library preview
and metadata-update routes. This release restores ONLY those two private,
permission-gated endpoints. Its six-file synchronizer reconstructs the
previously accepted routing hash before making any changes and preserves
the absent unrelated administrator redirect.

Run from aaPanel after successful PHP 8.3/8.5 and integrated QA:

    SOURCE=/root/edumia-github
    SHA=PINNED_40_CHARACTER_MAIN_SHA
    git -C "$SOURCE" fetch origin main
    git -C "$SOURCE" merge --ff-only origin/main
    bash "$SOURCE/scripts/aa-panel-media-sync.sh" --check --sha "$SHA"

The read-only preflight prints every file to update, checks the release-local
Blade cache, and blocks unknown live source differences. Only after CHECK
PASSED, run apply and recheck with the same SHA.

    bash "$SOURCE/scripts/aa-panel-media-sync.sh" --apply --sha "$SHA"
    bash "$SOURCE/scripts/aa-panel-media-sync.sh" --check --sha "$SHA"

The script creates a private six-file backup, clears Laravel routes, rebuilds
views as www, validates media endpoint method/name/URI via route-list JSON,
and records installed hashes. It never changes .env, database or uploads.
Rollback is available using the exact successful backup path:

    bash "$SOURCE/scripts/aa-panel-media-sync.sh" --rollback /root/edumia-media-sync-backups/EXACT_BACKUP

Check the authorized Media Library and Blog editor on desktop and mobile;
verify private image previews, metadata edit, audit activity, featured-image
picker separate from inline insertion and student access denial.
