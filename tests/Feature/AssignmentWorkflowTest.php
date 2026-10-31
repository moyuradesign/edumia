<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\SubmittedAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AssignmentWorkflowTest extends TestCase
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

    private function course(User $teacher, string $slug = 'course-one'): Course
    {
        return Course::create([
            'instructor_id' => $teacher->id,
            'title' => 'Edumia Class',
            'slug' => $slug,
            'status' => 'published',
            'published_at' => now(),
            'visibility' => 'public',
            'price' => 0,
        ]);
    }

    private function assignment(Course $course, array $overrides = []): Assignment
    {
        return $course->assignments()->create(array_replace([
            'title' => 'Reflection',
            'instructions' => 'Describe how you learned this topic.',
            'max_score' => 100,
            'published' => true,
        ], $overrides));
    }

    public function test_complete_instructor_student_submission_and_grading_workflow(): void
    {
        $teacher = $this->member('instructor', 'teacher@edumia.test');
        $student = $this->member('student', 'student@edumia.test');
        $course = $this->course($teacher);

        $this->actingAs($teacher)->post('/instructor/courses/'.$course->id.'/assignments', [
            'title' => 'Reflection',
            'instructions' => 'Describe what you have learned.',
            'max_score' => 100,
            'published' => 1,
        ])->assertRedirect(route('instructor.courses.edit', $course));

        $assignment = Assignment::firstOrFail();
        $this->get('/instructor/courses/'.$course->id.'/edit')->assertOk()->assertSee('Reflection');
        $this->get('/instructor/courses/'.$course->id.'/assignments/'.$assignment->id.'/gradebook')
            ->assertOk()->assertSee('No learner submissions');

        $this->actingAs($student)
            ->get('/learn/'.$course->id.'/assignments/'.$assignment->id)->assertForbidden();

        $this->post('/courses/'.$course->id.'/enroll')->assertRedirect();
        $this->get('/learn/'.$course->id)->assertOk()->assertSee('Reflection');

        $this->post('/learn/'.$course->id.'/assignments/'.$assignment->id, [
            'response' => 'I learned to organize my study sessions.',
        ])->assertRedirect();

        $submission = SubmittedAssignment::firstOrFail();
        $this->assertSame($student->id, $submission->user_id);

        $this->post('/learn/'.$course->id.'/assignments/'.$assignment->id, [
            'response' => 'I learned to organize my study sessions with a schedule.',
        ])->assertRedirect();
        $this->assertDatabaseCount('submitted_assignments', 1);

        $this->actingAs($teacher)
            ->put('/instructor/courses/'.$course->id.'/assignments/'.$assignment->id.'/submissions/'.$submission->id.'/grade', [
                'score' => 86.5,
                'feedback' => 'Clear observations and examples.',
            ])->assertRedirect(route('instructor.assignments.gradebook', [$course, $assignment]));

        $this->assertDatabaseHas('submitted_assignments', [
            'id' => $submission->id,
            'score' => 86.5,
            'graded_by' => $teacher->id,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'action' => 'assignments.graded',
            'subject_id' => $submission->id,
        ]);

        $this->actingAs($student)
            ->get('/learn/'.$course->id.'/assignments/'.$assignment->id)
            ->assertOk()->assertSee('Clear observations and examples.');

        $this->post('/learn/'.$course->id.'/assignments/'.$assignment->id, [
            'response' => 'Trying to replace a graded response.',
        ])->assertSessionHasErrors('response');
    }

    public function test_student_cannot_access_other_students_or_unpublished_assignments(): void
    {
        $teacher = $this->member('instructor', 'teacher@edumia.test');
        $owner = $this->member('student', 'owner@edumia.test');
        $other = $this->member('student', 'other@edumia.test');
        $course = $this->course($teacher);
        $privateAssignment = $this->assignment($course, ['published' => false]);

        $this->actingAs($owner)->post('/courses/'.$course->id.'/enroll')->assertRedirect();
        $this->get('/learn/'.$course->id.'/assignments/'.$privateAssignment->id)->assertNotFound();
        $this->post('/learn/'.$course->id.'/assignments/'.$privateAssignment->id, [
            'response' => 'A response to a private assignment.',
        ])->assertNotFound();

        $privateAssignment->update(['published' => true]);
        $this->actingAs($other)
            ->post('/learn/'.$course->id.'/assignments/'.$privateAssignment->id, [
                'response' => 'A response by an unenrolled student.',
            ])->assertForbidden();
        $this->assertDatabaseCount('submitted_assignments', 0);
    }

    public function test_course_ownership_and_cross_course_grading_are_enforced(): void
    {
        $teacher = $this->member('instructor', 'teacher@edumia.test');
        $anotherTeacher = $this->member('instructor', 'another@edumia.test');
        $course = $this->course($teacher);
        $otherCourse = $this->course($anotherTeacher, 'another-course');
        $assignment = $this->assignment($course);

        $this->actingAs($anotherTeacher)
            ->get('/instructor/courses/'.$course->id.'/assignments/'.$assignment->id.'/gradebook')
            ->assertForbidden();
        $this->put('/instructor/courses/'.$course->id.'/assignments/'.$assignment->id, [
            'title' => 'Changed',
            'instructions' => 'Malicious ownership override.',
            'max_score' => 100,
        ])->assertForbidden();

        $this->actingAs($teacher)
            ->get('/instructor/courses/'.$otherCourse->id.'/assignments/'.$assignment->id.'/gradebook')
            ->assertForbidden();
    }

    public function test_deadlines_and_maximum_grades_are_enforced(): void
    {
        $teacher = $this->member('instructor', 'teacher@edumia.test');
        $student = $this->member('student', 'student@edumia.test');
        $course = $this->course($teacher);
        $assignment = $this->assignment($course);

        $this->actingAs($student)->post('/courses/'.$course->id.'/enroll')->assertRedirect();
        $this->post('/learn/'.$course->id.'/assignments/'.$assignment->id, [
            'response' => 'An original response to this assignment.',
        ])->assertRedirect();

        $submission = SubmittedAssignment::firstOrFail();
        $this->actingAs($teacher)
            ->put('/instructor/courses/'.$course->id.'/assignments/'.$assignment->id.'/submissions/'.$submission->id.'/grade', [
                'score' => 101,
                'feedback' => 'Invalid grade.',
            ])->assertSessionHasErrors('score');

        $this->put('/instructor/courses/'.$course->id.'/assignments/'.$assignment->id, [
            'title' => 'Changed',
            'instructions' => 'A change to the scoring scale.',
            'max_score' => 200,
            'published' => 1,
        ])->assertSessionHasErrors('max_score');

        $assignment->update(['due_at' => now()->subDay()]);
        $this->actingAs($student)
            ->post('/learn/'.$course->id.'/assignments/'.$assignment->id, [
                'response' => 'An assignment submission after the deadline.',
            ])->assertSessionHasErrors('response');
    }

    public function test_instructor_cannot_link_a_lesson_from_another_course(): void
    {
        $teacher = $this->member('instructor', 'teacher@edumia.test');
        $course = $this->course($teacher);
        $otherCourse = $this->course($teacher, 'other-course');
        $section = $otherCourse->sections()->create(['title' => 'Wrong section']);
        $lesson = $section->lessons()->create([
            'course_id' => $otherCourse->id,
            'title' => 'Wrong lesson',
            'slug' => 'wrong-lesson',
        ]);

        $this->actingAs($teacher)
            ->post('/instructor/courses/'.$course->id.'/assignments', [
                'title' => 'An invalid linked lesson',
                'instructions' => 'This lesson belongs elsewhere.',
                'lesson_id' => $lesson->id,
                'max_score' => 100,
            ])->assertStatus(422);

        $this->assertDatabaseCount('assignments', 0);
    }
}
