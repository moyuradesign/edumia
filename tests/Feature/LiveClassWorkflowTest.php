<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LiveClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LiveClassWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role, string $email): User
    {
        $user = User::create(['name' => $role, 'email' => $email, 'password' => 'Password#12345']);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    private function course(User $teacher, string $slug): Course
    {
        return Course::create([
            'instructor_id' => $teacher->getKey(),
            'title' => 'Teaching Online',
            'slug' => $slug,
            'status' => 'published',
            'visibility' => 'public',
            'published_at' => now(),
            'price' => 0,
        ]);
    }

    private function schedule(): array
    {
        return [
            'title' => 'Online Workshop',
            'provider' => 'zoom',
            'meeting_url' => 'https://example.test/meeting/private-room',
            'starts_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(2)->addHour()->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
        ];
    }

    public function test_instructor_schedules_private_session_visible_only_to_enrolled_learner(): void
    {
        $teacher = $this->member('instructor', 'teacher@edumia.test');
        $student = $this->member('student', 'student@edumia.test');
        $course = $this->course($teacher, 'online-workshop');

        $this->actingAs($teacher)->get('/instructor/courses/'.$course->id.'/live-classes')
            ->assertOk()->assertSee('Schedule a class');
        $this->post('/instructor/courses/'.$course->id.'/live-classes', $this->schedule())
            ->assertRedirect(route('instructor.live-classes.index', $course));

        $class = LiveClass::firstOrFail();
        $this->assertSame('scheduled', $class->status);
        $this->assertDatabaseHas('audit_events', ['action' => 'live_classes.created']);

        auth()->logout();
        $this->get('/courses/'.$course->slug)->assertOk()->assertDontSee('private-room');
        $this->actingAs($student)->get('/learn/'.$course->id.'/live-classes')->assertNotFound();

        $this->post('/courses/'.$course->id.'/enroll')->assertRedirect();
        $this->get('/learn/'.$course->id.'/live-classes')
            ->assertOk()->assertSee('Online Workshop')
            ->assertSee('private-room');

        $this->actingAs($teacher)->put(
            '/instructor/courses/'.$course->id.'/live-classes/'.$class->id,
            array_replace($this->schedule(), ['status' => 'cancelled'])
        )->assertRedirect();
        $this->actingAs($student)->get('/learn/'.$course->id.'/live-classes')
            ->assertOk()->assertDontSee('private-room');
    }

    public function test_student_cannot_create_session_or_other_instructor_edit_it(): void
    {
        $teacher = $this->member('instructor', 'teacher@edumia.test');
        $other = $this->member('instructor', 'other@edumia.test');
        $student = $this->member('student', 'student@edumia.test');
        $course = $this->course($teacher, 'owned-course');
        $differentCourse = $this->course($other, 'other-course');

        $this->actingAs($teacher)
            ->post('/instructor/courses/'.$course->id.'/live-classes', $this->schedule())->assertRedirect();

        $class = LiveClass::firstOrFail();
        $this->actingAs($student)->post('/instructor/courses/'.$course->id.'/live-classes', $this->schedule())
            ->assertForbidden();
        $this->actingAs($other)
            ->get('/instructor/courses/'.$course->id.'/live-classes')->assertForbidden();
        $this->put('/instructor/courses/'.$course->id.'/live-classes/'.$class->id, $this->schedule())
            ->assertForbidden();

        $this->actingAs($teacher)
            ->put('/instructor/courses/'.$differentCourse->id.'/live-classes/'.$class->id, $this->schedule())
            ->assertForbidden();
    }

    public function test_invalid_scheme_and_end_before_start_are_rejected(): void
    {
        $teacher = $this->member('instructor', 'teacher@edumia.test');
        $course = $this->course($teacher, 'schedule-validator');

        $this->actingAs($teacher)->post('/instructor/courses/'.$course->id.'/live-classes',
            array_replace($this->schedule(), [
                'meeting_url' => 'javascript:alert(1)',
                'ends_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ]))->assertSessionHasErrors(['meeting_url', 'ends_at']);

        $this->assertDatabaseCount('live_classes', 0);
    }
}
