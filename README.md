# Edumia — Learning Management System

**Version:** v0.0.1 (development foundation)  
**Author:** [Ton Samith](https://tonsamith.com)  
**Project website:** https://edumia.tonsamith.com  
**Framework:** Laravel 13.x (minimum v13.39.0), PHP 8.3–8.5  
**Frontend:** Blade, CSS3, vanilla JavaScript, Vite 8  
**License:** Proposed proprietary/commercial code by Ton Samith. Dependencies keep their original licenses.

Edumia is a multilingual learning management system designed for educational institutions, trainers, and independent educators. Its roadmap includes organized courses and lessons, enrollment, assessments, certificates, instructor and student tools, and administrative management. The currently implemented public catalogue is **sample preview content only**, not a deployed LMS backend.

## Existing capabilities

- Original public landing page, searchable preview catalogue and course detail routes.
- Eight language URL patterns; English default at `/` (`/en` redirects to `/`).
- Chinese `/zh`, French `/fr`, German `/de`, Japanese `/ja`, Khmer `/km`, Korean `/ko`, Vietnamese `/vi`.
- Light and dark color schemes, with accessible orange primary, blue secondary and contrasting text.
- Single CSS and JavaScript source entries, preserving existing Laravel application source paths.
- Standalone, removable installer in the **root-level `installer/` folder**, accessible at `/installer` through `public/index.php` before Laravel boots.

## Independent installer: PHP 8.3+ and shared hosting

The installer is **not** a Laravel controller and can render its prerequisites before the framework is installed. It uses pure PHP and PDO with no CDN, third-party scripts or Composer runtime dependency for its own UI. To migrate the database it loads the application's installed Composer packages, so `vendor/` must be ready first.

1. Upload this project to hosting. Set the web document root to the **`public/` directory**. Never expose the project root to the web.
2. Run `composer install --no-dev --optimize-autoloader` using PHP 8.3–8.5 and the package manifest. For Codecanyon customers without Composer on hosting, the author must provide a separately built, license-compliant production ZIP containing the actual resolved `vendor/` files. The current source ZIP does **not** include Composer dependencies.
3. Build assets with Node.js 20.19+ and `npm install && npm run build`, or use the included original-source prebuilt CSS/JS manifest as a provisional fallback. The manifest is compatible with Laravel's `@vite` directive; a real Vite build is recommended for production.
4. Create a **new empty MySQL database** and user with permissions to create tables.
5. Using File Manager, create `installer/authorize.txt` in the private project root. Set its contents to a unique, random token of at least 16 characters; e.g. a 32-byte random hex string generated privately. **Never ship or commit this token.** Restrict file permissions where supported (0600).
6. Open `https://your-domain.example/installer`. Check prerequisites; enter HTTPS site URL, DB connection, timezone and the authorization token. Check the consent box, then select Install Edumia.
7. Setup checks the new database connection, creates a secure `.env` with a random 32-byte `APP_KEY`, invokes `artisan migrate --force` through Laravel's console kernel, then writes `storage/app/edumia-installed.lock`. After the installation marker or `.env` exists, installer requests return 404.
8. **Delete the entire `installer/` folder** after success. The public entry point safely falls through to Laravel if that folder is gone. Set `APP_DEBUG=false` and HTTPS.

**Important:** This is an initial-install workflow, not an upgrade manager or a database-backup tool. It deliberately refuses to overwrite an existing `.env` or run against a database containing a `migrations` table. A failed migration can leave partial database changes; restore an empty database before retrying. There is **no administrator account creation** yet because administrator authentication and persistence are not part of v0.0.1. Do not claim production-ready student or instructor operations.

## Security and deployment

- Root-level installer is not directly exposed by the web server; only the `/installer` public entry-point bridge is reachable.
- Install authorization token must be created manually by the site owner. Session CSRF protection, output escaping, frame denial, no-store headers, minimal CSP, HTTPS URL checks and an installation marker are implemented.
- The installer locks when an existing `.env` or installed marker is detected; it does not modify installed sites.
- Only grant database permissions on a new isolated database. Keep `.env` and the authorization token outside `public/` and Git.
- Laravel dependencies and Vite packages are not bundled into the source repository. Versions are **constraints**, not proof that packages were downloaded or runtime-tested on hosting. Resolve and audit a production lockfile before distributing a customer installation package.
- PHP 8.3+ does not mean unlimited future PHP versions: Laravel 13 officially supports PHP **8.3, 8.4 and 8.5** as of October 2026.

## Dependency sources and license references

| Library | Requested Composer/npm constraint | Upstream source | License |
|---|---|---|---|
| Laravel Framework | `laravel/framework ^13.39` | https://github.com/laravel/framework | MIT |
| Laravel Tinker | `laravel/tinker ^3.0` | https://github.com/laravel/tinker | MIT |
| FakerPHP Faker (dev) | `fakerphp/faker ^1.23` | https://github.com/FakerPHP/Faker | MIT |
| Laravel Pint (dev) | `laravel/pint ^1.27` | https://github.com/laravel/pint | MIT |
| Mockery (dev) | `mockery/mockery ^1.6` | https://github.com/mockery/mockery | BSD-3-Clause |
| Collision (dev) | `nunomaduro/collision ^8.6` | https://github.com/nunomaduro/collision | MIT |
| PHPUnit (dev) | `phpunit/phpunit ^12.5.12` | https://github.com/sebastianbergmann/phpunit | BSD-3-Clause |
| Vite (build) | `vite ^8.3.5` | https://github.com/vitejs/vite | MIT |
| Laravel Vite plugin (build) | `laravel-vite-plugin ^3.2.0` | https://github.com/laravel/vite-plugin | MIT |

Official docs: https://laravel.com/docs/13.x/releases · https://laravel.com/docs/13.x/upgrade · https://laravel.com/docs/13.x/vite · https://phpunit.de/supported-versions.html

Third-party package licenses and notices must remain intact after installing the dependencies. No third-party application source, vendor code or unknown-rights images have been copied into this repository. Only Edumia-authored code and the existing directory paths were retained. Third-party dependencies are referenced through their package managers and official source repositories.

## Validation status

Checked: PHP syntax, JSON manifests, expected installer routing and lock behavior, branding scan, CSS/JS sources and ZIP integrity. The sandbox environment lacks Composer plus required PHP extensions and cannot download dependencies, so actual `composer install`, PHPUnit, database migration and production Vite compilation remain **unverified**. The included prebuilt fallback assets are copies of Edumia-authored source assets with a generated Vite-compatible manifest, **not** an npm production build.

## Development roadmap

Database-backed courses, users, authentication, authorization, instructor tools, enrollment, quizzes, certificate issuance and verification, audit trails, accessibility QA, migrations and deployment acceptance remain future milestones.

## Edumia database schema (October 30, 2026)

`database/migrations/` contains original Laravel 13 schema definitions reauthored
from the supplied migration *table names*, not copied third-party PHP code.
Every migration has the October 30, 2026 date prefix. Dependencies are
ordered so foreign-key targets exist before referencing tables.

The supplied archive contained 126 PHP files but included 31 duplicate table
creations, plus a custom `migrations` tracking-table creation. Laravel manages
that tracking table automatically; Edumia does not create it separately.
Legacy table spellings were normalized:

- `addtocarts` is replaced by `cart_items`.
- `watche_durations` and `watche_histories` are replaced by `watch_durations` and `watch_histories`.
- `knowledge_base_topicks` is replaced by `knowledge_base_topics`.
- New Laravel runtime tables `cache`, `cache_locks`, `jobs`, `job_batches`, and `sessions` support optional file/database service configuration.

**Important:** These are fresh-install migrations only. Never rename already
executed migrations in a deployed installation. Existing databases require
separate additive migrations and verified data transformations. No live database
was modified as part of this source update.

For installation on an empty MySQL database with the existing root installer,
confirm `vendor/autoload.php` is installed and the application `storage/` and
`bootstrap/cache/` are writable. Then run `php artisan migrate --force` from
the application root as part of the installation acceptance test.

The database definitions establish data structures; complete LMS business
logic, admin workflows, authentication and user management require their own
implementation and testing before deployment.

**Installer safety check:** The standalone installer requires a completely empty
MySQL database before executing the fresh-install migrations. It refuses a
database containing any pre-existing table, rather than checking only Laravel's
`migrations` ledger.

The legacy Google-auth alter-table migration is folded into the fresh `users`
schema as an optional, uniquely indexed `google_id`. OAuth client secrets are
**not** seeded into migration files; configure them securely at runtime.
