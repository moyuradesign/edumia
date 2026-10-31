<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LearningReportsTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role, string $email): User
    {
        $user = User::create(['name' => $role, 'email' => $email, 'password' => 'Password#12345']);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    private function course(User $teacher, string $slug, string $title): Course
    {
        return Course::create([
            'instructor_id' => $teacher->getKey(),
            'title' => $title,
            'slug' => $slug,
            'price' => 0,
            'visibility' => 'public',
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function test_admin_sees_platform_learning_metrics(): void
    {
        $admin = $this->actor('admin', 'admin@edumia.test');
        $teacher = $this->actor('instructor', 'teacher@edumia.test');
        $student = $this->actor('student', 'student@edumia.test');
        $course = $this->course($teacher, 'leadership', 'Leadership Academy');

        Enrollment::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 'completed',
            'progress_percent' => 100,
            'enrolled_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)->get('/admin/reports/learning')
            ->assertOk()
            ->assertSee('Leadership Academy')
            ->assertSee('Completed')
            ->assertSee('Learning reports');
    }

    public function test_instructor_reports_only_own_courses(): void
    {
        $teacher = $this->actor('instructor', 'teacher@edumia.test');
        $otherTeacher = $this->actor('instructor', 'other@edumia.test');
        $student = $this->actor('student', 'student@edumia.test');

        $owned = $this->course($teacher, 'first-course', 'My Teaching Course');
        $this->course($otherTeacher, 'second-course', 'Private Other Course');
        Enrollment::create([
            'course_id' => $owned->id,
            'user_id' => $student->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $this->actingAs($teacher)->get('/instructor/reports')
            ->assertOk()->assertSee('My Teaching Course')
            ->assertDontSee('Private Other Course');

        $this->actingAs($student)->get('/instructor/reports')->assertForbidden();
        $this->get('/admin/reports/learning')->assertForbidden();
    }
}
