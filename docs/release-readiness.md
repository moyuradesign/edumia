# Edumia LMS — Release readiness and evidence register

Updated: November 4, 2026. This describes **development acceptance**, not permission to publish or process real-money payments.

## Automated baseline

For source commit 77b2749fcd7ad77a99151eca24aa1fc7ef3fcc90, both the Laravel quality workflow and the integrated QA package workflow completed successfully in GitHub Actions on November 4, 2026. The integrated job exercises a disposable PHP/MySQL installation, simulated browser/device viewports and a second clean archived-customer installation. These results are evidence for **automated QA only**.

The administrator-only route GET /admin/release-readiness provides read-only signals for **the installation serving that page**, a dated feature-workstream snapshot, and external release gates. It is not linked to real hosting panel data, GitHub live build status, physical hardware or processor merchant accounts. No administrator can mark unsupported evidence complete from this page.

## Required release gates

| Gate | Independent acceptance requirement | Baseline |
| --- | --- | --- |
| Code and CI | Quality tests, cross-browser suite and packaged-install jobs green on the exact frozen source SHA | PASS on prior baseline 77b2749f; re-run on each future release candidate |
| Runtime on candidate | PHP handler, key present, debug disabled, lock retained, installer removed, compiled assets, permissions and private document root | PENDING on actual host |
| Hosting H01–H12 | Real aaPanel and cPanel fresh installation, web PHP/HTTPS/assets, backup-backed upgrade, rollback and evidence | NOT TESTED |
| Device D01–D14 | Physical iPhone Safari, Android Chrome and tablet, screen readers, mobile admin journeys and connectivity/orientation checks | NOT TESTED |
| Learning and business features | Advertised end-to-end roles, course, assessment, content, instructor and administration functions with positive and negative tests | INCOMPLETE |
| Global commerce | Actual eligible merchant, signed sandbox callbacks, verified entitlements, refunds, chargebacks, tax invoices, currency and payout reconciliation | NOT VERIFIED; KEEP LIVE PAYMENTS DISABLED |

A reviewer must record source SHA, environment, date, independent test steps, PASS/FAIL/BLOCKED and redacted evidence location to close each actual-host/device row. A PHPUnit test or Playwright screenshot cannot close a physical hardware or customer-account gate.

## Module workstreams

The current release-readiness view documents development priorities without claiming these modules are complete:

- Courses and assessments: advanced exams, assignments with files, revisions and deeper analytics.
- Members, roles and instructors: expanded permission matrix, credentials and ownership checks.
- Media and appearance: reusable image selection, variants and complete page/locale editing.
- Payments, invoices and payouts: real eligible provider acceptance and reconciliation, taxes, refund/dispute and payout controls.
- Bootcamps, live classes and ebooks: attendance, provider integration, reader formats and paid-access tests.
- Tutor booking and team training: booking calendars, corporate seats and reports.
- Localization and notifications: complete translated phrases, native review, real SMTP, queue and delivery evidence.

Treat these as a dated source audit, not dynamically checked functionality.

## Evidence protocol

Use the documents shared-host-acceptance.md, device-acceptance.md, feature-completion-audit.md and quality-gates.md from the same source release. Store any signed-off evidence privately, never inside public/ or a customer ZIP. Exclude secrets, installation authorization, environment files, live credentials and customer information.

Never automatically approve a release because all local runtime checks pass. A failed or missing mandatory external result keeps release status OPEN.

**Deployment policy:** GitHub Main does not deploy to the installed website https://edumia.tonsamith.com. That installation and its live database are not test environments.
