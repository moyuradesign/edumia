<?php

declare(strict_types=1);

/**
 * Edumia source structure contract.
 * Run as: php scripts/verify-source-structure.php
 * No Laravel/bootstrap, database, or webserver dependency.
 */
$root = dirname(__DIR__);
$errors = [];
$app = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
    $root.'/app',
    FilesystemIterator::SKIP_DOTS
));

foreach ($app as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = substr($file->getPathname(), strlen($root) + 1);
    $shortName = $file->getBasename('.php');

    if (! preg_match('/^[A-Z][A-Za-z0-9]*$/D', $shortName)) {
        $errors[] = $path.': use a PascalCase class filename without a version suffix';
    }

    $source = file_get_contents($file->getPathname());
    if ($source === false || trim($source) === '') {
        $errors[] = $path.': source is empty or unreadable';

        continue;
    }

    $expectedNamespace = str_replace('/', '\\', dirname($path));
    $expectedNamespace = preg_replace('/^app(?=\\\\|$)/', 'App', $expectedNamespace);
    if (! preg_match('/\\bnamespace\\s+([^;]+)\\s*;/', $source, $m)) {
        $errors[] = $path.': missing namespace declaration';
    } elseif ($m[1] !== $expectedNamespace) {
        $errors[] = $path.': namespace '.$m[1].' should be '.$expectedNamespace;
    }

    if (preg_match('/\b(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)\b/', $source, $m)
        && $m[1] !== $shortName) {
        $errors[] = $path.': declared type '.$m[1].' does not match '.$shortName;
    }
}

foreach (['auth', 'admin', 'instructor', 'student'] as $audience) {
    if (! is_file($root.'/routes/'.$audience.'.php')) {
        $errors[] = 'routes/'.$audience.'.php is missing';
    } elseif (! str_contains((string) file_get_contents($root.'/routes/web.php'), "require __DIR__.'/$audience.php';")) {
        $errors[] = 'routes/web.php must include '.$audience.'.php';
    }
}

foreach (['default', 'auth', 'panel'] as $layout) {
    $path = 'resources/views/layouts/'.$layout.'.blade.php';
    $source = @file_get_contents($root.'/'.$path);
    if (! is_string($source) || ! str_contains($source, '@vite(')) {
        $errors[] = $path.': use Laravel @vite for production asset loading';
    }
    if (is_string($source) && str_contains($source, "asset('build/assets/")) {
        $errors[] = $path.': hardcoded Vite filenames are forbidden';
    }
    if ($layout !== 'default' && is_string($source) && ! str_contains($source, 'resources/css/admin.css')) {
        $errors[] = $path.': authenticated layouts must load admin.css';
    }
}

$assets = ['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js', 'resources/js/admin.js'];
foreach ($assets as $asset) {
    if (! is_file($root.'/'.$asset) || filesize($root.'/'.$asset) === 0) {
        $errors[] = $asset.': missing or empty source entry';
    }
}

if ($errors !== []) {
    fwrite(STDERR, "EDUMIA_SOURCE_STRUCTURE=FAIL\n".implode("\n", $errors)."\n");
    exit(1);
}

printf("EDUMIA_SOURCE_STRUCTURE=PASS\nChecked app/ names, PSR-4 namespaces, route composition, Vite layouts and source entries.\n");
