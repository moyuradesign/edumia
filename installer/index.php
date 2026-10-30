<?php

declare(strict_types=1);

/**
 * Edumia standalone setup (original code; no Laravel runtime dependency for UI).
 * Remove the entire installer directory after successful installation.
 * A manually created installer/authorize.txt with a random >= 16 character
 * deployment-specific token is required before installation can begin.
 */

const EDUMIA_ROOT = __DIR__.'/..';
const EDUMIA_LOCK = EDUMIA_ROOT.'/storage/app/edumia-installed.lock';
const EDUMIA_TOKEN_FILE = __DIR__.'/authorize.txt';

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function installed(): bool
{
    return is_file(EDUMIA_LOCK) || is_file(EDUMIA_ROOT.'/.env');
}

// This check runs before opening a PHP session and without requiring vendor/.
if (installed()) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    echo 'Installer unavailable. Installation is complete or a configuration already exists.';
    exit;
}

$https = ($_SERVER['HTTPS'] ?? '') === 'on'
    || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('edumia_installer');
session_set_cookie_params([
    'httponly' => true, 'secure' => $https, 'samesite' => 'Strict', 'path' => '/installer',
]);
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, private');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
header('Content-Type: text/html; charset=utf-8');

$_SESSION['edumia_csrf'] ??= bin2hex(random_bytes(24));
$csrf = $_SESSION['edumia_csrf'];
$checks = [
    'PHP 8.3 or later (Laravel 13 supports PHP up to 8.5)' => PHP_VERSION_ID >= 80300 && PHP_VERSION_ID < 80600,
    'OpenSSL extension' => extension_loaded('openssl'),
    'PDO and pdo_mysql extensions' => extension_loaded('pdo') && extension_loaded('pdo_mysql'),
    'Mbstring extension' => extension_loaded('mbstring'),
    'Ctype / tokenizer / fileinfo extensions' => extension_loaded('ctype') && extension_loaded('tokenizer') && extension_loaded('fileinfo'),
    'Composer dependencies installed (vendor/autoload.php)' => is_file(EDUMIA_ROOT.'/vendor/autoload.php'),
    'Frontend production build (public/build/manifest.json)' => is_file(EDUMIA_ROOT.'/public/build/manifest.json'),
    'Application root writable for first-time .env' => is_writable(EDUMIA_ROOT),
    'Application storage writable' => is_writable(EDUMIA_ROOT.'/storage/app'),
    'Bootstrap cache writable' => is_writable(EDUMIA_ROOT.'/bootstrap/cache'),
    'Private authorization file configured' => is_file(EDUMIA_TOKEN_FILE)
        && strlen(trim((string) file_get_contents(EDUMIA_TOKEN_FILE))) >= 16,
];
$ready = !in_array(false, $checks, true);
$errors = [];
$success = false;

function envValue(string $value): string
{
    if (preg_match('/[\x00-\x1f\x7f]/', $value)) {
        throw new RuntimeException('Configuration values cannot contain control characters.');
    }
    return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
}

function install(array $values): void
{
    // Installation must target a *new* installation. Never overwrite a real .env.
    if (installed()) {
        throw new RuntimeException('An existing Edumia installation or .env file was found.');
    }

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $values['db_host'], (int) $values['db_port'], $values['db_database']);
    try {
        $pdo = new PDO($dsn, $values['db_username'], $values['db_password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 7,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->query('SELECT 1')->fetch();
        // Avoid accidentally migrating an existing unrelated application database.
        $existing = $pdo->query("SHOW TABLES LIKE 'migrations'")->fetch();
        if ($existing !== false) {
            throw new RuntimeException('Select a fresh database with no existing migrations table.');
        }
    } catch (PDOException) {
        throw new RuntimeException('Database connection failed. Check database name, host, port and credentials.');
    }

    $settings = [
        'APP_NAME' => $values['app_name'], 'APP_ENV' => 'production',
        'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
        'APP_DEBUG' => 'false', 'APP_URL' => $values['app_url'],
        'APP_TIMEZONE' => $values['timezone'], 'APP_LOCALE' => 'en',
        'APP_FALLBACK_LOCALE' => 'en', 'LOG_CHANNEL' => 'stack',
        'DB_CONNECTION' => 'mysql', 'DB_HOST' => $values['db_host'],
        'DB_PORT' => $values['db_port'], 'DB_DATABASE' => $values['db_database'],
        'DB_USERNAME' => $values['db_username'], 'DB_PASSWORD' => $values['db_password'],
        'SESSION_DRIVER' => 'file', 'CACHE_STORE' => 'file',
        'QUEUE_CONNECTION' => 'sync',
    ];
    $lines = [];
    foreach ($settings as $name => $value) {
        $lines[] = $name.'='.envValue($value);
    }
    $env = implode("\n", $lines)."\n";
    $temp = tempnam(EDUMIA_ROOT, '.edumia-');
    if ($temp === false) {
        throw new RuntimeException('Could not create temporary configuration file.');
    }
    @chmod($temp, 0600);
    try {
        if (file_put_contents($temp, $env, LOCK_EX) !== strlen($env)) {
            throw new RuntimeException('Could not write temporary configuration.');
        }
        // Never replace a .env from an earlier installation.
        if (installed()) {
            throw new RuntimeException('Installation became unavailable; another configuration exists.');
        }
        if (!rename($temp, EDUMIA_ROOT.'/.env')) {
            throw new RuntimeException('Unable to put .env into place.');
        }
        @chmod(EDUMIA_ROOT.'/.env', 0600);
        $temp = '';

        // Laravel is loaded only for migrations. Installer UI itself is independent.
        require_once EDUMIA_ROOT.'/vendor/autoload.php';
        $app = require EDUMIA_ROOT.'/bootstrap/app.php';
        /** @var \Illuminate\Contracts\Console\Kernel $kernel */
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        $result = $kernel->call('migrate', ['--force' => true, '--no-interaction' => true]);
        if ($result !== 0) {
            throw new RuntimeException('Laravel migrations failed; inspect the application logs.');
        }
        if (file_put_contents(EDUMIA_LOCK, 'installed_at='.gmdate('c')."\n", LOCK_EX) === false) {
            throw new RuntimeException('Could not write the installation lock.');
        }
        @chmod(EDUMIA_LOCK, 0600);
    } catch (Throwable $exception) {
        // A failed install must not expose database credentials. The database
        // may contain partial migrations; restore it before retrying.
        if (!is_file(EDUMIA_LOCK) && is_file(EDUMIA_ROOT.'/.env')) {
            @unlink(EDUMIA_ROOT.'/.env');
        }
        throw new RuntimeException('Installation did not finish: '.$exception->getMessage().
            ' If migrations ran, restore a clean database before retrying.');
    } finally {
        if ($temp !== '' && is_file($temp)) {
            @unlink($temp);
        }
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedCsrf = $_POST['_csrf'] ?? '';
    if (!is_string($submittedCsrf) || !hash_equals($csrf, $submittedCsrf)) {
        http_response_code(419);
        $errors[] = 'This form has expired. Reload the installer and try again.';
    } elseif (!$ready) {
        $errors[] = 'Resolve every failed prerequisite before proceeding.';
    } elseif (!isset($_POST['accept']) || $_POST['accept'] !== 'yes') {
        $errors[] = 'Please confirm you are authorized to install this application.';
    } else {
        $key = $_POST['authorization'] ?? '';
        $storedKey = trim((string) file_get_contents(EDUMIA_TOKEN_FILE));
        if (!is_string($key) || !hash_equals($storedKey, $key)) {
            $errors[] = 'Invalid deployment authorization token.';
        } else {
            $required = ['app_name', 'app_url', 'timezone', 'db_host', 'db_port', 'db_database', 'db_username', 'db_password'];
            $values = [];
            foreach ($required as $field) {
                $input = $_POST[$field] ?? '';
                if (!is_string($input) || strlen($input) > 512) {
                    $errors[] = 'Invalid configuration value: '.$field;
                    break;
                }
                $values[$field] = trim($input);
            }
            if (!$errors) {
                $parts = parse_url($values['app_url']);
                $scheme = is_array($parts) ? ($parts['scheme'] ?? '') : '';
                $host = is_array($parts) ? ($parts['host'] ?? '') : '';
                $local = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
                if (($scheme !== 'https' && !($scheme === 'http' && $local))
                    || $host === '' || isset($parts['user']) || isset($parts['pass'])
                    || isset($parts['query']) || isset($parts['fragment'])) {
                    $errors[] = 'Application URL must use HTTPS (HTTP allowed only for localhost).';
                }
                if ($values['app_name'] === '' || strlen($values['app_name']) > 100) {
                    $errors[] = 'Application name must be between 1 and 100 characters.';
                }
                if (!in_array($values['timezone'], timezone_identifiers_list(), true)) {
                    $errors[] = 'Choose a valid PHP timezone.';
                }
                if (!ctype_digit($values['db_port']) || (int) $values['db_port'] < 1 || (int) $values['db_port'] > 65535) {
                    $errors[] = 'Invalid database port.';
                }
                foreach (['db_host', 'db_database', 'db_username'] as $field) {
                    if ($values[$field] === '' || preg_match('/[\x00-\x20\x7f;\\]/', $values[$field])) {
                        $errors[] = 'Invalid database field: '.$field;
                    }
                }
                if (!$errors) {
                    try {
                        install($values);
                        $success = true;
                        @unlink(EDUMIA_TOKEN_FILE);
                        $_SESSION = [];
                        session_regenerate_id(true);
                    } catch (Throwable $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            }
        }
    }
}

if ($success) {
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Edumia installed</title><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{font:16px system-ui;background:#f8fafc;color:#14213d;display:grid;place-items:center;min-height:100vh}.card{background:white;padding:40px;border-radius:18px;max-width:560px;box-shadow:0 20px 80px #14213d18}a{color:#1d4ed8}</style><main class="card"><h1>Edumia installed</h1><p>Configuration saved and Laravel migrations completed. The installer is now locked.</p><p><strong>Delete the entire root-level <code>installer/</code> directory using your hosting file manager.</strong></p><p><a href="/">Open Edumia</a></p></main></html>';
    exit;
}

function field(string $name, string $title, string $type = 'text', string $default = '', bool $required = true): void
{
    $value = isset($_POST[$name]) && is_string($_POST[$name]) && $type !== 'password'
        ? (string) $_POST[$name] : $default;
    echo '<label>'.escape($title).'<input name="'.escape($name).'" type="'.escape($type).
        '" value="'.escape($value).'" '.($required ? 'required ' : '').
        'autocomplete="off" maxlength="512"></label>';
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Edumia · Installation</title>
<style>
:root{color-scheme:light;--orange:#c2410c;--blue:#1d4ed8;--ink:#162033;--muted:#526177;--line:#e4e9ef}
*{box-sizing:border-box}body{margin:0;font:15px/1.6 system-ui,-apple-system,'Segoe UI',sans-serif;background:linear-gradient(140deg,#fff7ed,#f4f8ff);color:var(--ink)}
main{width:min(860px,calc(100% - 30px));margin:55px auto}.heading{display:flex;gap:14px;align-items:center;margin-bottom:25px}.logo{border-radius:15px;background:var(--orange);color:#fff;padding:9px 18px;font-size:28px;font-weight:900}.title{font-weight:750;font-size:29px;margin:0;line-height:1.2}.subtitle{margin:3px 0;color:var(--muted)}.card{background:#fff;border:1px solid var(--line);border-radius:18px;padding:28px;margin-bottom:20px;box-shadow:0 18px 50px #152b5012}
h2{font-size:19px;margin:0 0 15px}ul{margin:0;padding:0;list-style:none;display:grid;gap:10px}.check{display:flex;justify-content:space-between;gap:10px;border-bottom:1px solid var(--line);padding:5px 0}.pass{color:#166534;font-weight:800}.fail{color:#b91c1c;font-weight:800}.notice{padding:13px 15px;border-radius:10px;background:#fff1eb;color:#85270b;margin:12px 0}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}label{display:grid;gap:6px;font-weight:650;font-size:13px}input{width:100%;font:inherit;color:var(--ink);border:1px solid #b7c1d1;border-radius:9px;padding:11px 12px;background:#fff}input:focus{outline:3px solid #93c5fd;outline-offset:1px}.full{grid-column:1/-1}.buttons{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-top:25px}.button{border:0;border-radius:10px;padding:13px 22px;background:var(--orange);color:white;font-weight:800;font-size:14px;cursor:pointer}.button:disabled{background:#6b7280;cursor:not-allowed}code{font-size:12px;overflow-wrap:anywhere;background:#eff6ff;padding:2px 4px}.foot{color:var(--muted);font-size:12px;text-align:center}a{color:var(--blue)}@media(max-width:600px){.grid{grid-template-columns:1fr}.card{padding:20px}.buttons{align-items:start;flex-direction:column}}
</style>
</head>
<body><main>
<header class="heading"><div class="logo">e</div><div><h1 class="title">Edumia Installer</h1><p class="subtitle">Standalone initial setup · Laravel 13 · PHP 8.3+</p></div></header>
<section class="card"><h2>1. Server requirements</h2><ul>
<?php foreach ($checks as $label => $ok): ?>
<li class="check"><span><?=escape($label)?></span><span class="<?=$ok?'pass':'fail'?>"><?=$ok?'PASS':'ACTION REQUIRED'?></span></li>
<?php endforeach; ?>
</ul><?php if (!$ready): ?><p class="notice">Complete the listed requirements. In your hosting file manager, create <code>installer/authorize.txt</code> containing your own unique random authorization token (at least 16 characters). The token is never shipped in the ZIP.</p><?php endif; ?></section>
<?php if ($errors): ?><section class="card" role="alert"><h2>Review required</h2><?php foreach ($errors as $error): ?><p class="notice"><?=escape($error)?></p><?php endforeach; ?></section><?php endif; ?>
<form class="card" method="post" action="/installer" autocomplete="off">
<h2>2. Site settings</h2><input type="hidden" name="_csrf" value="<?=escape($csrf)?>">
<div class="grid"><?php field('app_name','Application name','text','Edumia');field('app_url','HTTPS application URL','url','https://edumia.tonsamith.com');field('timezone','Timezone','text','Asia/Phnom_Penh'); ?></div>
<h2 style="margin-top:25px">3. Database connection</h2>
<div class="grid"><?php field('db_host','Database host','text','127.0.0.1');field('db_port','Database port','number','3306');field('db_database','Database name');field('db_username','Database username');field('db_password','Database password','password','',false); ?></div>
<h2 style="margin-top:25px">4. Deployment authorization</h2>
<div class="grid"><?php field('authorization','Token from installer/authorize.txt','password'); ?></div>
<div class="buttons"><label style="display:flex;align-items:start;gap:10px;font-weight:500"><input type="checkbox" name="accept" value="yes" required style="width:auto;margin-top:5px">I am authorized to install Edumia in a new database. I understand this creates configuration and executes Laravel migrations.</label><button class="button" type="submit" <?=$ready?'':'disabled'?>>Install Edumia</button></div>
</form><p class="foot">© <?=date('Y')?> <a href="https://tonsamith.com" rel="noopener noreferrer">Ton Samith</a>. Remove this installer folder after installation.</p>
</main></body></html>
