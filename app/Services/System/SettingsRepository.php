<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Models\AuditEvent;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SettingsRepository
{
    private const DEFAULTS = [
        'site_name' => 'Edumia',
        'support_email' => '',
        'default_locale' => 'en',
        'site_timezone' => 'UTC',
        'allow_registration' => '1',
        'homepage_notice' => '',
    ];

    public function general(): array
    {
        $stored = Setting::query()
            ->whereIn('key', array_keys(self::DEFAULTS))
            ->pluck('value', 'key')->all();

        return array_replace(self::DEFAULTS, $stored);
    }

    public function registrationEnabled(): bool
    {
        return $this->general()['allow_registration'] === '1';
    }

    public function updateGeneral(User $actor, array $data): void
    {
        abort_unless($actor->isAdmin(), 403);
        $values = [
            'site_name' => trim($data['site_name']),
            'support_email' => trim($data['support_email'] ?? ''),
            'default_locale' => (string) ($data['default_locale'] ?? $this->general()['default_locale']),
            'site_timezone' => (string) ($data['site_timezone'] ?? $this->general()['site_timezone']),
            'allow_registration' => ($data['allow_registration'] ?? false) ? '1' : '0',
            'homepage_notice' => trim($data['homepage_notice'] ?? ''),
        ];

        DB::transaction(function () use ($actor, $values): void {
            foreach ($values as $key => $value) {
                $setting = Setting::query()->firstOrNew(['key' => $key]);
                $old = $setting->exists ? (string) $setting->value : self::DEFAULTS[$key];
                if ($old === $value) {
                    continue;
                }
                $setting->fill([
                    'value' => $value,
                    'type' => $key === 'allow_registration' ? 'boolean' : 'string',
                    'group_name' => 'general',
                    'is_public' => in_array($key, ['site_name', 'homepage_notice'], true),
                ]);
                $setting->save();
                AuditEvent::create([
                    'actor_id' => $actor->getKey(),
                    'subject_type' => Setting::class,
                    'subject_id' => $setting->getKey(),
                    'action' => 'settings.updated',
                    'previous_values' => ['key' => $key, 'value' => $old],
                    'new_values' => ['key' => $key, 'value' => $value],
                ]);
            }
        });
    }
}
