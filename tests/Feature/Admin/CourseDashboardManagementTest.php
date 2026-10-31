<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CourseDashboardManagementTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role, string $email): User
    {
        $user = User::query()->create([
            'name' => 'Platform Member', 'email' => $email,
            'password' => 'Strong#Password123',
        ]);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();
        return $user;
    }

    public function test_administrator_sees_published_pending_and_draft_courses_with_real_filters(): void
    {
        $admin = $this->member('admin', 'admin-courses@example.test');
        $teacher = $this->member('instructor', 'teacher-courses@example.test');
        foreach (['published', 'pending', 'draft'] as $status) {
            Course::query()->create([
                'instructor_id' => $teacher->id,
                'title' => 'Course '.$status,
                'slug' => 'admin-course-'.$status,
                'status' => $status, 'visibility' => $status === 'published' ? 'public' : 'private',
                'published_at' => $status === 'published' ? now() : null,
                'summary' => 'Learning content',
                'price' => '0.00',
            ]);
        }

        $this->actingAs($admin)->get('/admin/courses')->assertOk()
            ->assertSee('Course published')
            ->assertSee('Course pending')
            ->assertSee('Course draft')
            ->assertSee('admin-course-query', false)
            ->assertSee('admin-course-status', false)
            ->assertSee('href="'.route('admin.courses.index').'"', false);

        $this->get('/admin/courses?status=pending')->assertOk()
            ->assertSee('Course pending')
            ->assertDontSee('Course published')
            ->assertDontSee('Course draft');

        $this->get('/admin/courses?q=published')->assertOk()
            ->assertSee('Course published')
            ->assertDontSee('Course draft');

        $this->get('/admin/courses?status[]=invalid&q[]=invalid')->assertOk();
        $this->get('/dashboard')->assertOk()
            ->assertSee('href="'.route('admin.courses.index').'"', false)
            ->assertSee('href="'.route('admin.courses.index', ['status' => 'pending']).'"', false);
    }

    public function test_course_admin_listing_is_for_administrators_only(): void
    {
        $student = $this->member('student', 'student-courses@example.test');
        $this->get('/admin/courses')->assertRedirect(route('login'));
        $this->actingAs($student)->get('/admin/courses')->assertForbidden();
    }
}
