# Notifications and Secure Email Delivery — Edumia

Reuse the existing notifications, notification_settings, jobs and
failed_jobs tables. Private account inbox and per-event delivery preferences
work for administrators, instructors and students. Essential security
notifications remain mandatory. No confidential support ticket text or
instructor review notes are copied into notifications.

Events: instructor decisions, support ticket creation and replies, ticket
status changes and password updates. Browser accounts can only mark their own
notifications as read. Delivery readiness is restricted to administrators.

## Secure SMTP and queue release gates

The configured mailer remains log-only by default and transactional email
is disabled. Credentials stay exclusively in protected environment files.
Never copy passwords to GitHub, dashboard inputs, audit rows or screenshots.

First configure and verify SMTP provider onboarding out-of-band. With
EDUMIA_SMTP_TEST_ENABLED=true only, an administrator can send a throttled
test exclusively to their own account. A successful SMTP transport response
is not proof of final inbox delivery. Confirm receipt separately.

Only after test receipt, sender/domain eligibility and a supervised database
queue worker are accepted, explicitly enable:
EDUMIA_MAIL_PROVIDER_VERIFIED=true
EDUMIA_TRANSACTIONAL_MAIL_ENABLED=true
QUEUE_CONNECTION=database

No script automatically enables these or starts a worker. A manual cron-safe
queue example for approved shared hosting is:

    php artisan queue:work database --queue=notifications --tries=3 --timeout=30 --stop-when-empty

Email jobs retry with bounded backoff. They record redacted transport result
and failure categories in existing audit_events. Exactly-once delivery is
not guaranteed if an SMTP send succeeds just before a worker crash.

## aaPanel production source sync

The guarded synchronizer updates seven existing and thirteen new PHP/Blade
config/view/service files. It protects the accepted Governance baseline,
blocks unknown changes, creates a backup including the newly created path
manifest, and verifies seven registered notification routes. It does not
modify .env, MySQL, media, web server or compiled frontend assets.

Read-only check, pinned to a successful GitHub Main CI commit:

    SOURCE=/root/edumia-github
    SHA=APPROVED_FULL_MAIN_SHA
    git -C "$SOURCE" fetch origin main
    git -C "$SOURCE" merge --ff-only origin/main
    bash "$SOURCE/scripts/aa-panel-notifications-sync.sh" --check --sha "$SHA"

Apply only after both quality and independent browser/customer installation
QA pass for the same commit and precheck says CHECK PASSED:

    bash "$SOURCE/scripts/aa-panel-notifications-sync.sh" --apply --sha "$SHA"
    bash "$SOURCE/scripts/aa-panel-notifications-sync.sh" --check --sha "$SHA"

Rollback needs the exact backup path printed by a completed deployment.
The script refuses to overwrite subsequent changes and removes only files
listed as newly added by the successful installation. Live-browser QA must
then confirm notification ownership, inbox updates, preferences, security
alerts, support/instructor events, admin SMTP gating, mobile/light/dark UI.
Outbound SMTP remains a separate future provider acceptance gate.
