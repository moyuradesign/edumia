# Edumia development plan

## Product goal

Ship a complete, professionally structured, original Laravel 13 LMS suitable for commercial review. Build features directly on `main`; keep the currently installed site on its existing baseline. The production site is **not** the development test bed.

The 96 existing migrations describe potential entities, not 96 finished features. No table or screen is counted complete until its controller, requests, authorization, services, templates, tests, navigation, seed data and documentation work together.

## Definition of complete for every module

1. Clear business capabilities and permissions for administrator, instructor, student and guest.
2. Database relationships, constraints, indexes, migrations and safe upgrade path.
3. Form Requests, thin controllers, authorized transactional services, model policies.
4. Functional forms, lists, detail screens, responsive layouts and accessible states.
5. Frontend and authenticated navigation, breadcrumbs, correct links and empty/loading/error states.
6. Positive/negative tests for validation, authorization, ownership and important state transitions.
7. Feature documentation, sample data where appropriate, and user-visible success/error feedback.
8. Zero orphaned routes, dead buttons, placeholders described as working features, or duplicate assets.

## Build phases

### Foundation — must complete first

- Standardize PSR-4 directory/file naming, route composition and base controllers.
- Consolidate shared UI tokens, responsive layout and light/dark behavior.
- Separate bundled CSS/JS by public/auth and dashboard concerns, with Vite manifest references and asset integrity checks.
- Add seeded permissions and role-driven navigation, with explicit administrator, instructor and student boundaries.
- Form Request validation; application services; repositories only if they provide real abstraction.
- Maintain committed `composer.lock` and `package-lock.json` and regenerate them through the tested lockfile workflow when dependencies are intentionally updated.
- Versionless source filenames; eliminate legacy/duplicate files and stale docs.
- CI now validates source structure, formatting, PHP syntax, Composer, Laravel routes, PHP feature tests and Vite asset integrity on PHP 8.3 and 8.5. Add real-browser, upgrade and security acceptance before release.

### Identity and account management

- Login, logout, registration, email verification, password reset, secure profile edits.
- Roles, permissions, access statuses and guarded administrative delegation.
- User lifecycle; instructor applications/approvals; account recovery.
- Session hardening, CSRF, throttling, email delivery and consistent account errors.

### Academic core

- Categories and hierarchical course catalog with search/filter and SEO.
- Instructor course workflow: draft, sections, lessons, rich content, previews, learning resources, revisions.
- Administrator review/approval/publish/unpublish with audit history.
- Student enrollment and ownership checks; prerequisites; progress tracking.
- Instructor and student dashboards backed by real data, not placeholder KPI text.

### Assessments and credentials

- Question banks and randomized quizzes, attempt rules, scoring and feedback.
- Assignments, secure submissions, instructor grading and gradebook.
- Course completion rules, unique certificates, public verification and revocation.
- Survey and ratings/reviews with abuse controls.

### Commerce and transactions

- Orders, cart, coupons, taxes, currencies, invoices, refunds.
- Payment gateways with signed webhooks, sandbox/live configuration, idempotency and reconciliation.
- Entitlements for paid courses, books, bootcamps and tutoring.
- Instructor earnings, payouts and auditable settlements.
- Avoid claiming paid flows work before a real payment test matrix passes.

### Extended learning modes

- Live class scheduling and attendance.
- Ebooks, bootcamps, tutor scheduling/booking and team training.
- Rescheduling, cancellation, access control and clear learner journeys.
- These modules are required for the advertised full-product scope, not satisfied by migrations alone.

### Content and platform operations

- Homepage/appearance builder, reusable sections, menus, footers, SEO.
- Media library with safe upload and access policies.
- Blog/newsletter, knowledge base, tickets, forums and moderation.
- Reports, analytics, event logs, export, translations and settings.
- Transactional email templates and scheduled tasks with hosting-friendly cron instructions.

### Distribution and documentation

- A removable installer with first-administrator creation and proper post-install lock.
- A **separate** licensing adapter for genuine purchase-code validation (not a renamed local token).
- Upgrade strategy, DB backups and non-destructive schema transitions.
- Demo-data import with rights-cleared media and safe import boundaries.
- Customer documentation: installation on shared hosting/aaPanel/cPanel, admin, instructor, student, troubleshooting, FAQs, configuration and updates.
- Commercial archive with reproducible dependencies, license notices, source and built assets, no private files.
- Final clean install, upgrade rehearsal, cross-browser QA, mobile/accessibility checks, security review and screenshot/documentation alignment.

## Release acceptance workflow

**During development:** automated tests run internally with each feature and pull request. User-facing production testing and server patching do not substitute for finishing the modules.

**Before release candidate:** complete module inventory with no missing advertised features; all mandatory workflow tests pass; static analysis/formatting pass; no unreviewed high-risk issues.

**At release candidate:** fresh empty MySQL install, multi-role browser journeys, functional payment sandbox transactions, all UI assets loaded, email and queues verified, upgrade rehearsal on copied customer data, clean uninstall of installer, archive inspection.

**Only then:** freeze a reviewed `main` commit, tag the release, build the customer package, and deploy with backup and rollback. Live hotfixes are reserved for production incidents rather than the normal build loop.

## Implementation tracking

| Workstream | Existing evidence | Remaining work |
|---|---|---|
| Basic authentication | Controllers and tests exist | Email/reset delivery, real browser/security acceptance, consistent architecture |
| Core courses | Basic controllers, models and migrations | Full editor, moderation lifecycle, media, revisions, authorization and UX |
| Quizzes/certificates | Basic services and tests | Question banks, grading policy, anti-abuse, PDF/output and operational audits |
| Financial flows | Schema only | Services, UI, providers, webhooks, invoices, refunds, payouts and tests |
| Instructor marketplace | Schema only for tutoring/bootcamps/ebooks | All workflows and interfaces |
| Admin modules | Basic dashboard and user role update | Full settings, media, CMS, users, analytics, finance and support |
| Localization | Eight-language helper labels and URL patterns | Translation manager, full coverage, date/number formatting, content localization |
| Asset build | Vite manifest integration, shared/admin bundles, pinned dependencies and CI checks | Real-browser asset verification and full responsive/accessibility acceptance |
| Installer | Fresh MySQL acceptance passed in QA and production | Customer packaging, licensing separation, installation/upgrade docs |
| Documentation | Prior README contradicts current source | Update README, manuals, changelog, guided installation |

## Repository policy

- `main`: sole development trunk while the product is pre-release.
- No routine feature branches or ongoing release branches.
- When all modules pass the release gates, use a reviewed immutable Git tag and reproducible production artifact.
- Use conventional commits and Git tags for versions, not versioned PHP/CSS/JS filenames.
- Every module must receive regression tests and reviewed documentation.
- No partial feature is represented as CodeCanyon-ready.

## Originality and reviewer readiness

Do not transplant or paraphrase purchased application code from Academy-LMS or other scripts. Architecture patterns and general Laravel conventions are permissible. Follow upstream license requirements for third-party packages and fonts/images. Envato approval remains discretionary and depends on implementation quality, originality, functionality, documentation and market fit.
