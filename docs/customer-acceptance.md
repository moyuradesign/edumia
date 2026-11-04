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

GitHub's **Edumia integrated QA package** workflow installs a disposable copy with a temporary MySQL service, then runs Chromium, Firefox and WebKit browser checks. A dependent, separate-runner job downloads the generated QA archive, verifies that installation secrets are absent, and performs a second fresh HTTP/MySQL installation using PHP 8.3. Both jobs use disposable infrastructure, **not** real hosting-provider accounts or customer databases.

Acceptance covers: installer prerequisites, CSRF rejection, successful first administrator creation, one-time lock, user login, dashboard, public HTML/CSS/JS, language switching, dark/light mode, 390px and 768px mobile/tablet viewports, keyboard interaction, and basic desktop rendering. Tests use a standalone QA account and disposable database.

Headless mobile/tablet viewport simulation cannot replace a physical-device review. Separately verify current iPhone Safari, Android Chrome, tablet layout, keyboard-only focus, slow connections, email delivery, MySQL compatibility and shared-host permissions on a disposable staging host before release.

## Release gate

- A failed automated installer or browser test blocks the QA artifact.
- Never auto-activate payment gateways from the acceptance workflow.
- No job in this workflow pushes, uploads, or deploys to a live Edumia installation.


## Physical devices and real shared hosting

The evidence-based sign-off documents are [Physical-device acceptance](device-acceptance.md) and [Shared-host acceptance](shared-host-acceptance.md). Both gates remain OPEN until actual hardware and real hosting are tested. Neither CI browser emulation nor a disposable MySQL runner can automatically close them.

## Four administrator workspaces: UI and installation acceptance

The automated browser suite opens **Media Library**, **General Settings**, **Roles & Permissions**, and **Instructor Management** using the new administrator created by the one-time installer. Each page must render its heading and shared admin shell with no page-level horizontal overflow. Browser coverage includes Chromium desktop (1440px), Firefox desktop (1366px), Chromium mobile (390px), Chromium tablet (768px), and WebKit mobile (375px). The suite also checks functional media and instructor filters, registration and language controls, role creation fields, color-theme persistence, and mobile sidebar opening and backdrop dismissal.

- First-run screenshots: `installed-{media,settings,roles,instructors}-{desktop,mobile}.png`.
- Browser-matrix screenshots: `admin-{media,settings,roles,instructors}-{browser-scenario}.png`.
- Evidence artifacts: `edumia-browser-evidence` and `edumia-independent-customer-evidence`. Screenshots show only disposable QA users. Do not add real credentials to evidence.
- The second job downloads `edumia-integrated-qa`, checks its build/source SHA and absence of `.env`, installer code, or installation lock, then completes setup and verifies the resulting lock and administrator pages on a clean runner. A successful source-tree install **alone** does not qualify as archive acceptance.
- **Automated sign-off:** require both `Edumia quality checks` and **both jobs** of `Edumia integrated QA package` to be green for the **same tested commit SHA**. Check screenshots for clipping, contrast, legibility and touch-target accessibility before release.

### Mandatory external gates (not satisfied by GitHub CI)

The automated tests are not a physical iPhone, Android phone, or tablet; nor are they a hosted cPanel or aaPanel customer environment. On actual staging hardware and hosting, an independent reviewer must perform the cases in [Physical-device acceptance](device-acceptance.md) and [Shared-host acceptance](shared-host-acceptance.md), verify separate browser and hosting PHP handlers, test a clean installation and a backup-backed upgrade, then record dated evidence. All external rows remain **NOT TESTED** unless explicitly signed off with real observations.



## Administrator release-readiness workspace

An administrator can open GET /admin/release-readiness for read-only runtime diagnostics, unverified external gates and remaining module workstreams. It is included as an additional admin page in the fresh-install and Chromium/Firefox/WebKit responsive browser matrix. This is not a deployment, migration or payment activation control.

Local PASS labels describe only the PHP installation serving the page. Real hosting, physical-device, module and commerce gates stay explicitly NOT VERIFIED or INCOMPLETE until independently evidenced. The authoritative release record is release-readiness.md; see also shared-host-acceptance.md and device-acceptance.md.
