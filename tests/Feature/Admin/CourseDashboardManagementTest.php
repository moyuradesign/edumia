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
    public function test_instructor_filter_counts_and_sort_work_without_exposing_other_admin_data(): void
    {
        $admin = $this->member('admin', 'filter-admin@edumia.test');
        $a = $this->member('instructor', 'filter-a@edumia.test');
        $b = $this->member('instructor', 'filter-b@edumia.test');
        Course::query()->create([
            'instructor_id' => $a->id, 'title' => 'A Design Class',
            'slug' => 'a-design-class', 'status' => 'published', 'visibility' => 'public',
        ]);
        Course::query()->create([
            'instructor_id' => $b->id, 'title' => 'B Science Class',
            'slug' => 'b-science-class', 'status' => 'pending', 'visibility' => 'private',
        ]);
        $this->actingAs($admin)->get('/admin/courses')->assertOk()
            ->assertSee('Pending review')->assertSee('admin-course-instructor')
            ->assertSee('admin-course-sort')->assertSee('A Design Class')->assertSee('B Science Class');

        $this->get('/admin/courses?instructor='.$a->id.'&sort=title')->assertOk()
            ->assertSee('A Design Class')->assertDontSee('B Science Class')
            ->assertSee('value="'.$a->id.'" selected', false);
        $this->get('/admin/courses?instructor='.$b->id.'&status=pending')->assertOk()
            ->assertSee('B Science Class')->assertDontSee('A Design Class');
        $this->get('/admin/courses?instructor[]=7&sort[]=newest')->assertOk();
        $this->actingAs($b)->get('/admin/courses')->assertForbidden();
    }

    public function test_category_curriculum_and_status_totals_share_the_same_filter_scope(): void
    {
        $admin = $this->member('admin', 'category-filters-admin@edumia.test');
        $teacher = $this->member('instructor', 'category-filters-teacher@edumia.test');
        $design = \App\Models\Category::query()->create(['name' => 'Design', 'slug' => 'qa-design', 'status' => 'active']);
        $science = \App\Models\Category::query()->create(['name' => 'Science', 'slug' => 'qa-science', 'status' => 'active']);

        $pending = Course::query()->create([
            'instructor_id' => $teacher->id, 'category_id' => $design->id,
            'title' => 'Design Queue Course', 'slug' => 'design-queue-course',
            'status' => 'pending', 'visibility' => 'private',
        ]);
        Course::query()->create([
            'instructor_id' => $teacher->id, 'category_id' => $design->id,
            'title' => 'Design Draft Course', 'slug' => 'design-draft-course',
            'status' => 'draft', 'visibility' => 'private',
        ]);
        Course::query()->create([
            'instructor_id' => $teacher->id, 'category_id' => $science->id,
            'title' => 'Science Published Course', 'slug' => 'science-published-course',
            'status' => 'published', 'visibility' => 'public', 'published_at' => now(),
        ]);
        \App\Models\Lesson::query()->create([
            'course_id' => $pending->id, 'title' => 'First lesson',
            'slug' => 'first-design-lesson', 'content' => 'Introduction',
        ]);

        $this->actingAs($admin)->get('/admin/courses?category='.$design->id.'&content=with_lessons')
            ->assertOk()->assertSee('Design Queue Course')
            ->assertDontSee('Design Draft Course')->assertDontSee('Science Published Course')
            ->assertSee('admin-course-category')->assertSee('admin-course-content')
            ->assertSee('Status totals above reflect the current search')
            ->assertSee('href="'.route('admin.courses.index', [
                'status' => 'pending', 'instructor' => null, 'category' => $design->id,
                'content' => 'with_lessons', 'sort' => null,
            ]).'"', false);

        $this->get('/admin/courses?category='.$design->id.'&content=no_lessons')
            ->assertOk()->assertSee('Design Draft Course')->assertDontSee('Design Queue Course');

        $this->get('/admin/courses?status[]=invalid&category[]=999&content[]=no_lessons&sort[]=most_lessons')
            ->assertOk()->assertSee('Design Queue Course')->assertSee('Science Published Course');
    }

    public function test_sort_by_course_activity_does_not_change_access_permissions(): void
    {
        $admin = $this->member('admin', 'sort-activity-admin@edumia.test');
        $teacher = $this->member('instructor', 'sort-activity-teacher@edumia.test');
        $course = Course::query()->create([
            'instructor_id' => $teacher->id,
            'title' => 'Activity Ranking Course', 'slug' => 'activity-ranking-course',
            'status' => 'draft', 'visibility' => 'private',
        ]);

        $this->actingAs($admin)->get('/admin/courses?sort=most_lessons')->assertOk()
            ->assertSee('Activity Ranking Course')
            ->assertSee('value="most_lessons" selected', false)
            ->assertSee('Needs curriculum');
        $this->get('/admin/courses?sort=most_enrolled')->assertOk()
            ->assertSee('Activity Ranking Course');
        $this->actingAs($teacher)->get('/admin/courses?sort=most_enrolled')->assertForbidden();
    }

}
