<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CourseFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role, string $email): User
    {
        $user = User::create([
            'name' => ucfirst($role),
            'email' => $email,
            'password' => 'Password#12345',
        ]);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    private function course(User $teacher): Course
    {
        return Course::create([
            'instructor_id' => $teacher->getKey(),
            'title' => 'Reviewable Learning',
            'slug' => 'reviewable-learning',
            'status' => 'published',
            'published_at' => now(),
            'visibility' => 'public',
            'price' => 0,
        ]);
    }

    public function test_saved_courses_are_private_and_removable(): void
    {
        $teacher = $this->member('instructor', 'teacher@edumia.test');
        $student = $this->member('student', 'student@edumia.test');
        $other = $this->member('student', 'other@edumia.test');
        $course = $this->course($teacher);

        $this->actingAs($student)
            ->post('/saved-courses/'.$course->id)->assertRedirect();
        $this->assertDatabaseHas('wishlists', ['user_id' => $student->getKey(), 'course_id' => $course->getKey()]);
        $this->get('/saved-courses')->assertOk()->assertSee('Reviewable Learning');
        $this->post('/saved-courses/'.$course->id)->assertRedirect();
        $this->assertDatabaseCount('wishlists', 1);

        $this->actingAs($other)->get('/saved-courses')->assertOk()->assertDontSee('Reviewable Learning');
        $this->actingAs($student)
            ->delete('/saved-courses/'.$course->id)->assertRedirect();
        $this->assertDatabaseCount('wishlists', 0);
    }

    public function test_only_enrolled_students_can_review_and_approval_controls_visibility(): void
    {
        $teacher = $this->member('instructor', 'teacher@edumia.test');
        $student = $this->member('student', 'student@edumia.test');
        $admin = $this->member('admin', 'admin@edumia.test');
        $course = $this->course($teacher);

        $review = [
            'rating' => 5,
            'title' => 'A helpful course',
            'body' => 'The lessons were well organized and practical.',
        ];

        $this->actingAs($student)
            ->post('/courses/'.$course->id.'/reviews', $review)->assertForbidden();

        $this->post('/courses/'.$course->id.'/enroll')->assertRedirect();
        $this->post('/courses/'.$course->id.'/reviews', $review)->assertRedirect();

        $saved = Review::firstOrFail();
        $this->assertSame('pending', $saved->status);

        auth()->logout();
        $this->get('/courses/'.$course->slug)->assertOk()->assertDontSee('A helpful course');

        $this->actingAs($admin)
            ->get('/admin/reviews')->assertOk()->assertSee('A helpful course');
        $this->patch('/admin/reviews/'.$saved->id, ['status' => 'approved'])
            ->assertRedirect(route('admin.reviews.index'));
        $this->assertDatabaseHas('audit_events', ['action' => 'reviews.moderated', 'subject_id' => $saved->id]);

        auth()->logout();
        $this->get('/courses/'.$course->slug)->assertOk()->assertSee('A helpful course');

        $this->actingAs($student)->post('/courses/'.$course->id.'/reviews', [
            'rating' => 4,
            'title' => 'Updated experience',
            'body' => 'The material is helpful but could be longer.',
        ])->assertRedirect();

        $this->assertDatabaseCount('reviews', 1);
        $this->assertSame('pending', $saved->fresh()->status);
        auth()->logout();
        $this->get('/courses/'.$course->slug)->assertOk()->assertDontSee('Updated experience');
    }

    public function test_review_validation_and_html_escaping(): void
    {
        $teacher = $this->member('instructor', 'teacher@edumia.test');
        $student = $this->member('student', 'student@edumia.test');
        $admin = $this->member('admin', 'admin@edumia.test');
        $course = $this->course($teacher);

        $this->actingAs($student)->post('/courses/'.$course->id.'/enroll')->assertRedirect();
        $this->post('/courses/'.$course->id.'/reviews', [
            'rating' => 8,
            'body' => 'A valid length for the comment.',
        ])->assertSessionHasErrors('rating');

        $this->post('/courses/'.$course->id.'/reviews', [
            'rating' => 5,
            'title' => '<script>alert(1)</script>',
            'body' => '<script>alert("unsafe")</script> A full review.',
        ])->assertRedirect();

        $review = Review::firstOrFail();
        $this->actingAs($admin)
            ->patch('/admin/reviews/'.$review->id, ['status' => 'approved'])->assertRedirect();

        auth()->logout();
        $this->get('/courses/'.$course->slug)
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_students_cannot_moderate_reviews(): void
    {
        $teacher = $this->member('instructor', 'teacher@edumia.test');
        $student = $this->member('student', 'student@edumia.test');
        $course = $this->course($teacher);
        $review = Review::create([
            'course_id' => $course->getKey(),
            'user_id' => $student->getKey(),
            'rating' => 4,
            'body' => 'A public review that needs moderation.',
            'status' => 'pending',
        ]);

        $this->actingAs($student)
            ->get('/admin/reviews')->assertForbidden();
        $this->patch('/admin/reviews/'.$review->id, ['status' => 'approved'])->assertForbidden();

        $this->assertSame('pending', $review->fresh()->status);
    }
}
