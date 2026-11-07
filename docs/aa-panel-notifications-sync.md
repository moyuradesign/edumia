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
EDUMIA_SMTP_TEST_ENABLED=true and EDUMIA_SMTP_TEST_RECIPIENT set to an
explicitly approved sandbox recipient, an administrator may send a throttled
test only if their authenticated email matches that exact recipient.
Without the recipient allowlist, the test endpoint remains disabled.
Never use an unrestricted production recipient list or live mail credentials
for sandbox verification. A successful SMTP transport response
is not proof of final inbox delivery. Confirm receipt separately.

Only after test receipt, sender/domain eligibility and a supervised database
queue worker are accepted, explicitly enable:
EDUMIA_MAIL_PROVIDER_VERIFIED=true
EDUMIA_TRANSACTIONAL_MAIL_ENABLED=true
EDUMIA_QUEUE_WORKER_VERIFIED=true
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

## Queue operations acceptance — staging first

Keep `EDUMIA_TRANSACTIONAL_MAIL_ENABLED=false` and
`EDUMIA_QUEUE_WORKER_VERIFIED=false` until verified independently.
The admin dashboard provides read-only pending and failed *notification*
job counts. Counts alone do not establish worker liveness.

For aaPanel, configure an unprivileged process supervisor to launch the
release's PHP binary with the application's working directory and:

    php artisan queue:work database --queue=notifications --sleep=3 --tries=3 --timeout=30 --max-time=3600

Use automatic restart and environment-specific stdout/stderr log rotation.
Set database `retry_after` greater than the job timeout (current default:
120 seconds vs. 30 seconds). Run only one notification worker during
initial acceptance. Restart workers after every approved code deployment.
Do not run the worker as root or embed credentials in process arguments.

Shared hosting that allows cron but not persistent processes can execute
the following command every minute, under the hosting account and from
the application root (replace PHP with the host's approved PHP binary):

    php artisan queue:work database --queue=notifications --sleep=0 --tries=3 --timeout=30 --stop-when-empty --max-jobs=20

Prevent overlapping cron invocations with a host-supported exclusive lock
(e.g. `flock -n` where available), or explicitly confirm that the queue
driver prevents overlapping job claims on that host. If background
processing is prohibited, do not enable transactional mail.

Verify in staging: one test event queues, one worker processes it,
the audit reports `notifications.mail_accepted`, a separate recipient
confirms inbox receipt, and failed attempts appear in `failed_jobs`.
Inspect failures with `php artisan queue:failed`; replay only known-safe
jobs with `php artisan queue:retry <id>` after confirming the cause.
Never run `queue:retry all` blindly or paste exception payloads into
support chats. The existing audit distinguishes queued, SMTP-accepted,
and terminal failure states; SMTP acceptance does not prove inbox delivery.

Only after all of the above and verified sender/domain eligibility may an
operator set `EDUMIA_QUEUE_WORKER_VERIFIED=true` alongside the existing
provider and transactional-mail gates. Revert the transactional gate to
false if worker supervision, provider verification, or mail reliability
degrades. All environment changes remain an operator action, not a
dashboard action. This milestone does not authorize production deployment
or real-world outbound messages.

### Sandbox recipient approval check

Set `EDUMIA_SMTP_TEST_RECIPIENT` to the exact email of the signed-in
test administrator *only after* confirming it is an approved recipient
in the sandbox mail provider. The UI never accepts a recipient address
from form submissions. Configure `EDUMIA_SMTP_TEST_ENABLED=true` only for
a controlled operator test; keep `EDUMIA_TRANSACTIONAL_MAIL_ENABLED=false`.
Use the sandbox provider's inbox to confirm receipt and inspect provider
logs without exposing credentials. Remove the test gate after acceptance.
