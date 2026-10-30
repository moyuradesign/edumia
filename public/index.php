<?php

declare(strict_types=1);

// Independent, removable installer; the application never requires it after setup.
// Redirect the homepage to initial setup only when installation has not yet
// started. After the installer writes .env or the lock, Laravel serves /
// normally. The installer itself remains outside the public web root.
// The document root MUST be public/. Do not expose the repository root.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (is_string($path) && in_array($path, ['/installer', '/installer/'], true)
    && is_file(__DIR__.'/../installer/index.php')) {
    require __DIR__.'/../installer/index.php';
    exit;
}

// Pre-install entrypoint: do not bootstrap Laravel without a configuration.
// Avoid permanent/cached redirects: the site becomes normal after install.
if (in_array($path, ['/', '/index.php'], true)
    && in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)
    && !is_file(__DIR__.'/../.env')
    && !is_file(__DIR__.'/../storage/app/edumia-installed.lock')
    && is_file(__DIR__.'/../installer/index.php')) {
    header('Cache-Control: no-store, private');
    header('Location: /installer', true, 302);
    exit;
}

define('LARAVEL_START', microtime(true));
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->handleRequest(Illuminate\Http\Request::capture());
