<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\Localization;
use App\Models\Category;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HeaderFooterExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function instructor(): User
    {
        $user = User::query()->create([
            'name' => 'Course Teacher',
            'email' => 'teacher-header@example.test',
            'password' => 'Strong#Password123',
        ]);
        $user->forceFill(['role' => 'instructor', 'status' => 'active'])->save();

        return $user;
    }

    private function course(User $teacher, ?Category $category, string $slug, bool $published = true): Course
    {
        return Course::query()->create([
            'instructor_id' => $teacher->getKey(),
            'category_id' => $category?->getKey(),
            'title' => 'Learning '.$slug,
            'slug' => $slug,
            'summary' => 'Interesting learning for everyone',
            'price' => '25.00',
            'currency_code' => 'USD',
            'status' => $published ? 'published' : 'draft',
            'visibility' => $published ? 'public' : 'private',
            'published_at' => $published ? now()->subMinute() : null,
        ]);
    }

    public function test_header_has_logo_search_categories_cart_account_and_footer_keeps_newsletter(): void
    {
        foreach (['/', '/fr/courses', '/km/ebooks'] as $path) {
            $this->get($path)->assertOk()
                ->assertSee('data-premium-header', false)
                ->assertSee('class="premium-header-search"', false)
                ->assertSee('id="primary-navigation"', false)
                ->assertSee('aria-controls="primary-navigation"', false)
                ->assertSee('data-header-details="categories"', false)
                ->assertSee('data-header-details="profile"', false)
                ->assertSee('href="'.route('cart.index').'"', false)
                ->assertSee('class="site-footer premium-footer"', false)
                ->assertSee('name="consent"', false)
                ->assertSee('action="'.route('newsletter.subscribe').'"', false)
                ->assertSee('id="language-select"', false);
        }

        $this->get('/fr/courses')->assertSee('Catégories')->assertSee('Panier');
        $this->get('/km/ebooks')->assertSee('ប្រភេទវគ្គសិក្សា')->assertSee('កន្ត្រក');
        $this->get('/login')->assertOk()->assertDontSee('data-premium-header');
    }

    public function test_mega_menu_uses_real_active_categories_with_public_courses_only(): void
    {
        $teacher = $this->instructor();
        $active = Category::query()->create([
            'name' => 'Web Development', 'slug' => 'web-development',
            'status' => 'active', 'sort_order' => 1,
        ]);
        $inactive = Category::query()->create([
            'name' => 'Inactive Learning', 'slug' => 'inactive-learning',
            'status' => 'inactive', 'sort_order' => 2,
        ]);
        $draft = Category::query()->create([
            'name' => 'Secret Drafts', 'slug' => 'secret-drafts',
            'status' => 'active', 'sort_order' => 3,
        ]);
        $this->course($teacher, $active, 'published-web');
        $this->course($teacher, $inactive, 'unlisted-web');
        $this->course($teacher, $draft, 'draft-web', false);

        $html = (string) $this->get('/')->assertOk()
            ->assertSee('Web Development')
            ->assertSee('category=web-development', false)
            ->getContent();
        $start = strpos($html, 'class="premium-dropdown premium-mega-menu"');
        $this->assertNotFalse($start);
        $end = strpos($html, '</details>', $start);
        $this->assertNotFalse($end);
        $megaMenu = substr($html, $start, $end - $start);
        $this->assertStringNotContainsString('Inactive Learning', $megaMenu);
        $this->assertStringNotContainsString('Secret Drafts', $megaMenu);

        $this->get('/courses?category=web-development')->assertOk()
            ->assertSee('Learning published-web')
            ->assertDontSee('Learning unlisted-web')
            ->assertDontSee('Learning draft-web');

        $this->get('/courses?category=inactive-learning')->assertOk()
            ->assertDontSee('Learning unlisted-web');

        $this->get('/courses?category[]=invalid')->assertOk();
    }

    public function test_session_cart_deduplicates_limits_visibility_and_does_not_make_orders(): void
    {
        $teacher = $this->instructor();
        $public = $this->course($teacher, null, 'cart-eligible');
        $draft = $this->course($teacher, null, 'cart-private', false);
        $this->get('/cart')->assertOk()->assertSee('Your cart is empty');

        $this->post(route('cart.items.store', $draft))->assertNotFound();
        $this->assertSame(0, Order::query()->count());

        $this->post(route('cart.items.store', $public))
            ->assertRedirect(route('cart.index'));
        $this->post(route('cart.items.store', $public))
            ->assertRedirect(route('cart.index'));

        $this->get('/cart')->assertOk()
            ->assertSee('Learning cart-eligible')
            ->assertDontSee('Your cart is empty')
            ->assertSee('href="'.route('login', ['locale' => 'en']).'"', false)
            ->assertSee('name="_method" value="DELETE"', false);
        $this->assertSame([(int) $public->getKey()], session('cart_course_ids'));
        $this->assertDatabaseCount('orders', 0);

        $this->get('/cart?locale=fr')->assertOk()->assertSee('<html lang="fr"', false);

        $this->delete(route('cart.items.destroy', $public))->assertRedirect(route('cart.index'));
        $this->get('/cart')->assertOk()->assertSee('Your cart is empty');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_authenticated_dropdown_contains_real_account_and_logout_routes(): void
    {
        $member = User::query()->create([
            'name' => 'Member Learner',
            'email' => 'member-menu@example.test',
            'password' => 'Strong#Password123',
        ]);
        $member->forceFill(['role' => 'student', 'status' => 'active'])->save();

        $this->actingAs($member)->get('/')->assertOk()
            ->assertSee('Member Learner')
            ->assertSee('href="'.route('dashboard').'"', false)
            ->assertSee('href="'.route('account.preferences.edit').'"', false)
            ->assertSee('href="'.route('checkout.orders.index').'"', false)
            ->assertSee('action="'.route('logout').'"', false);

        $this->get('/cart')->assertOk()->assertSee('Cart');
        $this->get('/fr/ebooks')->assertOk()->assertSee('value="'.Localization::url('ja', 'ebooks').'"', false);
    }
}
