<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_runtime_health_command_reports_structured_results_without_secrets(): void
    {
        Artisan::call('edumia:health', ['--json' => true]);

        $result = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('checks', $result);
        $this->assertArrayHasKey('database_connection', $result['checks']);
        $this->assertArrayHasKey('frontend_assets', $result['checks']);

        $this->assertArrayNotHasKey('app_key', $result);
        $this->assertArrayNotHasKey('database_password', $result);
    }
    public function test_marketplace_defaults_are_not_bound_to_the_authors_domain(): void
    {
        $config = (string) file_get_contents(config_path('app.php'));
        $installer = (string) file_get_contents(base_path('installer/index.php'));
        $example = (string) file_get_contents(base_path('.env.example'));

        $this->assertStringNotContainsString('https://edumia.tonsamith.com', $config);
        $this->assertStringNotContainsString('https://edumia.tonsamith.com', $installer);
        $this->assertStringContainsString("env('APP_URL', 'http://localhost')", $config);
        $this->assertStringContainsString("field('app_url','HTTPS application URL','url')", $installer);
        $this->assertStringContainsString("'SESSION_SECURE_COOKIE' => str_starts_with(", $installer);
        $this->assertStringContainsString('APP_TIMEZONE=UTC', $example);
        $this->assertStringNotContainsString("field('authorization','Purchase Code'", $installer);
    }
}
