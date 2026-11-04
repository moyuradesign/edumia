<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizSubmission;
use App\Models\Section;
use App\Models\SubmittedAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InstructorLearnerProgressTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role, string $name, string $email): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'StrongPassword#12345',
        ]);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    private function course(User $teacher, string $title, string $slug): Course
    {
        return Course::create([
            'instructor_id' => $teacher->getKey(),
            'title' => $title,
            'slug' => $slug,
            'status' => 'published',
            'visibility' => 'public',
            'published_at' => now(),
            'price' => 0,
        ]);
    }

    private function enroll(Course $course, User $student, int $progress, string $status = 'active'): Enrollment
    {
        return Enrollment::create([
            'course_id' => $course->id, 'user_id' => $student->id,
            'status' => $status, 'progress_percent' => $progress,
            'enrolled_at' => now(), 'completed_at' => $status === 'completed' ? now() : null,
        ]);
    }

    public function test_instructor_can_view_real_learner_lesson_quiz_and_assignment_records(): void
    {
        $teacher = $this->member('instructor', 'Teaching Owner', 'teacher@edumia.test');
        $learner = $this->member('student', 'Assigned Learner', 'learner@edumia.test');
        $course = $this->course($teacher, 'Student Success', 'student-success');
        $enrollment = $this->enroll($course, $learner, 50);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Unit one',
            'sort_order' => 1, 'is_published' => true]);
        $lesson = Lesson::create(['course_id' => $course->id, 'section_id' => $section->id,
            'title' => 'Time management', 'slug' => 'time-management', 'is_published' => true]);
        LessonProgress::create(['user_id' => $learner->id, 'lesson_id' => $lesson->id,
            'completed_at' => now()]);
        $quiz = Quiz::create(['course_id' => $course->id,
            'title' => 'Time management quiz', 'is_published' => true,
            'passing_score' => 70, 'attempts_allowed' => 3]);
        QuizSubmission::create(['quiz_id' => $quiz->id, 'user_id' => $learner->id,
            'attempt_number' => 1, 'answers' => [], 'score' => 85,
            'passed' => true, 'started_at' => now(), 'submitted_at' => now()]);
        $assignment = Assignment::create([
            'course_id' => $course->id, 'title' => 'Reflection journal',
            'instructions' => 'Write a reflection.', 'max_score' => 100, 'published' => true,
        ]);
        SubmittedAssignment::create(['assignment_id' => $assignment->id,
            'user_id' => $learner->id, 'response' => 'My learning reflection.',
            'submitted_at' => now(), 'score' => 92,
            'graded_by' => $teacher->id, 'graded_at' => now()]);
        Certificate::create(['course_id' => $course->id, 'user_id' => $learner->id,
            'certificate_number' => 'CERT-TEST-1', 'verification_token' => str_repeat('D', 48),
            'issued_at' => now()]);

        $this->actingAs($teacher)->get(route('instructor.students.index'))
            ->assertOk()->assertSee('View progress')
            ->assertSee(route('instructor.students.show', $enrollment), false);

        $this->get(route('instructor.students.show', $enrollment))
            ->assertOk()
            ->assertSee('Assigned Learner')->assertSee('Student Success')
            ->assertSee('Time management')->assertSee('1 / 1')
            ->assertSee('Time management quiz')->assertSee('85.0%')
            ->assertSee('Reflection journal')->assertSee('92.0 / 100')
            ->assertSee('Issued')->assertDontSee('teacher@edumia.test');

        $this->get(route('instructor.reports.index'))
            ->assertOk()->assertSee('Student Success')
            ->assertSee('50.0%')->assertSee('1 passed attempts');
    }

    public function test_no_instructor_can_read_another_instructors_learner_progress_or_export(): void
    {
        $teacher = $this->member('instructor', 'Teacher One', 'one@edumia.test');
        $other = $this->member('instructor', 'Teacher Two', 'two@edumia.test');
        $learner = $this->member('student', 'Visible Student', 'visible@edumia.test');
        $privateLearner = $this->member('student', 'Private Student', 'private@edumia.test');

        $owned = $this->course($teacher, 'Owned Classes', 'owned-classes');
        $privateCourse = $this->course($other, 'Private Classes', 'private-classes');

        $ownedEnrollment = $this->enroll($owned, $learner, 65);
        $privateEnrollment = $this->enroll($privateCourse, $privateLearner, 100, 'completed');

        $this->actingAs($teacher)
            ->get(route('instructor.students.show', $privateEnrollment))->assertNotFound();
        $this->get(route('instructor.students.show', $ownedEnrollment))->assertOk()
            ->assertSee('Visible Student')->assertDontSee('Private Student');

        $this->actingAs($other)
            ->get(route('instructor.students.show', $ownedEnrollment))->assertNotFound();

        $this->actingAs($learner)
            ->get(route('instructor.students.show', $ownedEnrollment))->assertForbidden();
        $this->get(route('instructor.students.export'))->assertForbidden();
        $this->get(route('instructor.reports.index'))->assertForbidden();

    }

    public function test_student_filters_and_export_share_the_same_ownership_restrictions(): void
    {
        $teacher = $this->member('instructor', 'Teacher Owner', 'owner@edumia.test');
        $other = $this->member('instructor', 'Other Owner', 'other@edumia.test');
        $student = $this->member('student', '=SUM(1+1)', 'formula@edumia.test');
        $otherStudent = $this->member('student', 'Hidden Student', 'hidden@edumia.test');

        $course = $this->course($teacher, '+Course Totals', 'course-totals');
        $privateCourse = $this->course($other, 'Sensitive Training', 'sensitive-training');
        $this->enroll($course, $student, 72);
        $this->enroll($privateCourse, $otherStudent, 100, 'completed');

        $this->actingAs($teacher)->get(route('instructor.students.index', ['status' => 'active']))
            ->assertOk()->assertSee('View progress')->assertSee('=SUM(1+1)')
            ->assertDontSee('Hidden Student');
        $this->get(route('instructor.students.index', ['status' => 'completed']))
            ->assertOk()->assertSee('No matching enrollments')
            ->assertDontSee('Hidden Student');
        $this->get(route('instructor.students.index', ['course' => $privateCourse->id]))
            ->assertOk()->assertSee('No matching enrollments');

        $response = $this->get(route('instructor.students.export', ['status' => 'active']));
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $response->assertHeader('cache-control', 'private, no-store, max-age=0');

        ob_start();
        $response->baseResponse->sendContent();
        $csv = ob_get_clean();

        $this->assertIsString($csv);
        $this->assertStringContainsString("'=SUM(1+1)", $csv);
        $this->assertStringContainsString("'+Course Totals", $csv);
        $this->assertStringNotContainsString('Hidden Student', $csv);
        $this->assertStringNotContainsString('Sensitive Training', $csv);

        $privateResponse = $this->get(route('instructor.students.export', ['course' => $privateCourse->id]));
        ob_start();
        $privateResponse->baseResponse->sendContent();
        $privateCsv = ob_get_clean();
        $this->assertStringNotContainsString('Hidden Student', $privateCsv);
    }

    public function test_reports_aggregate_only_own_course_progress_and_grading(): void
    {
        $teacher = $this->member('instructor', 'Teaching Owner', 'teacher@edumia.test');
        $otherTeacher = $this->member('instructor', 'Private Teacher', 'private@edumia.test');
        $a = $this->member('student', 'Student A', 'a@edumia.test');
        $b = $this->member('student', 'Student B', 'b@edumia.test');
        $otherStudent = $this->member('student', 'Student C', 'c@edumia.test');

        $mine = $this->course($teacher, 'Leadership Skills', 'leadership-skills');
        $other = $this->course($otherTeacher, 'Hidden Leadership', 'hidden-leadership');

        $this->enroll($mine, $a, 40);
        $this->enroll($mine, $b, 100, 'completed');
        $this->enroll($other, $otherStudent, 100, 'completed');

        $quiz = Quiz::create(['course_id' => $mine->id, 'title' => 'Leadership quiz',
            'passing_score' => 70, 'attempts_allowed' => 3, 'is_published' => true]);
        QuizSubmission::create(['quiz_id' => $quiz->id, 'user_id' => $a->id,
            'attempt_number' => 1, 'answers' => [], 'score' => 30,
            'passed' => false, 'submitted_at' => now()]);
        QuizSubmission::create(['quiz_id' => $quiz->id, 'user_id' => $b->id,
            'attempt_number' => 1, 'answers' => [], 'score' => 90,
            'passed' => true, 'submitted_at' => now()]);

        $assignment = Assignment::create(['course_id' => $mine->id,
            'title' => 'Leadership reflection', 'instructions' => 'Reflection.',
            'max_score' => 100, 'published' => true]);
        SubmittedAssignment::create(['assignment_id' => $assignment->id, 'user_id' => $a->id,
            'response' => 'I completed it.', 'submitted_at' => now()]);

        $this->actingAs($teacher)->get(route('instructor.reports.index'))
            ->assertOk()
            ->assertSee('Leadership Skills')->assertDontSee('Hidden Leadership')
            ->assertSee('70.0%')->assertSee('50.0%')
            ->assertSee('1 ungraded submissions')
            ->assertSee('1 passed attempts')
            ->assertSee(route('instructor.students.index', ['course' => $mine->id]), false);
    }
}
