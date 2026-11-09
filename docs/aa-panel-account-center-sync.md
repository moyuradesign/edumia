# Edumia shared account center: guarded aaPanel deployment

This milestone adds a private notification bell with unread count and preview, a reversible mark-unread action, direct account navigation in all three workspaces, and a safe recent settings-change timeline. The application code is pinned to GitHub revision `36dd125cb0beab887a7a434e5ab6f2a716b738b7`. Its feature tests are in `tests/Feature/SharedAccountNotificationCenterTest.php`.

Use `scripts/aa-panel-account-center-sync.sh` for a targeted, backup-protected release. The script checks pinned GitHub quality and integrated QA runs, downloads the matching tested `edumia-integrated-qa` build artifact and compares source bytes to Git, then checks each active production source file against the exact previously approved revision `1cc7c7419c8ec58dd9cffdabe151a36c80e43fe2`. The new notification bell partial is the only new source file. Only the Vite admin stylesheet manifest entry is switched; current frontend CSS and JavaScript entry points are preserved.

**aaPanel:** From a clean, up-to-date checkout at /root/edumia-github, run `bash scripts/aa-panel-account-center-sync.sh --check`; proceed to `--apply` only when both workflows pass and the preflight reports CHECK PASSED. A protected backup path is printed; `--rollback EXACT_BACKUP_PATH` refuses unknown changes and restores the prior source and manifest.

This installer does not migrate or edit MySQL, .env, payments, SMTP gates, worker services, uploads or vendor dependencies. It refuses unknown source drift rather than silently overwriting active customizations. After deployment, use logged-in administrator, instructor and student accounts to verify the bell, private preview, read/unread status, navigation, recent account activity and responsive light/dark styling on mobile and desktop. Physical device QA remains separate.

## Historical profile source reconciliation (November 9, 2026)

The read-only aaPanel preflight reported `app/Http/Controllers/Auth/ProfileController.php` with Git SHA `c1d40c832b16a359c5f0e8da87a4abdde1b764c3`. It matches the **exact file blob** from Edumia commit `5f901b67cc62a0e398a734146788976943c2bf2d` (the earlier account-preferences release). The script verifies that exact source through local pinned Git history before allowing the update; it does not accept unknown modifications. If another file differs unexpectedly, the preflight prints each unknown file and its Git hash before stopping. Production remains unchanged during `--check`.

## Historical account preferences view reconciliation (November 9, 2026)

The read-only aaPanel audit reported `resources/views/auth/preferences.blade.php` with blob `c17eebee44a77279f79806c96e65a9c9ec346ebb`. GitHub confirms the same exact file at historical Edumia commit `5f901b67cc62a0e398a734146788976943c2bf2d`. The installer now accepts only these verified historical bytes; any other difference still blocks deployment and is reported to the operator. The original controller and view were from the same older account-preferences milestone. No environment, database, or live account source files are changed by this correction.
