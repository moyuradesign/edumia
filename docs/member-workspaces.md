# Student and Instructor workspaces

## Routes and permissions

- `GET /account` — signed-in active learners, including instructors accessing their learner history.
- `GET /account/courses` — own enrollments with search and status filtering; owned courses only.
- `GET /instructor` — active instructors or administrators.
- `GET /instructor/courses` — courses owned by the signed-in instructor; draft, review and published filters.
- `GET /dashboard` — existing role-aware entrypoint, retained for compatibility.

The student, instructor and administrator workspaces share the premium responsive shell styles in `resources/css/admin/app.css`, with an accessible mobile menu, local module finder, color-theme toggle and account dropdown. Existing protected routes (course editing, ebooks, bootcamps, purchase records, learning and help) remain authoritative; new navigation links invoke these real endpoints.

## Error pages

`resources/views/errors/404.blade.php` is the Laravel application 404 view with a concise branded message and links to home, courses and (if signed in) dashboard. `public/404.html` is a static counterpart for Nginx-generated 404s.

To use the static page for Nginx errors on aaPanel, add this **inside the specific website server block** after a backup of the Nginx vhost:

```nginx
error_page 404 /404.html;
location = /404.html { internal; }
```

Keep the existing Laravel rewrite rule. Validate with `nginx -t` before reloading. The static Nginx fallback cannot include authenticated account links, by design.

## Release and tests

Run `php artisan test --filter=AccountWorkspacesTest` and the complete existing CI quality and package workflows. Verify both browser roles using dedicated nonproduction student/instructor accounts on a staging site; do not test production accounts or live payments. Physical-device and independent hosting sign-off remain separate.
