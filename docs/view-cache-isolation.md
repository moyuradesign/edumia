# Edumia Blade view-cache isolation

## Problem

Older aaPanel releases linked or configured Laravel's `view.compiled` to a previous
release's `storage/framework/views`. A root-run `view:cache` subsequently
created unreadable PHP files. Under PHP-FPM this caused HTTP 500, including on
exception pages.

## Release-local source contract

`config/view.php` now sets:

```php
'compiled' => base_path('bootstrap/cache/views'),
```

The directory is tracked via `bootstrap/cache/views/.gitignore`. It must be
independently writable by the web PHP user and must **not** be a symlink.
`VIEW_COMPILED_PATH` is intentionally ignored by Edumia to prevent an old
absolute path leaking into a replacement release.

Shared media, sessions and logs remain in storage/ and are unaffected.

## Customer-installation acceptance

- Check that `bootstrap/cache/views` exists and is writable by the PHP web user.
- Run `php scripts/view-cache-preflight.php` as that user.
- Never run `php artisan view:cache` as root: root-only compiled files can break PHP-FPM.
- Clear stale `bootstrap/cache/config.php` through Laravel's `config:clear`
  under the web user before compiling views; then run `view:clear` and
  `view:cache` under the same user.
- Verify `config('view.compiled')` is the active release's
  `bootstrap/cache/views`, not an old release.
- Test an authenticated admin page plus an unauthenticated page after upgrade.
- Do not delete or recursively change ownership on shared storage, files or
  another release. Avoid changing .env, APP_DEBUG or database settings.

## Operator note

GitHub code changes alone cannot correct a running PHP-FPM worker with a stale
cached configuration. A separate, backup-backed aaPanel repair script checks
the active Nginx document root and effective PHP-FPM access before making
any production file changes. The operator should check its output first.

## Customer package protections

The build excludes generated compiled Blade and cached Laravel configuration PHP from both isolated QA installs and customer archives. Archive creation and independently received-archive checks reject these files, while retaining the tracked release-local `bootstrap/cache/views/.gitignore` directory marker.
