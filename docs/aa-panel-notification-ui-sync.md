# Notification UI aaPanel release

This is a **targeted** installation of notifications UI source and compiled Vite assets. The approved source commit is e74131110dddab4e5277a93d8a3acd5ada164885. GitHub quality workflow 42627826178 and integrated customer-installation workflow 42627826183 passed. The deployment script downloads the exact integrated-QA artifact and compares source bytes with the approved Git commit.

From a clean, up-to-date /root/edumia-github checkout run:
- bash scripts/aa-panel-notification-ui-sync.sh --check
- Only after CHECK PASSED: bash scripts/aa-panel-notification-ui-sync.sh --apply

It refuses unknown production source drift, symlinked inputs, nonmatching QA source and asset collisions. It preserves the existing Laravel installation, .env, MySQL, customer media, vendor and payment configuration. No SMTP activation or migrations occur. Older hashed assets are kept for rollback; only the Vite manifest and approved source are swapped.

The successful apply prints ROLLBACK_BACKUP. To undo a completed release with no intervening modifications, run bash scripts/aa-panel-notification-ui-sync.sh --rollback EXACT_ROLLBACK_BACKUP. Browser acceptance must still cover logged-in student/instructor/admin notification roles, mark/read, event filters, delivery history, light/dark and physical mobile. This engineering QA snapshot is not a customer-distribution release.
