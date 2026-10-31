# Edumia architecture

## Status and purpose

Edumia is being rebuilt as an original, commercially distributable Laravel 13 learning-management platform. This document describes the **target** architecture. Directory examples are not a claim that modules have been implemented.

- Production: `https://edumia.tonsamith.com` (installed baseline; do not use it for development).
- Installed baseline: commit `45eec2cef4e2deef19a9595b4da27c20d88d3153`, plus controlled installer/Nginx changes on the server; do not confuse this with the development HEAD.
- Development: `main` is the single development trunk. The installed website uses an explicit, separately built release and does not auto-deploy GitHub changes.
- Author/rights holder: Ton Samith.
- PHP 8.3–8.5; Laravel 13; MySQL 8; Blade; Vite.
- No copying of the purchased Academy-LMS application code.

## Audit of the current repository (2026-10-31)

The existing repository contains approximately 96 schema migrations and only 11 application models with 14 controllers. Implemented controller workflows cover basic registration/login, user roles, a course workflow, student enrollment, lessons, quizzes and certificates. Database tables for other product areas **do not constitute implemented modules**. The platform is not yet CodeCanyon-ready.

Observed structural and product risks:

1. Three Blade layouts hardcoded `build/assets/app.css` and `build/assets/app.js`. The actual production Vite build emits fingerprinted files, which explains the visually unstyled production pages. Replace hardcoded filenames with Laravel's `@vite` directive and add asset acceptance gates.
2. A single `routes/web.php` holds all role routes; routes, request validation and controllers need functional grouping.
3. `app/Helpers/Common_helper.php` and `app/Http/Controllers/frontend/` have inconsistent PHP naming conventions.
4. Role enforcement uses a single `role` field; the schema includes permissions but granular role/permission administration is not delivered.
5. Existing domain controllers combine HTTP validation, state transitions and persistence. Introduce Form Requests, policies, transactional application services and domain events.
6. Documentation is stale and even contradicts the deployed authentication and first-admin installer capabilities. Documentation must be generated/reviewed alongside features.
7. Composer/npm dependency lockfiles are absent from source control. Each approved distributable build must pin, audit and retain resolved dependencies.
8. Installer is a standalone removable directory. It has passed fresh-database QA, but licensing and purchase-code verification are **not** implemented; its current field is an authorization secret, not an Envato purchase code.
9. No release pipeline yet proves clean installation, upgrades, assets, authorizations, email, background jobs, content safety, refunds or cross-role isolation on a complete platform.

## Target Laravel folder conventions

Use standard Laravel PSR-4 namespaces, clean singular class names, grouped directories, thin controllers, and descriptive version-free filenames. Laravel timestamp-prefixed migration names are a framework convention and remain necessary.

```text
app/
  Enums/
  Events/
  Exceptions/
  Helpers/
    Localization.php
  Http/
    Controllers/
      Auth/
        LoginController.php
        RegisterController.php
        PasswordResetController.php
        EmailVerificationController.php
      Frontend/
        HomeController.php
        CourseController.php
        InstructorProfileController.php
        BlogController.php
        ContactController.php
      Admin/
        DashboardController.php
        Users/
          UserController.php
          RoleController.php
          PermissionController.php
        Courses/
          CourseController.php
          ApprovalController.php
          CategoryController.php
        Commerce/
          OrderController.php
          PaymentController.php
          RefundController.php
          PayoutController.php
        Appearance/
          HomepageController.php
          MenuController.php
          FooterController.php
        Content/
          BlogController.php
          PageController.php
          MediaController.php
        System/
          SettingController.php
          LanguageController.php
          AuditLogController.php
          UpdateController.php
      Instructor/
        DashboardController.php
        Courses/
          CourseController.php
          SectionController.php
          LessonController.php
          QuizController.php
          AssignmentController.php
        Learners/
          EnrollmentController.php
          GradebookController.php
      Student/
        DashboardController.php
        Learning/
          CourseController.php
          LessonController.php
          QuizController.php
          AssignmentController.php
        CertificateController.php
        PurchaseController.php
      Api/
        V1/
    Middleware/
    Requests/
      Auth/
      Admin/
      Instructor/
      Student/
    Resources/
  Models/
    Identity/
    Learning/
    Commerce/
    Content/
    Support/
  Notifications/
  Policies/
  Providers/
  Services/
    Identity/
    Learning/
    Commerce/
    Content/
    Support/
  Support/
bootstrap/
config/
database/
  factories/
  migrations/
  seeders/
docs/
  architecture.md
  development-plan.md
  quality-gates.md
installer/
  index.php
  assets/
lang/
  en/  km/  zh/  fr/  de/  ja/  ko/  vi/
public/
  index.php
  build/              # generated assets, never hand-edited
resources/
  css/
    app.css           # public + auth visual system
    admin.css         # authenticated workspace visual system
  js/
    app.js
    admin.js
    components/
  views/
    layouts/
    components/
    auth/
    frontend/
    admin/
    instructor/
    student/
    emails/
routes/
  web.php             # route file composition
  auth.php
  admin.php
  instructor.php
  student.php
  api.php
  console.php
tests/
  Feature/
    Auth/
    Frontend/
    Admin/
    Instructor/
    Student/
    Installer/
    Commerce/
  Unit/
    Services/
    Policies/
    Payments/
  Architecture/
scripts/
  verify-assets.php
```

Use module controllers for HTTP orchestration, request classes for authorization/validation, policy classes for ownership and permissions, domain services for transactions, model classes for relationships and casts, queues for asynchronous work, and named route groups with consistent URL prefixes. Avoid controller-level SQL business rules.

## Asset contract

- Use Laravel `@vite` and two purposeful bundles: shared public `app.css`/`app.js`, plus `admin.css`/`admin.js` for authentication and authenticated workspaces. These source filenames stay stable.
- Keep source filenames stable and descriptive. Vite **may** produce hashed output filenames for cache invalidation; these are generated artifacts and not version-named source files.
- Do not hardcode `public/build/assets/*` filenames in templates.
- A release must include `public/build/manifest.json` and the exact files it references. Verify asset HTTP 200, expected MIME types, styles, JavaScript behavior, mobile layout and light/dark mode.
- CI must build assets **before** running browser acceptance checks; do not rely solely on `withoutVite()` Laravel feature tests.
- Keep no unused CSS/JS copies or legacy/version-suffixed source files.

## Layer boundaries and safety requirements

- Web document root is `public/`; `.env`, installer authorization, `vendor/`, `storage/` and private files must never be web-served.
- Public API and admin entrypoints use authentication, permissions, CSRF protection, request validation and rate limits as appropriate.
- Money is handled in integer minor currency units or appropriately scaled DECIMAL with currency codes; never float prices. Enrollments for paid items require confirmed payment entitlement.
- Payment callbacks must validate signatures, enforce idempotency and reconcile transaction status.
- File uploads use private storage where needed, MIME/content validation, authorization and safe serving.
- Publishing, grades, certificates, payouts, refunds and role changes are auditable state transitions.
- First administrator creation belongs only in initial installer, and setup must lock and be removable.
- Secrets are never stored in Git or user-facing errors.
- Per-locale dictionaries are version-controlled, with English fallback and explicit translation coverage.

## Commercial packaging

Use clean source with vendor dependencies licensed and documented, compiled assets, SQL migrations, default configuration example, removable installer, changelog, user/admin manuals and a demo-data package of rights-cleared assets. End-user packaging must not contain `.env`, personal access credentials, debug logs, test tokens, QA databases, `.git`, or local machine paths.

Keep source, documentation, demo content and distributable release artifacts separate. Approval by CodeCanyon is subject to Envato review and cannot be promised by architecture alone.

## Reference standards

- Laravel 13 Vite: https://laravel.com/framework/docs/vite
- Laravel 13 deployment: https://laravel.com/framework/docs/deployment
- Envato common rejection factors: https://help.author.envato.com/hc/en-us/articles/360000536823-Common-Rejection-Factors-for-Code-Items
- Envato quality requirements: https://help.author.envato.com/hc/en-us/articles/45774519899673-Quality-and-Technical-Requirements
