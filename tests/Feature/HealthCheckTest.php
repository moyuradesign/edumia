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
}
