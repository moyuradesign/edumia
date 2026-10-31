# Edumia quality gates

A CodeCanyon submission must not be based only on source code appearance, route HTTP 200 responses or successful migrations. This file is the acceptance contract for Edumia.

## Gate 1 — Source integrity and architecture

- All PHP follows PSR-12, valid PSR-4 namespaces and consistent naming (e.g., `Frontend`, `LoginController`, `Localization`).
- No duplicate controller logic, disconnected migration-only feature promises, copied application code or dead routes.
- All screens link to working business flows; ownership/permission checks are enforced server-side.
- Request classes own input validation; policies authorize actions; services own business operations and transactions.
- No production secret, token, customer data, absolute personal path or debug stack trace in distributions.
- PHP source passes syntax checks; formatter and static analysis run as part of CI.

## Gate 2 — Assets, UI and accessibility

- `npm ci && npm run build` succeeds using a committed `package-lock.json`.
- All Blade layouts use `@vite`; all manifest entries resolve to real compiled files.
- CSS and JS URLs return HTTP 200 with correct MIME types from an HTTP server, not only in-memory PHPUnit.
- Public homepage, catalogue, authentication and every role dashboard are styled and responsive.
- Interactive buttons, menus, forms, notifications, modal dialogs, theme and language switching work with keyboard and pointer input.
- Contrast, labeling, focus, validation feedback, mobile overflow and empty states receive visual review.
- Browser smoke tests cover Chromium, Firefox and WebKit where supported.

## Gate 3 — Identity and isolation

- New registration cannot elevate its role; role/permission changes are logged and authorized.
- An instructor cannot edit another instructor's course; a student cannot access instructor/admin actions.
- Verified and active-account rules are applied uniformly; suspended accounts lose protected access.
- Authentication, password reset, CSRF, session fixation prevention, throttling and email workflows are verified.
- Installer is one-time, server-side authorized and absent from finished packages' public surface.

## Gate 4 — Learning workflows

- Course draft to review to publication and unpublication transitions work.
- Section and lesson ordering, published revisions, student progress and resuming learning work.
- Quizzes enforce attempt and scoring rules, and certificates require defined completion; verification/revocation work.
- Assignments, uploads, grading, instructor feedback and learner notifications work end to end.
- Localized UI, search and SEO output include the eight supported languages or explicitly documented fallbacks.

## Gate 5 — Payments and commerce

- Prices, fees and payouts use currency-safe storage and audited calculations.
- Payment methods use gateway SDK/API correctly; webhook verification and idempotency are demonstrated.
- No paid enrollment without a confirmed entitlement; refunds/revocations adjust access consistently.
- Currencies, taxes, discounts, invoices and payout histories reconcile in test scenarios.
- Only supported payment providers are advertised. Clearly mark optional integration prerequisites.

## Gate 6 — Reproducible installation and upgrades

- Fresh install on a new MySQL database runs every migration and creates the initial administrator.
- Existing installations do not receive unintended destructive migrations or dropped user data.
- Shared hosting/cPanel/aaPanel instructions account for PHP, document root, permissions, cron, email, queues and SSL.
- Compiled assets and production Composer dependencies are included in the customer package with license notices.
- `.env.example` is clean; no `.env` or installer secret is included.
- Upgrade rehearsal uses a backed-up customer-like database and verifies no data loss.

## Gate 7 — Commercial delivery and reviewer readiness

- Honest feature list, original demo media, live demo, admin/instructor/student guides, installer guide and FAQ.
- Demonstration accounts contain non-sensitive sample content and resettable test data.
- Documentation screenshots reflect the exact shipped UI.
- Code, docs, demos and update packages are separately organized with correct third-party licenses.
- Security review, code review, browser acceptance and rollback rehearsal documented.
- Release candidate is frozen and reproducibly built; no direct production editing to complete missing features.

## Enforcement

Track each requirement in an acceptance report with pass/fail, evidence, test command and reviewer. A failed mandatory requirement blocks the release candidate. The development branch may contain incomplete modules, but `main` must not advertise them as complete.

CodeCanyon acceptance is decided by Envato reviewers, not by this checklist. References: https://help.author.envato.com/hc/en-us/articles/360000536823-Common-Rejection-Factors-for-Code-Items and https://help.author.envato.com/hc/en-us/articles/45774519899673-Quality-and-Technical-Requirements .
