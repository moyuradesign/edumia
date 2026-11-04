# Edumia shared dashboard layout, distinct audiences and global 404

## Audiences

All authenticated panels use `layouts.panel`, the shared CSS in `resources/css/admin/app.css`, the existing interactive navigation controller, light/dark mode, topbar and responsive layout. **The URL's route audience selects the navigation**:

- `/dashboard` or `/admin/*` for an active administrator: unchanged Admin Console layout, existing Admin views, permission checks and modules.
- `/account`, `/account/courses` and `/account/certificates`: Student Account sidebar and topbar, including when an administrator or instructor opens their own learning account.
- `/instructor/*`: Instructor Studio sidebar and topbar, including an administrator explicitly visiting an instructor page. Only the current teacher's courses and enrollments are ever listed.
- Other member pages use the student's navigation or the instructor's navigation based on the authenticated account role. A student cannot access the instructor routes. Neither member sidebar has Admin navigation.

### Student

The student workspace links to real course enrollments and lesson progress; saved courses; ebook library and private download permissions; bootcamp enrollments; verified order history; issued, nonrevoked certificates; preferences; password security; support tickets; instructor applications for eligible students. There are no invented navigation links.

### Instructor

The instructor workspace links to owned course drafts/editing/approval flow, courses, assigned student enrollment directory (without student email addresses), ebooks, bootcamps, learning reports, verified-earnings information, support and account preferences. Editing and grading remain subject to the existing controller and model authorization checks. Student records are filtered to courses owned by the signed-in instructor, including when an administrator opens the instructor workspace.

### Global 404

The Laravel `resources/views/errors/404.blade.php` now extends `layouts.default`, preserving the existing public global header (search, categories, cart and account), footer, CSS, theme and responsive navigation. It shows only a short page-not-found message and working links.

The static `public/404.html` is still only a web-server fallback. A Nginx-generated error does not render Blade, and a Laravel error cannot dynamically render if the server never forwards the request to `public/index.php`. Keep Laravel `try_files $uri $uri/ /index.php?$query_string`; configure Nginx's custom `error_page` separately if necessary, with a verified vhost backup.

## Deployment

Do **not** unzip over a running aaPanel release. Create a new release, copy the old `.env` without changing its `APP_KEY`, preserve uploaded storage and the installation lock, keep the installer removed, ensure both the PHP-FPM user and Nginx can read the new release (including `bootstrap/cache/packages.php`), validate the new artifact, then perform a backed-up Nginx cutover. Do not assume database migrations are required and do not enable live payments.

Automated tests cover route selection, administrator layout isolation, student/instructor authorization and scoped records, certificates, and public 404 rendering. Physical-device and independent customer aaPanel acceptance remain open.
