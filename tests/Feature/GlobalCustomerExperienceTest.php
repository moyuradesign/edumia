<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Course;
use App\Models\KnowledgeBase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GlobalCustomerExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role = 'student'): User
    {
        $user = User::query()->create([
            'name' => 'Global Learner',
            'email' => $role.'@example.test',
            'password' => 'Strong#Password123',
        ]);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    public function test_public_catalog_help_contact_and_navigation_keep_supported_language(): void
    {
        $pages = [
            '/fr/ebooks' => 'fr', '/ja/bootcamps' => 'ja',
            '/zh/help' => 'zh', '/de/contact' => 'de',
            '/km/courses' => 'km', '/ko/blog' => 'ko', '/vi/' => 'vi',
        ];

        foreach ($pages as $path => $language) {
            $this->get($path)->assertOk()->assertSee('<html lang="'.$language.'"', false);
        }

        $this->get('/fr/ebooks')->assertSee('href="'.url('/fr/bootcamps').'"', false)
            ->assertSee('href="'.url('/fr/help').'"', false)
            ->assertSee('href="'.url('/fr/contact').'"', false)
            ->assertSee('value="'.url('/ja/ebooks').'"', false);

        $base = KnowledgeBase::query()->create([
            'title' => 'Getting started', 'slug' => 'getting-started',
            'content' => 'Welcome to learning.', 'published' => true,
        ]);
        $base->topics()->create([
            'title' => 'Sign in', 'slug' => 'sign-in',
            'body' => 'Use your email and password.', 'sort_order' => 0,
        ]);

        $this->get('/fr/help/getting-started')->assertOk()
            ->assertSee('href="'.url('/fr/help/getting-started/sign-in').'"', false);
        $this->get('/fr/help/getting-started/sign-in')->assertOk()
            ->assertSee('Use your email and password.');
        $this->get('/fr/help?'.http_build_query(['q' => 'password']))->assertOk()
            ->assertSee('value="'.url('/ja/help').'?q=password"', false);
    }

    public function test_localized_contact_post_returns_to_selected_language(): void
    {
        $this->post('/de/contact', [
            'name' => 'International Student',
            'email' => 'international@example.test',
            'subject' => 'Course information',
            'message' => 'Could you share the next course schedule please?',
        ])->assertRedirect(url('/de/contact'));

        $this->assertDatabaseHas('contacts', ['email' => 'international@example.test', 'status' => 'new']);
        $this->post('/de/contact', ['name' => 'A', 'email' => 'invalid', 'message' => 'tiny'])
            ->assertSessionHasErrors(['email', 'message']);
        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_owner_can_set_default_language_and_customer_brand(): void
    {
        $admin = $this->account('admin');
        $this->actingAs($admin)->put('/admin/settings/general', [
            'site_name' => 'Global Learning Hub',
            'default_locale' => 'fr',
            'support_email' => 'help@example.test',
            'allow_registration' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'default_locale', 'value' => 'fr']);
        auth()->logout();

        $this->get('/')->assertOk()
            ->assertSee('<html lang="fr"', false)
            ->assertSee('Global Learning Hub')
            ->assertSee('Accueil');
        $this->get('/register')->assertOk()
            ->assertSee('value="fr" selected', false);
        $this->get('/fr/contact')->assertOk()->assertSee('Global Learning Hub');

        $this->actingAs($admin)->put('/admin/settings/general', [
            'site_name' => 'Wrong language',
            'default_locale' => 'xx',
        ])->assertSessionHasErrors('default_locale');

        $this->assertDatabaseMissing('settings', ['key' => 'site_name', 'value' => 'Wrong language']);
    }

    public function test_registration_and_account_preferences_keep_validated_member_language(): void
    {
        $this->get('/register?locale=ja')->assertOk()
            ->assertSee('value="ja" selected', false);
        $this->post('/register', [
            'name' => 'International Learner',
            'email' => 'learner@example.test',
            'password' => 'Password#12345',
            'password_confirmation' => 'Password#12345',
            'locale' => 'ja', 'role' => 'admin',
        ])->assertRedirect('/dashboard');

        $member = User::query()->where('email', 'learner@example.test')->firstOrFail();
        $this->assertSame('student', $member->role);
        $this->assertSame('ja', $member->locale);

        $this->get('/account/preferences')->assertOk()->assertSee('Preferred language');
        $this->put('/account/preferences', [
            'name' => 'Updated Learner', 'locale' => 'km',
            'role' => 'admin', 'email' => 'hacker@example.test',
        ])->assertRedirect(route('account.preferences.edit'));

        $this->assertSame('km', $member->fresh()->locale);
        $this->assertSame('Updated Learner', $member->fresh()->name);
        $this->assertSame('student', $member->fresh()->role);
        $this->assertSame('learner@example.test', $member->fresh()->email);
        $this->assertDatabaseHas('audit_events', ['action' => 'account.locale_updated']);
        $this->get('/')->assertOk()->assertSee('<html lang="km"', false);

        $this->put('/account/preferences', ['name' => 'New', 'locale' => '../en'])
            ->assertSessionHasErrors('locale');
        $this->assertSame('km', $member->fresh()->locale);
    }

    public function test_guest_cannot_change_account_preferences_and_preferred_locale_applies_to_course(): void
    {
        $this->get('/account/preferences')->assertRedirect(route('login'));
        $this->put('/account/preferences', ['name' => 'Imposter', 'locale' => 'fr'])
            ->assertRedirect(route('login'));

        $teacher = $this->account('instructor');
        $course = Course::query()->create([
            'instructor_id' => $teacher->getKey(),
            'title' => 'International Course',
            'slug' => 'international-course',
            'price' => '0.00',
            'status' => 'published',
            'visibility' => 'public',
            'published_at' => now(),
        ]);
        $student = User::query()->create([
            'name' => 'French Student', 'email' => 'french@example.test',
            'password' => 'Password#12345', 'locale' => 'fr',
        ]);

        $this->actingAs($student)->get('/courses/'.$course->slug)->assertOk()
            ->assertSee('<html lang="fr"', false)
            ->assertSee('International Course');
    }

    public function test_accessible_public_and_member_navigation_use_existing_assets(): void
    {
        $this->get('/fr/ebooks')->assertOk()
            ->assertSee('aria-controls="primary-navigation"', false)
            ->assertSee('id="primary-navigation"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('Ouvrir le menu')
            ->assertSee('Fermer le menu');

        $member = $this->account('student');
        $this->actingAs($member)->get('/dashboard')->assertOk()
            ->assertSee('aria-controls="panel-navigation"', false)
            ->assertSee('id="panel-navigation"', false)
            ->assertSee('Open dashboard menu');

        $javascript = (string) file_get_contents(resource_path('js/frontend/modules/language.js'));
        $this->assertStringContainsString("event.key === 'Escape'", $javascript);
        $this->assertStringContainsString("button.focus()", $javascript);
        $this->assertStringContainsString("setOpen(false)", $javascript);
        $this->assertStringContainsString("js-navigation", $javascript);
    }

    public function test_regional_money_formatting_never_converts_the_currency(): void
    {
        $this->assertSame('USD 1.234,50', \App\Helpers\Localization::money('1234.50', 'USD', 'de'));
        $this->assertSame('EUR 1'."\u{202F}".'234,50',
            \App\Helpers\Localization::money('1234.50', 'EUR', 'fr'));
        $this->assertSame('USD 1,234.50', \App\Helpers\Localization::money('1234.50', 'USD', 'en'));
        $this->assertSame('KHR 125,000', \App\Helpers\Localization::money('125000.00', 'KHR', 'km'));
        $this->assertSame('USD 125.00', \App\Helpers\Localization::money('125.00', 'USD', 'km'));
        $this->assertSame('KHR 125,000.50', \App\Helpers\Localization::money('125000.50', 'KHR', 'en'));
    }

    public function test_site_timezone_only_changes_display_not_original_instant(): void
    {
        $instant = \Carbon\CarbonImmutable::parse('2026-10-31 22:00:00', 'UTC');
        $admin = $this->account('admin');
        $this->actingAs($admin)->put('/admin/settings/general', [
            'site_name' => 'World Academy',
            'default_locale' => 'en',
            'site_timezone' => 'Asia/Tokyo',
            'allow_registration' => '1',
        ])->assertRedirect();

        $formatted = \App\Helpers\Localization::date($instant, 'en', true);
        $this->assertStringContainsString('1 Nov 2026', $formatted);
        $this->assertStringContainsString('07:00', $formatted);
        $this->assertSame('2026-10-31 22:00:00', $instant->format('Y-m-d H:i:s'));

        $this->put('/admin/settings/general', [
            'site_name' => 'Invalid Academy',
            'site_timezone' => '../../etc/passwd',
        ])->assertSessionHasErrors('site_timezone');
        $this->assertDatabaseMissing('settings', ['key' => 'site_name', 'value' => 'Invalid Academy']);
        $this->assertDatabaseHas('settings', ['key' => 'site_timezone', 'value' => 'Asia/Tokyo']);
    }

    public function test_translated_public_controls_and_currency_labels_are_rendered(): void
    {
        $this->get('/de/contact')->assertOk()
            ->assertSee('Nachricht senden')
            ->assertSee('Vollständiger Name');
        $this->get('/km/ebooks')->assertOk()->assertSee('សៀវភៅ និងមគ្គុទ្ទេសក៍');
        $this->get('/km/contact')->assertOk()->assertSee('ផ្ញើសារ');
        $this->get('/ja/bootcamps')->assertOk()->assertSee('集中講座');
        $this->get('/fr/courses')->assertOk()->assertSee('Rechercher des cours');
    }

}
