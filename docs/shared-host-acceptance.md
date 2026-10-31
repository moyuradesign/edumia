# Edumia shared-host installation acceptance

**Current result: NOT TESTED ON REAL SHARED HOSTING.** GitHub CI's disposable MySQL/PHP and Apache checks are supporting evidence only. Run the following on a dedicated test subdomain and new empty database. Never deploy to the existing installed website for acceptance.

## Preflight on staging

1. Choose PHP 8.3, 8.4 or 8.5 and enable OpenSSL, PDO/MySQL, Mbstring, Ctype, Tokenizer and Fileinfo. Verify the web PHP handler separately from PHP CLI.
2. Use the host's document root setting to serve **only the application's public/ directory**. Apache requires mod_rewrite and AllowOverride; Nginx needs try_files routing. Never expose the repository root.
3. Extract an acceptance snapshot, install/include production Composer dependencies and compiled Vite assets. Ensure required Laravel storage and cache directories are writable.
4. Create a **new, empty MySQL database** and unique test DB user. Do not use existing customers or databases.
5. Run the read-only CLI tool from the application's staging root: **php scripts/shared-host-preflight.php --root=. --document-root=public**. For a report: add **--json** and redirect the output to a private JSON file.
6. Make a fresh, private, random installer/authorize.txt (minimum 16 characters), outside public/. It is a one-time installation code, not a verified purchase code.

## HTTP installer and customer flows

Open the staging HTTPS URL /installer (or follow the first-visit / redirect). Confirm prerequisites, enter staging-only settings and install exactly once. Verify an active administrator can log in and open General Settings. Confirm the installer rejects a second run; **delete the root-level installer/ directory** after success. Confirm .env and installation lock remain private.

Check the public /courses, /fr/courses and /de/contact routes, both compiled CSS/JS assets with correct MIME types, HTTPS and denied private URLs. Check sessions, navigation, search, localization and contact validation. Re-run the read-only preflight and record all REVIEW items and the true hosting document root. Never activate live payments or issue real customer transactions as part of acceptance.

| ID | Requirement | Evidence | State |
| --- | --- | --- | --- |
| H01 | Real cPanel/aaPanel/Plesk host and PHP web handler | Host panel, PHP version | NOT TESTED |
| H02 | public/ document root and Apache/Nginx routing | Domain settings, HTTP results | NOT TESTED |
| H03 | Fresh isolated MySQL installation | Successful installer output | NOT TESTED |
| H04 | Administrator login and permissions | Redacted screenshots | NOT TESTED |
| H05 | One-time lock and installer removed | HTTP status, file inventory | NOT TESTED |
| H06 | Vite CSS/JS HTTP responses, correct MIME types | Network tab | NOT TESTED |
| H07 | Runtime permissions, sessions and cache | Preflight JSON | NOT TESTED |
| H08 | HTTPS, private paths denied, secure settings | HTTP statuses, redacted review | NOT TESTED |
| H09 | Physical-device checks on the same staging URL | Link to device-acceptance evidence | NOT TESTED |
| H10 | Payments disabled and no production data | Configuration review | NOT TESTED |

**Release gate: OPEN.** All mandatory rows require actual host evidence and reviewer sign-off, not simulated results. Keep sensitive information out of screenshots and reports.
