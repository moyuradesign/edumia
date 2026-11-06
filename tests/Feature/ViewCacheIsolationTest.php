<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\System\ReleaseReadinessService;
use Tests\TestCase;

final class ViewCacheIsolationTest extends TestCase
{
    public function test_blade_cache_is_always_scoped_to_this_release(): void
    {
        $expected = base_path('bootstrap/cache/views');
        $this->assertSame($expected, config('view.compiled'));
        $this->assertDirectoryExists($expected);
        $this->assertFalse(is_link($expected));
        $this->assertTrue(is_writable($expected));
        $this->assertStringNotContainsString('storage/framework/views', (string) config('view.compiled'));

        $definition = file_get_contents(base_path('config/view.php'));
        $this->assertIsString($definition);
        $this->assertStringNotContainsString('env(', $definition);
    }

    public function test_release_readiness_reports_a_broken_compiled_view_directory(): void
    {
        $service = new ReleaseReadinessService();
        $checks = $service->localChecks();
        $viewCheck = collect($checks)->firstWhere('id', 'compiled_views');
        $this->assertIsArray($viewCheck);
        $this->assertSame('PASS', $viewCheck['state']);

        config(['view.compiled' => base_path('storage/framework/views')]);
        $checks = $service->localChecks();
        $viewCheck = collect($checks)->firstWhere('id', 'compiled_views');
        $this->assertSame('ACTION NEEDED', $viewCheck['state']);
    }

    public function test_public_page_renders_using_the_release_local_blade_cache(): void
    {
        $this->get('/login')->assertOk();
        $this->assertSame(base_path('bootstrap/cache/views'), config('view.compiled'));
    }
}
