<?php

declare(strict_types=1);

/**
 * Safe, read-only compiled-view check. Requires Composer dependencies.
 *
 * php scripts/view-cache-preflight.php
 * php scripts/view-cache-preflight.php --json
 * php scripts/view-cache-preflight.php --root=/path/to/edumia --json
 *
 * Does not modify Laravel's cache, .env, files, database or ownership.
 * Run under PHP-FPM's Unix account to verify effective write access.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}
$options = getopt('', ['root::', 'json']);
$root = realpath($options['root'] ?? dirname(__DIR__));
if ($root === false || ! is_file($root.'/vendor/autoload.php')) {
    fwrite(STDERR, "EDUMIA_VIEW_CACHE=FAIL Cannot locate Laravel root/vendor/autoload.php\n");
    exit(1);
}
require_once $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$expected = $root.'/bootstrap/cache/views';
$actual = config('view.compiled');
$errors = [];
if (! is_string($actual) || $actual !== $expected) {
    $errors[] = 'Compiled views must point to this release, not a historical storage path.';
}
if (! is_dir($expected)) {
    $errors[] = 'Release-local view directory is missing.';
}
if (is_link($expected)) {
    $errors[] = 'Release-local view directory must not be a symlink.';
}
if (! is_writable($expected)) {
    $errors[] = 'Current Unix account cannot write to the release-local view directory.';
}
$compiledFiles = is_dir($expected) ? glob($expected.'/*.php') : false;
if (is_array($compiledFiles)) {
    foreach ($compiledFiles as $file) {
        if (! is_readable($file)) {
            $errors[] = 'A compiled Blade file is not readable by the current Unix account.';
            break;
        }
    }
}
$report = [
    'status' => $errors === [] ? 'PASS' : 'FAIL',
    'application_root' => $root,
    'expected_compiled_path' => $expected,
    'effective_compiled_path' => is_string($actual) ? $actual : null,
    'checked_by_uid' => function_exists('posix_geteuid') ? posix_geteuid() : null,
    'issues' => $errors,
];
if (isset($options['json'])) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
} else {
    echo "EDUMIA_VIEW_CACHE=".$report['status'].PHP_EOL;
    echo 'Expected: '.$expected.PHP_EOL;
    echo 'Effective: '.($report['effective_compiled_path'] ?? '(missing)').PHP_EOL;
    foreach ($errors as $issue) {
        echo 'FAIL: '.$issue.PHP_EOL;
    }
}
exit($errors === [] ? 0 : 1);
