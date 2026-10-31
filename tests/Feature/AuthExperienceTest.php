<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuthExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_authentication_routes_use_standalone_centered_card(): void
    {
        foreach (['/login', '/register', '/forgot-password',
            '/reset-password/sample-token?email=student%40example.test'] as $path) {
            $page = $this->get($path);
            $page->assertOk()
                ->assertSee('class="auth-wrap"', false)
                ->assertSee('class="auth-card"', false)
                ->assertSee('id="auth-heading"', false)
                ->assertSee('auth-field-control')
                ->assertSee('auth-field-icon')
                ->assertDontSee('<header', false)
                ->assertDontSee('<footer', false)
                ->assertSee('name="_token"', false);
        }
    }

    public function test_signin_form_keeps_accessible_labels_svg_icons_and_existing_endpoints(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('action="'.route('login.store').'"', false)
            ->assertSee('for="auth-email"', false)
            ->assertSee('id="auth-email"', false)
            ->assertSee('aria-label="Show password"', false)
            ->assertSee('data-password-toggle', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('name="remember"', false)
            ->assertSee('href="'.route('password.request').'"', false)
            ->assertSee('href="'.route('register').'"', false);

        $this->post('/login', [
            'email' => 'not-an-email',
            'password' => 'wrong',
        ])->assertSessionHasErrors('email');

        $this->get('/login')->assertOk()
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('class="auth-field-error"', false);
    }

    public function test_registration_recovery_and_reset_keep_validated_form_contracts(): void
    {
        $this->get('/register?locale=ja')->assertOk()
            ->assertSee('action="'.route('register.store').'"', false)
            ->assertSee('value="ja" selected', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('autocomplete="new-password"', false)
            ->assertSee('name="locale"', false);

        $this->get('/forgot-password')->assertOk()
            ->assertSee('action="'.route('password.email').'"', false)
            ->assertSee('autocomplete="email"', false);

        $this->get('/reset-password/sample-token?email=student%40example.test')->assertOk()
            ->assertSee('value="sample-token"', false)
            ->assertSee('value="student@example.test"', false)
            ->assertSee('action="'.route('password.store').'"', false);
    }

    public function test_invalid_query_locale_cannot_break_guest_authentication_page(): void
    {
        $this->get('/login?locale[]=xx')->assertOk()
            ->assertSee('<html lang="en"', false);
    }
}
