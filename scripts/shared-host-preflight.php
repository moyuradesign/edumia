<?php

declare(strict_types=1);

/**
 * Edumia read-only shared-host preflight (PHP 8.3+).
 *
 * Examples:
 * php scripts/shared-host-preflight.php --root=. --document-root=public
 * php scripts/shared-host-preflight.php --root=/home/site/edumia --document-root=/home/site/edumia/public --json
 *
 * Does not load Laravel, run migrations, write to files or print secrets.
 * Run from SSH/hosting terminal on a dedicated *staging* installation.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$options = getopt('', ['root::', 'document-root::', 'json']);
$root = realpath($options['root'] ?? dirname(__DIR__));
$public = $root === false ? false : realpath($root.'/public');
$documentRoot = isset($options['document-root'])
    ? realpath((str_starts_with($options['document-root'], '/') ? '' : getcwd().'/').$options['document-root'])
    : null;
$results = [];
$check = static function (string $name, bool $passed, string $message) use (&$results): void {
    $results[] = [
        'check' => $name,
        'status' => $passed ? 'PASS' : 'FAIL',
        'detail' => $message,
    ];
};
$note = static function (string $name, string $message) use (&$results): void {
    $results[] = ['check' => $name, 'status' => 'REVIEW', 'detail' => $message];
};

if ($root === false || $public === false) {
    $check('application_root', false, 'Cannot locate application root and public/ directory.');
} else {
    $check('application_root', true, 'Application and public/ directories exist.');
    $check('php_runtime', PHP_VERSION_ID >= 80300 && PHP_VERSION_ID < 80600,
        'Use a supported PHP runtime (8.3, 8.4 or 8.5).');

    foreach (['openssl', 'PDO', 'pdo_mysql', 'mbstring', 'ctype', 'tokenizer', 'fileinfo'] as $extension) {
        $check('extension_'.$extension, extension_loaded($extension), 'Required PHP extension: '.$extension);
    }
    if (! extension_loaded('curl')) {
        $note('extension_curl', 'cURL recommended for external payment or API integrations.');
    }

    $check('composer_vendor', is_file($root.'/vendor/autoload.php'),
        'Production Composer dependencies must be included or installed.');
    $check('public_apache_rewrites', is_file($public.'/.htaccess')
        && str_contains((string) file_get_contents($public.'/.htaccess'), 'RewriteEngine On')
        && str_contains((string) file_get_contents($public.'/.htaccess'), 'RewriteRule'),
        'public/.htaccess and Apache mod_rewrite/AllowOverride are required on Apache hosts.');
    $check('public_entrypoint', is_file($public.'/index.php'),
        'public/index.php must be the HTTP entry point.');

    $manifestPath = $public.'/build/manifest.json';
    $manifest = is_file($manifestPath) ? json_decode((string) file_get_contents($manifestPath), true) : null;
    $check('vite_manifest', is_array($manifest), 'Compiled public/build/manifest.json must contain valid JSON.');
    if (is_array($manifest)) {
        foreach ([
            'resources/css/frontend/app.css',
            'resources/css/admin/app.css',
            'resources/js/frontend/app.js',
            'resources/js/admin/app.js',
        ] as $entry) {
            $asset = is_array($manifest[$entry] ?? null) ? ($manifest[$entry]['file'] ?? null) : null;
            $resolved = is_string($asset) && $asset !== ''
                ? realpath($public.'/build/'.$asset)
                : false;
            $check('vite_'.basename($entry), $resolved !== false
                && str_starts_with($resolved, $public.'/build/')
                && is_file($resolved),
                'Compiled entrypoint '.$entry.' must exist inside public/build/.');
        }
    }

    foreach (['storage/app', 'storage/framework/cache', 'storage/framework/sessions',
        'storage/framework/views', 'bootstrap/cache'] as $directory) {
        $check('writable_'.str_replace('/', '_', $directory),
            is_dir($root.'/'.$directory) && is_writable($root.'/'.$directory),
            'Required Laravel runtime directory must be writable: '.$directory);
    }

    $check('private_paths',
        !file_exists($public.'/.env')
        && !file_exists($public.'/vendor')
        && !file_exists($public.'/installer')
        && !file_exists($public.'/composer.json'),
        'Never put .env, vendor/, installer/ or composer.json directly inside public/.');

    if ($documentRoot === null) {
        $note('document_root', 'Pass --document-root=/absolute/path/to/public to verify actual hosting document root.');
    } else {
        $check('document_root', $documentRoot === $public,
            'Configured document root must equal the Edumia public/ directory.');
    }

    $installed = is_file($root.'/.env');
    $locked = is_file($root.'/storage/app/edumia-installed.lock');
    if ($installed) {
        $check('installation_lock', $locked,
            'Installed site must retain its one-time installation lock.');
        if (is_dir($root.'/installer')) {
            $note('remove_installer', 'Delete the root-level installer/ directory after installation.');
        }
        $envMode = fileperms($root.'/.env');
        if ($envMode !== false && ($envMode & 0004) !== 0) {
            $note('env_permissions', 'Restrict .env read access for other local hosting users where supported.');
        }
    } else {
        $check('clean_install', ! $locked,
            'New installation should have neither .env nor an old installation lock.');
        $note('installer_authorization',
            'Before installing, create a unique installer/authorize.txt outside public/; do not distribute the code.');
    }

    $note('web_php', 'Hosting PHP-FPM or Apache PHP configuration may differ from CLI; verify in the hosting panel.');
    $note('http_acceptance', 'Verify HTTPS, /installer, real asset MIME types and denied private paths on the isolated staging URL.');
    $note('device_acceptance', 'Physical iPhone, Android and tablet results require device evidence, not emulation.');
}

$passed = count(array_filter($results, static fn (array $item): bool => $item['status'] === 'PASS'));
$failed = count(array_filter($results, static fn (array $item): bool => $item['status'] === 'FAIL'));
$review = count($results) - $passed - $failed;
$summary = [
    'tool' => 'Edumia shared-host preflight',
    'result' => $failed === 0 ? 'PASS_WITH_REVIEW_ITEMS' : 'FAIL',
    'passed' => $passed, 'failed' => $failed, 'review' => $review,
    'checks' => $results,
];
if (isset($options['json'])) {
    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
} else {
    foreach ($results as $result) {
        echo sprintf('[%-6s] %s — %s', $result['status'], $result['check'], $result['detail']).PHP_EOL;
    }
    echo sprintf("EDUMIA_SHARED_HOST_PREFLIGHT=%s PASS=%d FAIL=%d REVIEW=%d\n",
        $summary['result'], $passed, $failed, $review);
}
exit($failed === 0 ? 0 : 1);
