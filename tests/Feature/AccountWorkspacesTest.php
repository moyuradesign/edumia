<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AccountWorkspacesTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role, string $email): User
    {
        $user = User::create(['name' => ucfirst($role).' Member', 'email' => $email, 'password' => 'Password#12345']);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    public function test_guest_workspace_routes_require_signin(): void
    {
        foreach (['/account', '/account/courses', '/instructor', '/instructor/courses'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_student_account_has_premium_shell_and_live_navigation(): void
    {
        $student = $this->member('student', 'student-workspace@edumia.test');

        $this->actingAs($student)->get('/account')
            ->assertOk()
            ->assertSee('STUDENT ACCOUNT')
            ->assertSee('Welcome back, Student Member')
            ->assertSee('class="admin-shell" data-member-shell', false)
            ->assertSee('data-admin-search', false)
            ->assertSee('My ebooks')
            ->assertSee('My bootcamps')
            ->assertSee('Password & security')
            ->assertSee('href="'.route('account.courses').'"', false);

        $this->get('/account/courses')->assertOk()->assertSee('Course library');
        $this->get('/dashboard')->assertOk()->assertSee('My learning');
    }

    public function test_student_sees_only_their_enrollments_and_can_search(): void
    {
        $teacher = $this->member('instructor', 'teacher-workspace@edumia.test');
        $student = $this->member('student', 'student-courses@edumia.test');
        $other = $this->member('student', 'other-courses@edumia.test');

        $own = Course::create([
            'instructor_id' => $teacher->id, 'title' => 'My English Course', 'slug' => 'my-english-course',
            'status' => 'published', 'visibility' => 'public', 'published_at' => now(),
        ]);
        $foreign = Course::create([
            'instructor_id' => $teacher->id, 'title' => 'Private Math Course', 'slug' => 'private-math-course',
            'status' => 'published', 'visibility' => 'public', 'published_at' => now(),
        ]);
        Enrollment::create(['course_id' => $own->id, 'user_id' => $student->id, 'status' => 'active', 'progress_percent' => 30]);
        Enrollment::create(['course_id' => $foreign->id, 'user_id' => $other->id, 'status' => 'active', 'progress_percent' => 90]);

        $this->actingAs($student)->get('/account/courses?status=active&q=English')
            ->assertOk()->assertSee('My English Course')->assertDontSee('Private Math Course');
        $this->get('/account/courses?status=completed')->assertOk()->assertSee('No matching courses');
    }

    public function test_instructor_workspace_shows_only_owned_courses_and_real_management_links(): void
    {
        $teacher = $this->member('instructor', 'studio-teacher@edumia.test');
        $other = $this->member('instructor', 'studio-other@edumia.test');

        $own = Course::create(['instructor_id' => $teacher->id, 'title' => 'Original Teaching', 'slug' => 'original-teaching', 'status' => 'draft']);
        Course::create(['instructor_id' => $other->id, 'title' => 'Hidden Private Teaching', 'slug' => 'hidden-private-teaching', 'status' => 'draft']);

        $this->actingAs($teacher)->get('/instructor')->assertOk()
            ->assertSee('INSTRUCTOR STUDIO')->assertSee('Original Teaching')
            ->assertDontSee('Hidden Private Teaching')
            ->assertSee('href="'.route('instructor.courses.create').'"', false)
            ->assertSee('data-admin-search', false);

        $this->get('/instructor/courses?status=draft&q=Original')
            ->assertOk()->assertSee('Original Teaching')->assertDontSee('Hidden Private Teaching');
        $this->get('/instructor/courses?status=published')->assertOk()->assertSee('No matching courses');
        $this->get('/instructor/courses/'.$own->id.'/edit')->assertOk();
    }

    public function test_student_cannot_access_instructor_or_admin_workspaces(): void
    {
        $student = $this->member('student', 'restricted-student@edumia.test');
        $this->actingAs($student)->get('/instructor')->assertForbidden();
        $this->get('/instructor/courses')->assertForbidden();
        $this->get('/admin/release-readiness')->assertForbidden();
    }

    public function test_administrator_dashboard_remains_unchanged(): void
    {
        $admin = $this->member('admin', 'admin-still@edumia.test');
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('ADMIN CONSOLE');
    }

    public function test_unknown_application_url_has_branded_focused_404_page(): void
    {
        $this->get('/this-page-should-not-exist')->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('404 · Not found')
            ->assertSee('Go to homepage')
            ->assertSee('Browse courses')
            ->assertDontSee('nginx');
    }
    public function test_all_account_sidebars_have_distinct_accessible_svg_icons(): void
    {
        $accounts = [
            ['admin', 'nav-owner@edumia.test', '/dashboard', 20],
            ['instructor', 'nav-teacher@edumia.test', '/instructor', 12],
            ['student', 'nav-learner@edumia.test', '/account', 12],
        ];

        foreach ($accounts as [$role, $email, $path, $minimum]) {
            $user = $this->member($role, $email);
            $html = (string) $this->actingAs($user)->get($path)->assertOk()->getContent();

            preg_match_all('/<svg[^>]*data-nav-icon="([^"]+)"[^>]*>(.*?)<\\/svg>/s', $html, $matches);
            $this->assertGreaterThanOrEqual($minimum, count($matches[1]), $role.' menu SVG count');
            $this->assertCount(count(array_unique($matches[1])), $matches[1], $role.' must not reuse menu icons');
            $this->assertCount(count(array_unique($matches[2])), $matches[2], $role.' must have unique SVG artwork');
            foreach ($matches[2] as $art) {
                $this->assertNotSame('', trim($art), 'Menu icon must have a visible SVG glyph.');
            }
        }
    }

    public function test_account_page_headers_omit_redundant_overview_links(): void
    {
        $admin = $this->member('admin', 'nav-pages-admin@edumia.test');
        foreach ([
            '/admin/courses', '/admin/users', '/admin/settings/general',
            '/admin/release-readiness', '/admin/media', '/admin/tickets',
        ] as $path) {
            $response = $this->actingAs($admin)->get($path);
            $response->assertOk()->assertDontSee('Back to overview');
            // Sidebar overview navigation remains available for deliberate navigation.
            $response->assertSee('data-admin-nav-link', false);
        }

        $this->get('/admin/users')->assertSee('Staff roles');
        $this->get('/admin/media')->assertSee('Upload media');

        $student = $this->member('student', 'nav-certificates@edumia.test');
        $this->actingAs($student)->get('/account/certificates')
            ->assertOk()->assertDontSee('Back to overview');
    }

    public function test_account_preferences_show_own_activity_and_audit_name_changes(): void
    {
        $member = $this->member('student', 'account-member@edumia.test');
        $this->actingAs($member)->get('/account/preferences')->assertOk()
            ->assertSee('My enrollments')
            ->assertSee('Support tickets')
            ->assertSee('account-member@edumia.test')
            ->assertSee('form="account-preferences-form"', false);
        $this->put('/account/preferences', ['name' => '  Updated Learner  ', 'locale' => 'km'])
            ->assertRedirect(route('account.preferences.edit'));
        $this->assertSame('Updated Learner', $member->fresh()->name);
        $this->assertSame('km', $member->fresh()->locale);
        $this->assertDatabaseHas('audit_events', [
            'actor_id' => $member->id, 'action' => 'account.name_updated',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'actor_id' => $member->id, 'action' => 'account.locale_updated',
        ]);
        $this->put('/account/preferences', ['name' => '    ', 'locale' => 'en'])
            ->assertSessionHasErrors('name');
        $this->assertSame('Updated Learner', $member->fresh()->name);
    }

}
