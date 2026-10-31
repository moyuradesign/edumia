<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class AssetManifestTest extends TestCase
{
    private const ENTRYPOINTS = [
        'resources/css/app.css',
        'resources/js/app.js',
    ];

    private const LAYOUTS = [
        'resources/views/layouts/default.blade.php',
        'resources/views/layouts/auth.blade.php',
        'resources/views/layouts/panel.blade.php',
    ];

    public function test_every_layout_uses_the_vite_manifest(): void
    {
        $root = dirname(__DIR__, 2);

        foreach (self::LAYOUTS as $layout) {
            $source = file_get_contents($root.'/'.$layout);
            self::assertIsString($source, $layout);
            self::assertStringContainsString('@vite(', $source, $layout);
            self::assertStringNotContainsString("asset('build/assets/", $source, $layout);
        }
    }

    public function test_manifest_resolves_all_required_entrypoints_to_real_files(): void
    {
        $root = dirname(__DIR__, 2);
        $manifestPath = $root.'/public/build/manifest.json';

        self::assertFileExists($manifestPath, 'Run npm run build before production acceptance.');
        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($manifest);

        foreach (self::ENTRYPOINTS as $entrypoint) {
            self::assertArrayHasKey($entrypoint, $manifest);
            self::assertSame(true, $manifest[$entrypoint]['isEntry'] ?? null);

            $output = $manifest[$entrypoint]['file'] ?? null;
            self::assertIsString($output);
            self::assertStringStartsWith('assets/', $output);
            self::assertStringNotContainsString('..', $output);
            self::assertFileExists($root.'/public/build/'.$output);
        }
    }
}
