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
}
