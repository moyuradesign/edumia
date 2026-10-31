<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class HealthCheckCommand extends Command
{
    protected $signature = 'edumia:health {--json : Output structured machine-readable results}';

    protected $description = 'Check Edumia installation, database, asset and filesystem readiness without exposing secrets.';

    public function handle(): int
    {
        $root = base_path();
        $checks = [
            'php_supported' => PHP_VERSION_ID >= 80300 && PHP_VERSION_ID < 80600,
            'app_key_configured' => is_string(config('app.key')) && config('app.key') !== '',
            'production_debug_disabled' => app()->environment('production') ? config('app.debug') === false : true,
            'storage_writable' => is_writable(storage_path('framework')) && is_writable(storage_path('logs')),
            'bootstrap_cache_writable' => is_writable(base_path('bootstrap/cache')),
            'installer_removed' => ! is_dir($root.'/installer'),
            'installation_lock' => is_file(storage_path('app/edumia-installed.lock')),
        ];

        try {
            DB::connection()->getPdo();
            $checks['database_connection'] = true;
            $checks['users_table'] = Schema::hasTable('users');
        } catch (Throwable) {
            $checks['database_connection'] = false;
            $checks['users_table'] = false;
        }

        $manifestPath = public_path('build/manifest.json');
        $assets = [
            'resources/css/frontend/app.css',
            'resources/css/admin/app.css',
            'resources/js/frontend/app.js',
            'resources/js/admin/app.js',
        ];

        $checks['frontend_assets'] = false;
        if (is_file($manifestPath)) {
            try {
                $manifest = json_decode(
                    (string) file_get_contents($manifestPath),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                $checks['frontend_assets'] = is_array($manifest);
                foreach ($assets as $entry) {
                    $output = $manifest[$entry]['file'] ?? null;
                    if (! is_string($output)
                        || ! str_starts_with($output, 'assets/')
                        || str_contains($output, '..')
                        || ! is_file(public_path('build/'.$output))) {
                        $checks['frontend_assets'] = false;
                        break;
                    }
                }
            } catch (Throwable) {
                $checks['frontend_assets'] = false;
            }
        }

        $passed = ! in_array(false, $checks, true);

        if ($this->option('json')) {
            $this->line(json_encode([
                'status' => $passed ? 'pass' : 'fail',
                'checks' => $checks,
            ], JSON_THROW_ON_ERROR));
        } else {
            foreach ($checks as $check => $ok) {
                $this->line(($ok ? 'PASS' : 'FAIL').' '.$check);
            }
            $this->line('EDUMIA_HEALTH='.($passed ? 'PASS' : 'FAIL'));
        }

        return $passed ? self::SUCCESS : self::FAILURE;
    }
}
