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
}
