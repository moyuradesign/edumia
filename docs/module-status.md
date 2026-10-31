# Edumia implementation matrix and integrated QA

Updated: 2026-10-31. This is a **source-development snapshot**, not a statement of commercial or deployment readiness.

## Repository and deployment policy

- Sole development trunk: `main` at https://github.com/moyuradesign/edumia
- Installed website: https://edumia.tonsamith.com (separate older baseline; never auto-deploy)
- Each module belongs to a functional Laravel controller, request, service, model, route and Blade view path.
- Customer release is blocked until all advertised modules are functionally complete.
- All source files use clean descriptive names without version suffixes. Vite may hash generated build filenames.

For a complete, explicit list of missing and partially implemented features, see [Feature completion audit](feature-completion-audit.md).

## Integrated code modules

| Domain | Controller / service source | Views and routes | Current acceptance |
|---|---|---|---|
| Account authentication | `app/Http/Controllers/Auth/`, `app/Http/Requests/Auth/` | `resources/views/auth/`, `routes/auth.php` | Basic registration/login/password routes covered; email delivery and real-browser testing remain |
| Role-specific dashboards | `app/Http/Controllers/{Admin,Instructor,Student}/DashboardController.php` | `resources/views/dashboard/` | Basic dashboards covered |
| Course/lesson/quiz/certificates | `app/Http/Controllers/{Instructor,Student}/`, `app/Services/CourseCompletionService.php` | `routes/{instructor,student}.php` | Free enrollment, lesson progress, quiz grading and certificates covered; advanced exams, transcript and progress analytics still missing |
| Written assignments + gradebook | `app/Http/Controllers/{Instructor,Student}/AssignmentController.php`, `app/Services/Learning/AssignmentService.php` | `resources/views/{instructor,student}/assignments/` | Instructor authoring, learner submission, deadline, grading, feedback, ownership and certificate prerequisite regression tests |
| Saved courses + course reviews | `app/Http/Controllers/Student/{CourseWishlistController,CourseReviewController}.php`, `app/Services/Learning/CourseFeedbackService.php` | `resources/views/student/saved-courses.blade.php`, `resources/views/admin/courses/reviews/`, public course details | Saved-course privacy; enrolled reviews; pending/approved/rejected moderation; approved-only public display |
| Member governance | `app/Http/Controllers/Admin/Users/MemberController.php`, `app/Services/Identity/` | `routes/admin.php`, administrator dashboard | Role/status changes audited; granular role-permission management missing |
| Course categories | `app/Http/Controllers/Admin/Courses/CategoryController.php`, `app/Services/Learning/CategoryManagementService.php` | `resources/views/admin/courses/categories/` | CRUD, hierarchy cycle prevention and authorization tests |
| General settings | `app/Http/Controllers/Admin/System/GeneralSettingsController.php`, `app/Services/System/SettingsRepository.php` | `resources/views/admin/system/settings/` | General settings and registration enable/disable tested |
| Private media library | `app/Http/Controllers/Admin/Content/MediaController.php`, `app/Services/Content/MediaStorageService.php` | `resources/views/admin/content/media/` | Upload, download, deletion and role restrictions tested; advanced image derivatives and media assignment not yet implemented |
| Blog publishing | `app/Http/Controllers/{Admin/Content,Frontend}/BlogController.php`, `app/Services/Content/BlogPublishingService.php` | `resources/views/{admin/content/blog,frontend/default/blog}/` | Draft/published lifecycle, localized public pages and HTML escaping tested |
| Public contact and admin inbox | `app/Http/Controllers/{Frontend,Admin/Support}/ContactController.php`, `app/Services/Support/ContactManagementService.php` | `resources/views/{frontend/default/contact,admin/support/contacts}/` | Submission, validation, status review and isolation tested; reply email not implemented |
| Instructor applications | `app/Http/Controllers/{Student,Admin/Users}/InstructorApplicationController.php`, `app/Services/Identity/InstructorApplicationService.php` | Learner application and admin review screens | Transactional approval/rejection with role safeguards and audit events; external credential verification still missing |
| Free PDF ebook library | `app/Http/Controllers/{Instructor,Admin/Content,Frontend,Student}/Ebook*.php`, `app/Services/Content/EbookLibraryService.php` | Author, moderator, public catalogue and private learner library | Free PDF distribution and ownership/paid denial tested; paid sales remain blocked |
| Live class scheduling | `app/Http/Controllers/{Instructor,Student}/LiveClassController.php`, `app/Services/Learning/LiveClassService.php` | Course scheduling and private enrolled-learner timetable | External HTTPS meeting URLs, scheduling and access controls; conferencing API integration pending |
| Help Center | `app/Http/Controllers/{Admin/Content,Frontend}/KnowledgeBaseController.php`, `app/Models/KnowledgeBase*.php` | `resources/views/{admin/content/knowledge-base,frontend/default/help}/` | Drafts, public search, topics and administrator/staff permission gating; advanced search remains |
| Newsletter opt-in | `app/Services/Marketing/NewsletterSubscriptionService.php`, `app/Mail/NewsletterConfirmation.php` | Public footer form and signed confirmation/unsubscribe URLs | Opt-in/out lifecycle tested; SMTP provider and campaign sending not verified |
| Course forums | `app/Services/Learning/ForumDiscussionService.php`, `app/Models/Forum*.php` | `resources/views/{student/forums,admin/support/forums}/` | Learner-only discussions, replies, moderation and safe text; direct messages remain |
| Delegated role governance | `app/Services/Identity/RoleManagementService.php`, `app/Http/Middleware/EnsurePermission.php` | `resources/views/admin/users/roles/` | Limited whitelisted Help Center delegation; not yet a complete platform-wide permissions framework |
| Bootcamp cohorts (free) | `app/Services/Learning/BootcampService.php` | `resources/views/{instructor/bootcamps,admin/courses/bootcamps,student/bootcamps}/` | Cohorts, modules, sessions, free enrollment with capacity; paid and attendance incomplete |
| Learning reports | `app/Http/Controllers/{Admin/Reports,Instructor}/` | `resources/views/{admin/reports,instructor/reports}/` | Read-only enrollment/completion analytics; advanced/financial reports incomplete |
| Course checkout foundations | `app/Services/Commerce/CourseCheckoutService.php`, `app/Models/{Order,OrderItem}.php` | Learner order review/history/cancel; admin read-only order list | Transactional price snapshots, 30-minute quote lifetime, ownership, repeat-request deduplication and audit events. **Unpaid orders never grant access; payment integration is not implemented.** |
| Support tickets | `app/Http/Controllers/{Student/Support,Admin/Support}/TicketController.php`, `app/Services/Support/TicketService.php` | `resources/views/{student/support/tickets,admin/support/tickets}/` | Learner ownership, ticket reply, private internal notes and status tests added; verify latest CI before acceptance |
| Runtime diagnostics | `app/Console/Commands/HealthCheckCommand.php` | `php artisan edumia:health --json` | Safe configuration, DB, asset and installer-status checks |
| Asset build governance | `resources/css/{frontend,admin}/app.css`, `resources/js/{frontend,admin}/app.js` | Vite manifest, modular JS, CI | Exactly two first-party CSS source files and two JS entrypoints; production artifact paths are resolved via `@vite` |

## Folder conventions

```text
app/
  Console/Commands/
  Enums/
  Helpers/
  Http/
    Controllers/
      Admin/{Content,Courses,Support,System,Users}/
      Auth/
      Frontend/
      Instructor/
      Student/Support/
      Student/AssignmentController.php
      Student/{CourseWishlistController,CourseReviewController}.php
    Requests/{Admin,Auth,Frontend,Student}/
    Middleware/
  Models/
  Policies/
  Services/{Content,Identity,Learning,Support,System}/

resources/
  css/frontend/app.css
  css/admin/app.css
  js/frontend/app.js
  js/frontend/modules/
  js/admin/app.js
  js/admin/modules/
  views/admin/{content,courses,support,system}/
  views/auth/
  views/dashboard/
  views/frontend/default/
  views/instructor/
  views/student/support/tickets/
  views/student/assignments/
  views/instructor/assignments/
  views/admin/courses/reviews/

routes/{web,auth,admin,instructor,student,console}.php
tests/Feature/{Admin,...}/
docs/
```

No empty PHP files are created merely to make the tree look large.

## Outstanding full-platform modules

Order preparation and read-only ledger are implemented, but gateway transactions, coupon/redemption logic, payment providers/webhooks, payouts/refunds, paid ebooks, bootcamps, tutor bookings, live conferencing integrations, advanced grading, file attachments for assignments, complete CMS/appearance builder, language phrase management, notifications, advanced reports and CodeCanyon licensing **do not yet constitute complete functional modules**. Complete them with full vertical workflows and tests before advertising commercial readiness.

## Integrated QA on a disposable environment

1. Check out `main`. Confirm a **separate empty QA database**, never use `edumia_tonsamith_com` or its production credentials.
2. Run `composer install`, then `npm ci && npm run build`.
3. Create an isolated `.env` from `.env.example`, set the QA database, run `php artisan key:generate`.
4. Run `php artisan migrate --force` **only on the newly prepared QA database**.
5. Run `php scripts/verify-source-structure.php` and `php artisan test --fail-on-warning`.
6. Run `php artisan route:list`, then verify page styling, login, each role dashboard, categories, settings, media, blog, contact support tickets, written assignments, grading, wishlists and review moderation in a real browser.
7. Confirm file paths in `public/build/manifest.json` actually return HTTP 200 and correct CSS/JS MIME types. Tests alone do not demonstrate that a browser served those files.
8. Run `php artisan edumia:health --json`. Installer-removal checks will only pass on a finalized deployment, not on a source checkout containing a removable installer.

CodeCanyon release acceptance additionally needs real MySQL, queue/email, payment sandbox, upgrade rehearsal, data isolation, mobile accessibility, shared-hosting installer, documentation and final asset-license review.

Source and documentation are original Edumia work. Do not reuse Academy-LMS commercial source.
