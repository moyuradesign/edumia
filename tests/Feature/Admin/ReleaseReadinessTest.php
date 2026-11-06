<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\System\ReleaseReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReleaseReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role, string $email): User
    {
        $user = User::query()->create([
            'name' => 'QA Member',
            'email' => $email,
            'password' => 'Strong#Password123',
        ]);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    public function test_only_active_administrators_can_view_release_readiness(): void
    {
        $admin = $this->member('admin', 'admin@example.test');
        $student = $this->member('student', 'student@example.test');
        $instructor = $this->member('instructor', 'instructor@example.test');

        $this->get('/admin/release-readiness')->assertRedirect(route('login'));
        $this->actingAs($student)->get('/admin/release-readiness')->assertForbidden();
        $this->actingAs($instructor)->get('/admin/release-readiness')->assertForbidden();
        $this->actingAs($admin)->get('/admin/release-readiness')->assertOk()
            ->assertSee('data-admin-readiness', false);
    }

    public function test_screen_exposes_local_checks_without_claiming_external_approval(): void
    {
        $admin = $this->member('admin', 'admin-readiness@example.test');
        $this->actingAs($admin)->get(route('admin.readiness.index'))->assertOk()
            ->assertSee('Release readiness')
            ->assertSee('LOCAL RUNTIME ONLY')
            ->assertSee('Customer release blocked')
            ->assertSee('Not approved')
            ->assertSee('Independent shared-host installations')
            ->assertSee('Physical-device acceptance')
            ->assertSee('NOT VERIFIED')
            ->assertSee('Remaining module acceptance')
            ->assertSee('PROVIDER GATED')
            ->assertSee('data-readiness-check="assets"', false)
            ->assertSee('data-readiness-gate="DEVICE"', false)
            ->assertSee('href="'.route('admin.roles.index').'"', false)
            ->assertSee('href="'.route('admin.commerce.accounting.index').'"', false);
    }

    public function test_runtime_report_never_prints_secret_values(): void
    {
        config(['app.key' => str_repeat('K', 32)]);
        $admin = $this->member('admin', 'security@example.test');
        $this->actingAs($admin)->get(route('admin.readiness.index'))
            ->assertOk()
            ->assertDontSee(str_repeat('K', 32));
    }

    public function test_external_gates_remain_open_regardless_of_local_signals(): void
    {
        $service = app(ReleaseReadinessService::class);
        $states = array_column($service->externalGates(), 'state');

        $this->assertNotEmpty($states);
        $this->assertNotContains('PASS', $states);
        $this->assertContains('NOT VERIFIED', $states);
        $this->assertContains('INCOMPLETE', $states);
        $this->assertCount(9, $service->localChecks());
    }

    public function test_readiness_is_linked_from_admin_dashboard(): void
    {
        $admin = $this->member('admin', 'owner@example.test');
        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertSee('href="'.route('admin.readiness.index').'"', false)
            ->assertSee('Release readiness');
    }
}
