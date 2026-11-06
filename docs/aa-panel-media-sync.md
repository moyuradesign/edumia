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

## Paginated audit history

The existing `media.manage` authorization also controls a paginated
Media audit history section. This reports uploads, alternative text
changes and deleted file references with actor identity and timestamps.
Media and audit listings use separate `page` and `audit_page` parameters,
so reviewing history does not disturb the active media filters. No schema
changes are required.

## Audited live Media Library source reconciliation (2026-11-06)

The first production precheck for Media Library revision
`8d2f426a9e44b5bfc7ade862ba10d3adc79c48b5` safely stopped because two
installed files differ from the approved post-instructor GitHub baseline.
Their exact production blobs were retrieved by SHA from the Edumia GitHub
repository and compared with both baseline and target content:

- `app/Http/Controllers/Admin/Content/MediaController.php` —
  Git blob `9badae20a75dae9ef6e3c1022a9cee1bbbddfab9`. Its only differences
  from the baseline were removal of the description filter, authorized
  private image preview method, and metadata-update method.
- `resources/views/admin/content/media/index.blade.php` —
  Git blob `45f83e9538358e62447eaf3f7164d6660a56be1d`. Its only differences
  from the baseline were removal of description filtering, thumbnails and
  authorized metadata-editing controls, with the associated Clear filter
  condition adjusted.

The guarded synchronizer explicitly accepts **only these exact two old
production hashes** as additional pre-upgrade inputs. It still blocks
unknown controller or view edits and all unexpected differences in the other
four approved files. The new Media Library code restores only the intended
removed functions, while the route reconciler preserves the separate
production-specific admin redirect omission. All six original files are
backed up for rollback before any source is replaced.

Re-run `--check` using the new pinned commit and do not bypass a DRIFT
error or deploy without both PHP and integrated QA passing for that commit.
