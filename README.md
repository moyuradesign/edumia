# Edumia — Learning Management System

A Laravel 13 learning-management platform by **Ton Samith**.

**Status:** Active full-platform rebuild. The existing source is a functional foundation, **not** a complete commercial LMS or a submission-ready CodeCanyon package.

- Repository: https://github.com/moyuradesign/edumia
- Production baseline: https://edumia.tonsamith.com (preserved during rebuild)
- Development trunk: `main` (no routine feature branches)
- Runtime: PHP 8.3–8.5, Laravel 13, MySQL 8, Blade and Vite
- Brand UI: orange primary, blue secondary; light/dark; English + seven URL language variants

## Documentation

- [System architecture and source tree](docs/architecture.md)
- [Complete development plan and module delivery criteria](docs/development-plan.md)
- [CodeCanyon and production quality gates](docs/quality-gates.md)

## Currently implemented foundation

- Server-side registration and login with student as default role.
- Administrator/instructor/student access guards and separate role-specific dashboard controllers.
- Basic database-backed courses, sections, lessons and enrollment.
- Basic quiz submission, grading, progress and certificate records.
- Dedicated role/status management with validated requests, transactions and audit events.
- Reusable `edumia:health` command for safe installation and runtime diagnostics.
- Eight locale URL patterns with English fallback.
- Standalone removable installer that has passed isolated MySQL migration and first-admin acceptance.
- 96 original schema migrations plus a new audit-event migration, including many areas **not yet implemented in product controllers or UI**.

A schema table does not mean the corresponding marketplace, payment, bootcamp, tutoring, support or CMS feature exists. Refer to the development plan for the missing work.

## Current rebuild: asset contract

Three Blade layouts use Laravel `@vite(['resources/css/app.css', 'resources/js/app.js'])` instead of fixed output filenames. Production bundles are hashed by Vite. `public/build/` is generated and excluded from source control; it must be regenerated for each release.

Example developer setup in an **isolated** working directory:

```bash
composer install
npm ci
npm run build
php artisan test
```

These commands require a correctly configured local or QA `.env` and PHP database driver. The production installation must never be used as a development testing database. The new asset-integrity tests require a Vite build before running the feature suite.

The repository now tracks `composer.lock` and `package-lock.json`. Use `composer install` and `npm ci` for reproducible builds. GitHub Actions runs source, syntax, routing, Vite and feature tests against PHP 8.3 and PHP 8.5. These automated checks do not replace complete commercial acceptance.

## Source conventions

Use Laravel-standard `app/Http/Controllers/{Admin,Auth,Frontend,Instructor,Student}`, Form Requests, policies, services and views grouped by audience and module. Use clean descriptive source filenames without release suffixes. Laravel migration timestamps remain as required by the framework; compiled Vite asset hashes are generated output, not source versions.

The CSS/JS strategy is a shared public bundle and an additional authenticated workspace bundle, rather than dozens of versioned per-feature CSS files.

## Security and install

- Serve only `public/` as the web root.
- Never commit `.env`, real purchase codes, installer authorization secrets or vendor customer data.
- Initial setup requires an empty MySQL database and a removable, authorized installer.
- Retire the installer after setup; use separate migration/updater logic for subsequent releases.
- A displayed “Purchase Code” field is **not** an Envato API license verifier. Real purchase-code validation is a separate future licensing module.

## Commercial-release policy

Features must be complete, secured, documented and tested before being advertised. Release candidates must pass the architecture, assets, auth, learning, commerce, installer, upgrade, packaging and browser checks in [quality gates](docs/quality-gates.md). No promise of CodeCanyon acceptance is implied.

Code must be original, with third-party package licenses respected. The purchased Academy-LMS may inform high-level architecture comparisons only; its implementation source is not part of Edumia.
