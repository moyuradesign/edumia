<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Forum;
use App\Models\ForumReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CourseForumWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        $actor = User::create(['name' => $role, 'email' => $email, 'password' => 'Password#12345']);
        $actor->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $actor;
    }

    private function course(User $teacher, string $slug): Course
    {
        return Course::create([
            'instructor_id' => $teacher->getKey(),
            'title' => 'Course discussions',
            'slug' => $slug,
            'price' => 0,
            'status' => 'published',
            'visibility' => 'public',
            'published_at' => now(),
        ]);
    }

    public function test_enrolled_students_can_post_replies_and_admin_can_moderate(): void
    {
        $teacher = $this->user('instructor', 'teacher@edumia.test');
        $student = $this->user('student', 'student@edumia.test');
        $admin = $this->user('admin', 'admin@edumia.test');
        $course = $this->course($teacher, 'discussion-course');

        $this->actingAs($student)->get('/learn/'.$course->id.'/forums')->assertNotFound();
        $this->post('/courses/'.$course->id.'/enroll')->assertRedirect();
        $this->get('/learn/'.$course->id.'/forums')->assertOk()->assertSee('Start a discussion');

        $this->post('/learn/'.$course->id.'/forums', [
            'title' => 'Understanding the lesson',
            'body' => 'Can someone explain the assignment requirements?',
        ])->assertRedirect();

        $forum = Forum::firstOrFail();
        $this->get('/learn/'.$course->id.'/forums/'.$forum->id)->assertOk()
            ->assertSee('Understanding the lesson');

        $this->post('/learn/'.$course->id.'/forums/'.$forum->id.'/replies', [
            'body' => 'I have tried the following approach.',
        ])->assertRedirect();
        $reply = ForumReply::firstOrFail();

        $this->actingAs($admin)->get('/admin/forums')->assertOk()
            ->assertSee('Understanding the lesson');
        $this->patch('/admin/forum-replies/'.$reply->id, ['status' => 'hidden'])
            ->assertRedirect();
        $this->assertDatabaseHas('audit_events', ['action' => 'forum_replies.moderated']);

        $this->actingAs($student)->get('/learn/'.$course->id.'/forums/'.$forum->id)
            ->assertOk()->assertDontSee('I have tried the following approach.');

        $this->actingAs($admin)->patch('/admin/forums/'.$forum->id, ['status' => 'closed'])
            ->assertRedirect();
        $this->actingAs($student)->post('/learn/'.$course->id.'/forums/'.$forum->id.'/replies', [
            'body' => 'Another reply to the closed discussion.',
        ])->assertStatus(422);

        $this->actingAs($admin)->patch('/admin/forums/'.$forum->id, ['status' => 'hidden'])
            ->assertRedirect();
        $this->actingAs($student)->get('/learn/'.$course->id.'/forums/'.$forum->id)
            ->assertNotFound();
    }

    public function test_other_course_learner_cannot_read_or_reply_to_private_forum(): void
    {
        $teacher = $this->user('instructor', 'teacher@edumia.test');
        $owner = $this->user('student', 'owner@edumia.test');
        $outsider = $this->user('student', 'outsider@edumia.test');
        $course = $this->course($teacher, 'private-course');

        $this->actingAs($owner)->post('/courses/'.$course->id.'/enroll')->assertRedirect();
        $this->post('/learn/'.$course->id.'/forums', [
            'title' => 'Private conversation',
            'body' => 'Only learners enrolled in this course can read this.',
        ])->assertRedirect();
        $forum = Forum::firstOrFail();

        $this->actingAs($outsider)->get('/learn/'.$course->id.'/forums')->assertNotFound();
        $this->get('/learn/'.$course->id.'/forums/'.$forum->id)->assertNotFound();
        $this->post('/learn/'.$course->id.'/forums/'.$forum->id.'/replies', [
            'body' => 'Trying to access the private course thread.',
        ])->assertNotFound();

        $this->assertDatabaseCount('forum_replies', 0);
    }

    public function test_message_is_escaped_and_student_cannot_moderate(): void
    {
        $teacher = $this->user('instructor', 'teacher@edumia.test');
        $student = $this->user('student', 'student@edumia.test');
        $course = $this->course($teacher, 'safe-forum');

        $this->actingAs($student)->post('/courses/'.$course->id.'/enroll')->assertRedirect();
        $this->post('/learn/'.$course->id.'/forums', [
            'title' => 'Safe discussion',
            'body' => '<script>alert("x")</script> I have a question.',
        ])->assertRedirect();
        $forum = Forum::firstOrFail();

        $this->get('/learn/'.$course->id.'/forums/'.$forum->id)
            ->assertOk()->assertDontSee('<script>alert("x")</script>', false)
            ->assertSee('&lt;script&gt;', false);
        $this->patch('/admin/forums/'.$forum->id, ['status' => 'hidden'])->assertForbidden();
        $this->assertSame('open', $forum->fresh()->status);
    }
}
