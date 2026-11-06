# Edumia — GitHub CLI to aaPanel Course Management sync

## Purpose and scope

The pinned GitHub Main commit is the **source of truth**. Production is a separate
installed Laravel application and must not be replaced by a raw git checkout.
The safe sync entrypoint is scripts/aa-panel-course-sync.sh. This first milestone
updates ONLY the Admin Course Management controller and view. It preserves
all installed secrets, customer data, uploads, dependencies, and working
platform modules. Do not use gh repo sync (which syncs remote repositories)
as a substitute for a production installer.

## One-time server setup

The server must have Git, GitHub CLI authenticated with repository access,
PHP 8.5, the www PHP-FPM Unix user, and release-local view cache hardening
already installed.

~~~bash
gh auth status
gh repo clone moyuradesign/edumia /root/edumia-github -- --branch main
~~~

If the checkout already exists, do not delete it. The installer fetches
origin/main without changing local working files.

## Check the pinned feature release

After PHP and integrated QA pass, obtain the full pinned commit SHA from
GitHub Main. From the aaPanel terminal:

~~~bash
SHA="REPLACE_WITH_FULL_40_CHARACTER_MAIN_COMMIT"
bash /root/edumia-github/scripts/aa-panel-course-sync.sh --check --sha "$SHA"
~~~

Read the paths and output. Only the Admin Course Management controller and
the matching view should be proposed for update.

## Apply and verify

~~~bash
bash /root/edumia-github/scripts/aa-panel-course-sync.sh --apply --sha "$SHA"
bash /root/edumia-github/scripts/aa-panel-course-sync.sh --check --sha "$SHA"
~~~

The installer backs up the previous files under
/root/edumia-course-sync-backups/ and compiles views as www, never as root.
Its error trap attempts an automatic source restore if an apply step fails.

## Roll back only if necessary

Copy the backup path printed by the successful --apply output:

~~~bash
bash /root/edumia-github/scripts/aa-panel-course-sync.sh \
  --rollback /root/edumia-course-sync-backups/EXACT_BACKUP_DIRECTORY
~~~

This restores only the two saved code files and rebuilds Blade views; it
does not roll back databases or modify user data.

## Acceptance

Open /dashboard, /admin/courses, /admin/courses?status=pending, and
/admin/instructor-applications. Check category, lesson-readiness and sorting
filters in desktop and mobile views, with published/pending/draft counts
matching the active search context. Confirm the Admin and Instructor
workspaces still load and that no 500 errors appear.

## Future milestones

Extend the script's explicit file allowlist only after feature-specific
dependency, migration and rollback reviews. Do not blindly copy every
file from Main into production, since Main can contain features that have
not yet passed live upgrade acceptance.
