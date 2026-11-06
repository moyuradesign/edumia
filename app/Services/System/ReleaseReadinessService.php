<?php

declare(strict_types=1);

namespace App\Services\System;

/**
 * Read-only checks for the currently running Edumia installation.
 * Local results cannot certify hosting, physical devices or commerce eligibility.
 */
final class ReleaseReadinessService
{
    public function localChecks(): array
    {
        return [
            $this->check('php', 'Supported PHP web runtime',
                PHP_VERSION_ID >= 80300 && PHP_VERSION_ID < 80600,
                'PHP 8.3, 8.4 or 8.5 is required. Verify the web handler separately from CLI.'),
            $this->check('app_key', 'Application encryption key',
                is_string(config('app.key')) && config('app.key') !== '',
                'Key configured. The value is never displayed.'),
            $this->check('debug', 'Debug output disabled',
                config('app.debug') === false,
                'APP_DEBUG must be disabled on a customer-facing installation.'),
            $this->check('installation_lock', 'One-time installation lock',
                is_file(storage_path('app/edumia-installed.lock')),
                'The lock must exist after initial setup.'),
            $this->check('installer_removed', 'Installer directory removed',
                ! is_dir(base_path('installer')),
                'Delete the removable installer directory after setup.'),
            $this->check('assets', 'Compiled frontend and admin assets',
                $this->hasBuiltAssets(),
                'All required Vite CSS and JS files must exist in the compiled manifest.'),
            $this->check('compiled_views', 'Release-isolated compiled Blade views',
                config('view.compiled') === base_path('bootstrap/cache/views')
                    && is_dir(base_path('bootstrap/cache/views'))
                    && ! is_link(base_path('bootstrap/cache/views'))
                    && is_writable(base_path('bootstrap/cache/views')),
                'Blade views must compile inside this release in bootstrap/cache/views. Never use a previous release path or root-owned compiled views.'),
            $this->check('runtime_storage', 'Writable runtime directories',
                is_writable(storage_path('framework'))
                    && is_writable(storage_path('logs'))
                    && is_writable(base_path('bootstrap/cache')),
                'Storage, logs and bootstrap cache directories must be writable.'),
            $this->check('private_paths', 'Private files outside public directory',
                ! file_exists(public_path('.env'))
                    && ! file_exists(public_path('vendor'))
                    && ! file_exists(public_path('installer'))
                    && ! file_exists(public_path('composer.json')),
                'Also verify the true hosting document root independently.'),
        ];
    }

    /**
     * Unverified until an independent reviewer records real evidence.
     * A simulated browser test can never change these states.
     */
    public function externalGates(): array
    {
        return [
            [
                'id' => 'HOST',
                'title' => 'Independent shared-host installations',
                'state' => 'NOT VERIFIED',
                'detail' => 'Fresh installs and backup-backed upgrades on real aaPanel and cPanel accounts, including PHP handler, HTTPS, database, assets and rollback.',
                'evidence' => 'docs/shared-host-acceptance.md · H01–H12',
            ],
            [
                'id' => 'DEVICE',
                'title' => 'Physical-device acceptance',
                'state' => 'NOT VERIFIED',
                'detail' => 'Real iPhone Safari, Android Chrome and tablet tests, including portrait, landscape, accessibility and authenticated management.',
                'evidence' => 'docs/device-acceptance.md · D01–D14',
            ],
            [
                'id' => 'BUSINESS',
                'title' => 'Module-by-module acceptance',
                'state' => 'INCOMPLETE',
                'detail' => 'Advertised learning, instructor, content, finance and integration workflows require full positive and negative acceptance.',
                'evidence' => 'docs/feature-completion-audit.md · docs/quality-gates.md',
            ],
            [
                'id' => 'COMMERCE',
                'title' => 'External commerce and compliance',
                'state' => 'NOT VERIFIED',
                'detail' => 'Merchant eligibility, genuine signed sandbox events, reconciliation, chargebacks, refunds, tax invoices, currency and payout controls.',
                'evidence' => 'docs/payment-gateway.md · docs/commerce-accounting.md',
            ],
        ];
    }

    /** Code-review workstreams, not live or automatically verified feature telemetry. */
    public function moduleWorkstreams(): array
    {
        return [
            [
                'title' => 'Courses and assessments',
                'state' => 'PARTIAL',
                'remaining' => 'Advanced exams, attachment-based assignments, curriculum revisions and detailed learning analytics.',
                'route' => 'admin.courses.index',
            ],
            [
                'title' => 'Members, roles and instructors',
                'state' => 'PARTIAL',
                'remaining' => 'Platform-wide permissions, credential verification and comprehensive authorization acceptance.',
                'route' => 'admin.roles.index',
            ],
            [
                'title' => 'Media and appearance',
                'state' => 'PARTIAL',
                'remaining' => 'Reusable media selectors, image variants and complete multilingual appearance editing.',
                'route' => 'admin.media.index',
            ],
            [
                'title' => 'Payments, invoices and payouts',
                'state' => 'PROVIDER GATED',
                'remaining' => 'Actual sandbox provider acceptance, tax invoices, onboarding, refunds, chargebacks and payout reconciliation.',
                'route' => 'admin.commerce.accounting.index',
            ],
            [
                'title' => 'Bootcamps, live classes and ebooks',
                'state' => 'PARTIAL',
                'remaining' => 'Attendance, conferencing APIs, EPUB reader and payment-dependent access verification.',
                'route' => 'admin.bootcamps.index',
            ],
            [
                'title' => 'Tutor booking and team training',
                'state' => 'INCOMPLETE',
                'remaining' => 'Tutor calendars, conflict-safe bookings, organization seats, billing and reporting.',
                'route' => null,
            ],
            [
                'title' => 'Localization and notifications',
                'state' => 'PARTIAL',
                'remaining' => 'Full translation coverage, native review, verified SMTP, background queues and delivery evidence.',
                'route' => 'admin.settings.edit',
            ],
        ];
    }

    private function check(string $id, string $title, bool $pass, string $detail): array
    {
        return compact('id', 'title', 'detail') + [
            'state' => $pass ? 'PASS' : 'ACTION NEEDED',
        ];
    }

    private function hasBuiltAssets(): bool
    {
        $root = realpath(public_path('build'));
        $manifestPath = public_path('build/manifest.json');
        if ($root === false || ! is_file($manifestPath)) {
            return false;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        if (! is_array($manifest)) {
            return false;
        }

        foreach ([
            'resources/css/frontend/app.css',
            'resources/css/admin/app.css',
            'resources/js/frontend/app.js',
            'resources/js/admin/app.js',
        ] as $entry) {
            $relative = $manifest[$entry]['file'] ?? null;
            if (! is_string($relative)
                || ! str_starts_with($relative, 'assets/')
                || str_contains($relative, '..')) {
                return false;
            }

            $built = realpath($root.'/'.$relative);
            if ($built === false || ! str_starts_with($built, $root.DIRECTORY_SEPARATOR) || ! is_file($built)) {
                return false;
            }
        }

        return true;
    }
}
