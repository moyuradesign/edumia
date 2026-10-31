<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\Localization;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HomepageExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_three_accessible_slides_and_fallback_without_courses(): void
    {
        foreach (['/' => 'en', '/fr/' => 'fr', '/km/' => 'km'] as $url => $locale) {
            $response = $this->get($url)->assertOk()
                ->assertSee('data-hero-slider', false)
                ->assertSee('data-hero-track', false)
                ->assertSee('data-hero-prev', false)
                ->assertSee('data-hero-next', false)
                ->assertSee('data-hero-pause', false)
                ->assertSee('aria-roledescription="carousel"', false)
                ->assertSee('class="home-course-grid"', false)
                ->assertSee(Localization::label('featured', $locale))
                ->assertSee(Localization::label('no_courses', $locale));

            $html = (string) $response->getContent();
            $this->assertSame(3, substr_count($html, 'data-hero-slide role="group"'));
            $this->assertSame(3, substr_count($html, 'data-hero-dot="'));
            $this->assertSame(0, substr_count($html, 'class="home-course-card"'));
            $this->assertSame(1, substr_count($html, '<h1>'));
        }
    }

    public function test_only_latest_twelve_published_public_courses_appear_as_vertical_cards(): void
    {
        $instructor = User::query()->create([
            'name' => 'International Instructor',
            'email' => 'homepage-instructor@example.test',
            'password' => 'Strong#Password123',
        ]);
        $instructor->forceFill(['role' => 'instructor', 'status' => 'active'])->save();

        foreach (range(1, 14) as $number) {
            Course::query()->create([
                'instructor_id' => $instructor->getKey(),
                'title' => 'Published course '.$number,
                'slug' => 'published-course-'.$number,
                'summary' => 'Practical lessons for all learners '.$number,
                'price' => $number === 1 ? '0.00' : '25.50',
                'currency_code' => 'USD',
                'status' => 'published',
                'visibility' => 'public',
                'published_at' => now()->subMinutes($number),
            ]);
        }
        Course::query()->create([
            'instructor_id' => $instructor->getKey(),
            'title' => 'Do not display draft',
            'slug' => 'private-draft',
            'summary' => 'This draft must never appear publicly.',
            'price' => '0.00',
            'status' => 'draft',
            'visibility' => 'private',
        ]);

        $response = $this->get('/')->assertOk()
            ->assertSee('Published course 1')
            ->assertSee('Published course 12')
            ->assertDontSee('Published course 13')
            ->assertDontSee('Published course 14')
            ->assertDontSee('Do not display draft')
            ->assertSee('href="'.Localization::url('en', 'courses/published-course-1').'"', false)
            ->assertSee(Localization::label('free', 'en'))
            ->assertSee(Localization::money('25.50', 'USD', 'en'));

        $html = (string) $response->getContent();
        $this->assertSame(12, substr_count($html, 'class="home-course-card"'));
        $this->assertSame(12, substr_count($html, 'home-course-cover home-course-cover--'));
        $this->assertSame(1, substr_count($html, '<h1>'));
    }

    public function test_homepage_notice_is_escaped_and_course_data_is_not_required_for_slider(): void
    {
        $this->get('/?q=%3Cscript%3E')->assertOk()
            ->assertSee('data-hero-slider')
            ->assertSee('href="'.Localization::url('en', 'bootcamps').'"', false)
            ->assertSee('href="'.Localization::url('en', 'ebooks').'"', false);
    }
}
