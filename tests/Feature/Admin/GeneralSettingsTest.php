<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GeneralSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role, string $email): User
    {
        $user = User::create(['name' => $role, 'email' => $email, 'password' => 'Password#12345']);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    public function test_admin_can_update_settings_and_disable_registration(): void
    {
        $admin = $this->account('admin', 'admin@edumia.test');

        $this->actingAs($admin)->get('/admin/settings/general')->assertOk()->assertSee('General settings');
        $this->put('/admin/settings/general', [
            'site_name' => 'Edumia Campus',
            'support_email' => 'support@example.test',
            'homepage_notice' => 'Enrollment begins soon',
        ])->assertRedirect(route('admin.settings.edit'));

        $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'Edumia Campus']);
        $this->assertDatabaseHas('settings', ['key' => 'allow_registration', 'value' => '0']);
        $this->assertDatabaseHas('audit_events', ['action' => 'settings.updated']);
        auth()->logout();
        $this->get('/register')->assertForbidden();
        $this->post('/register', [
            'name' => 'A new student',
            'email' => 'new@edumia.test',
            'password' => 'Password#12345',
            'password_confirmation' => 'Password#12345',
        ])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'new@edumia.test']);
    }

    public function test_site_notice_and_contact_information_are_visible_and_escaped(): void
    {
        $admin = $this->account('admin', 'admin@example.test');
        $this->actingAs($admin)->put('/admin/settings/general', [
            'site_name' => 'Global Skills Center',
            'default_locale' => 'en',
            'site_timezone' => 'Europe/London',
            'support_email' => 'help@example.test',
            'homepage_notice' => '<script>alert("danger")</script> Welcome worldwide',
            'allow_registration' => '1',
        ])->assertRedirect(route('admin.settings.edit'));

        auth()->logout();
        $this->get('/')->assertOk()
            ->assertSee('Global Skills Center')
            ->assertSee('Welcome worldwide')
            ->assertDontSee('<script>alert("danger")</script>', false)
            ->assertSee('mailto:help@example.test', false);

        $this->assertDatabaseHas('settings', ['key' => 'site_timezone', 'value' => 'Europe/London']);
        $this->assertDatabaseHas('settings', ['key' => 'homepage_notice',
            'value' => '<script>alert("danger")</script> Welcome worldwide']);
    }

    public function test_student_cannot_change_general_settings(): void
    {
        $student = $this->account('student', 'student@edumia.test');

        $this->actingAs($student)->get('/admin/settings/general')->assertForbidden();
        $this->put('/admin/settings/general', ['site_name' => 'Other'])
            ->assertForbidden();
    }

    public function test_invalid_support_email_is_rejected(): void
    {
        $admin = $this->account('admin', 'admin@edumia.test');

        $this->actingAs($admin)->put('/admin/settings/general', [
            'site_name' => 'Edumia',
            'support_email' => 'invalid@',
        ])->assertSessionHasErrors('support_email');
        $this->assertDatabaseMissing('settings', ['key' => 'support_email']);
    }

    public function test_settings_workspace_uses_organized_sections_without_changing_the_save_contract(): void
    {
        $admin = $this->account('admin', 'admin@edumia.test');
        $this->actingAs($admin)->get('/admin/settings/general')->assertOk()
            ->assertSee('Platform identity')->assertSee('Language & timezone', false)
            ->assertSee('Support & registration', false)
            ->assertSee('name="site_name"', false)
            ->assertSee('name="default_locale"', false)
            ->assertSee('name="site_timezone"', false)
            ->assertSee('name="allow_registration"', false)
            ->assertSee('action="'.route('admin.settings.update').'"', false)
            ->assertSee('Save general settings');
    }

    public function test_settings_normalization_audit_history_and_invalid_region_are_enforced(): void
    {
        $admin = $this->account('admin', 'settings-operator@edumia.test');

        $payload = [
            'site_name' => '  Global Edumia Campus  ',
            'support_email' => '  help@example.test  ',
            'default_locale' => 'en',
            'site_timezone' => ' Asia/Phnom_Penh ',
            'allow_registration' => '0',
            'homepage_notice' => '  New learning courses  ',
        ];
        $this->actingAs($admin)->put('/admin/settings/general', $payload)
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'Global Edumia Campus']);
        $this->assertDatabaseHas('settings', ['key' => 'support_email', 'value' => 'help@example.test']);
        $this->assertDatabaseHas('settings', ['key' => 'site_timezone', 'value' => 'Asia/Phnom_Penh']);
        $this->assertDatabaseHas('settings', ['key' => 'allow_registration', 'value' => '0']);
        $this->get('/admin/settings/general')->assertOk()
            ->assertSee('Settings change history')
            ->assertSee('settings-site-name')
            ->assertSee('name="allow_registration" value="0"', false)
            ->assertSee('Global Edumia Campus')
            ->assertSee('help@example.test');

        $count = \App\Models\AuditEvent::query()->where('action', 'settings.updated')->count();
        $this->put('/admin/settings/general', $payload)->assertRedirect();
        $this->assertSame($count, \App\Models\AuditEvent::query()
            ->where('action', 'settings.updated')->count(), 'No audit event for a no-op save.');

        $this->put('/admin/settings/general', [
            'site_name' => 'Invalid defaults',
            'default_locale' => 'unknown_language',
            'site_timezone' => 'Invalid/Mars',
            'support_email' => 'bad@',
            'allow_registration' => '1',
        ])->assertSessionHasErrors(['default_locale', 'site_timezone', 'support_email']);
        $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'Global Edumia Campus']);
    }

    public function test_only_administrator_can_read_settings_audit_history(): void
    {
        $admin = $this->account('admin', 'settings-admin@edumia.test');
        $student = $this->account('student', 'settings-reader@edumia.test');
        $this->actingAs($admin)->put('/admin/settings/general', [
            'site_name' => 'Edumia Audit Center',
            'allow_registration' => '1',
        ])->assertRedirect();

        $this->get('/admin/settings/general?history_page=1')->assertOk()
            ->assertSee('Settings change history')
            ->assertSee('Edumia Audit Center');
        $this->assertDatabaseHas('audit_events', [
            'actor_id' => $admin->id, 'action' => 'settings.updated',
        ]);

        $this->actingAs($student)->get('/admin/settings/general?history_page=1')
            ->assertForbidden();
        $this->put('/admin/settings/general', ['site_name' => 'Escalated'])
            ->assertForbidden();
    }

}
