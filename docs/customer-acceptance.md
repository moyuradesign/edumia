# Edumia customer installation and browser acceptance

This verification is **non-production**. Run only on a fresh, isolated installation and an empty, dedicated database. Do not point it at an existing site or database.

## Web hosting contract

- Set the web server document root to **public/**, not the repository root. Apache sites need `mod_rewrite` and the shipped `public/.htaccess`. Nginx sites should route missing paths to `/index.php?$query_string`, while serving existing static files directly.
- The PHP entry point redirects unconfigured root requests to `/installer`, and dispatches that path to `installer/index.php` outside the public document root.
- The owner creates a random, one-time `installer/authorize.txt` (minimum 16 characters) outside public/. This is an installation authorization code, not a purchase-code verifier.
- Confirm PHP 8.3–8.5, the PHP extensions, MySQL and empty database, installed Composer dependencies, a Vite build, and writable storage and cache directories.
- Complete the installation form over HTTPS (HTTP only for isolated localhost QA). The installer must run migrations, create exactly one active administrator, write `.env` and the installation lock, then refuse any second installation.
- After initial setup, **delete installer/** using the hosting file manager. Retain `.env` outside the public document root. Never publish database credentials or installer codes.

## Automated acceptance

The existing GitHub **Edumia integrated QA package** workflow provisions a temporary MySQL service and a separate copy of the application. It exercises the installer and smoke-checks responsive pages with headless browsers, then uploads redacted browser evidence. This is a fresh-install rehearsal, **not** a deployment or a test against a customer's database.

Acceptance covers: installer prerequisites, CSRF rejection, successful first administrator creation, one-time lock, user login, dashboard, public HTML/CSS/JS, language switching, dark/light mode, 390px and 768px mobile/tablet viewports, keyboard interaction, and basic desktop rendering. Tests use a standalone QA account and disposable database.

Headless mobile/tablet viewport simulation cannot replace a physical-device review. Separately verify current iPhone Safari, Android Chrome, tablet layout, keyboard-only focus, slow connections, email delivery, MySQL compatibility and shared-host permissions on a disposable staging host before release.

## Release gate

- A failed automated installer or browser test blocks the QA artifact.
- Never auto-activate payment gateways from the acceptance workflow.
- No job in this workflow pushes, uploads, or deploys to a live Edumia installation.
