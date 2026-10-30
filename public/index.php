<?php

declare(strict_types=1);

// Independent, removable installer; the application never requires it after setup.
// The document root MUST be public/. Do not expose the repository root.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (is_string($path) && in_array($path, ['/installer', '/installer/'], true)
    && is_file(__DIR__.'/../installer/index.php')) {
    require __DIR__.'/../installer/index.php';
    exit;
}

define('LARAVEL_START', microtime(true));
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->handleRequest(Illuminate\Http\Request::capture());
