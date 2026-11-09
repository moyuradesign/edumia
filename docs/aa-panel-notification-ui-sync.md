# Notification UI aaPanel release

This is a **targeted** installation of notifications UI source and compiled Vite assets. The approved source commit is e74131110dddab4e5277a93d8a3acd5ada164885. GitHub quality workflow 42627826178 and integrated customer-installation workflow 42627826183 passed. The deployment script downloads the exact integrated-QA artifact and compares source bytes with the approved Git commit.

From a clean, up-to-date /root/edumia-github checkout run:
- bash scripts/aa-panel-notification-ui-sync.sh --check
- Only after CHECK PASSED: bash scripts/aa-panel-notification-ui-sync.sh --apply

It refuses unknown production source drift, symlinked inputs, nonmatching QA source and asset collisions. It preserves the existing Laravel installation, .env, MySQL, customer media, vendor and payment configuration. No SMTP activation or migrations occur. Older hashed assets are kept for rollback; only the Vite manifest and approved source are swapped.

The successful apply prints ROLLBACK_BACKUP. To undo a completed release with no intervening modifications, run bash scripts/aa-panel-notification-ui-sync.sh --rollback EXACT_ROLLBACK_BACKUP. Browser acceptance must still cover logged-in student/instructor/admin notification roles, mark/read, event filters, delivery history, light/dark and physical mobile. This engineering QA snapshot is not a customer-distribution release.

Only the Vite manifest entry for resources/css/admin/app.css is updated; the existing frontend CSS and both JavaScript entries remain pinned to their previous live assets. The tested admin stylesheet is the only newly activated compiled asset.

## Known production CSS omission (November 9, 2026)

The read-only aaPanel comparison reported production `resources/css/admin/app.css` Git blob `1e40eab636ba84bfecf050b480957e743b7b6cb4`. It is precisely the prior source `c5b432b5f66cefb8a88ecc0112de0895ed096ee2` minus lines 1510–1543 (course status, media edit, and account preferences styles), with no inserted or altered lines. The installer now reproduces the exact omission from the pinned prior commit and accepts **only** that hash; any other deviation remains blocked. The upgrade restores these missing styles along with the notification UI CSS.
