<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BrandIdentityTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        $user = User::query()->create([
            'name' => ucfirst($role).' User', 'email' => $role.'-brand@edumia.test',
            'password' => 'Strong#Password123',
        ]);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();
        return $user;
    }

    public function test_public_header_footer_and_favicons_share_theme_aware_edumia_assets(): void
    {
        $response = $this->get('/');
        $response->assertOk()
            ->assertSee('branding/logo-light.svg')
            ->assertSee('branding/logo-dark.svg')
            ->assertSee('branding/favicon.svg')
            ->assertSee('apple-touch-icon.png')
            ->assertSee('favicon.ico')
            ->assertSee('class="brand premium-brand"', false)
            ->assertSee('class="premium-footer-logo"', false);
        $this->assertSame(2, substr_count((string) $response->getContent(), 'class="edumia-brand-lockup"'));
    }

    public function test_authentication_uses_the_same_brand_without_external_header_or_footer(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('branding/logo-light.svg')
            ->assertSee('branding/logo-dark.svg')
            ->assertSee('branding/favicon.svg')
            ->assertDontSee('<header', false)
            ->assertDontSee('<footer', false);
    }

    public function test_admin_instructor_and_student_workspaces_use_consistent_logo_and_favicon(): void
    {
        foreach ([
            ['admin', '/dashboard', 'ADMIN CONSOLE'],
            ['instructor', '/instructor', 'INSTRUCTOR STUDIO'],
            ['student', '/account', 'STUDENT ACCOUNT'],
        ] as [$role, $route, $label]) {
            $user = $this->account($role);
            $this->actingAs($user)->get($route)->assertOk()
                ->assertSee('branding/favicon.svg')
                ->assertSee('branding/logo-light.svg')
                ->assertSee('branding/logo-dark.svg')
                ->assertSee($label)
                ->assertSee('class="admin-brand-text"', false);
        }
    }
}
