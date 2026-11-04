<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttemptSession;
use App\Models\QuizSubmission;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdvancedAssessmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $email, string $role): User
    {
        $user = User::query()->create(['name'=>ucfirst($role),
            'email'=>$email,'password'=>'SecurePass#123']);
        $user->forceFill(['role'=>$role,'status'=>'active'])->save();
        return $user;
    }

    public function test_timed_randomized_attempt_is_persisted_grades_weighted_questions_and_cannot_replay(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-05 08:00:00','UTC'));
        $teacher = $this->actor('teacher@example.test','instructor');
        $learner = $this->actor('learner@example.test','student');
        $course = Course::query()->create([
            'instructor_id'=>$teacher->getKey(), 'title'=>'Advanced Skills',
            'slug'=>'advanced-skills', 'status'=>'published',
            'visibility'=>'public','published_at'=>now(), 'price'=>0,
        ]);
        Enrollment::query()->create(['course_id'=>$course->id,
            'user_id'=>$learner->id,'status'=>'active']);

        $this->actingAs($teacher)->post(route('instructor.quizzes.store',$course), [
            'title'=>'Advanced Knowledge Check', 'passing_score'=>70,
            'attempts_allowed'=>2, 'time_limit_minutes'=>5,
            'randomize_questions'=>1,
        ])->assertRedirect();
        $quiz = Quiz::query()->firstOrFail();
        $this->assertSame(5, $quiz->time_limit_minutes);
        $this->assertTrue($quiz->randomize_questions);

        $this->post(route('instructor.questions.store',[$course,$quiz]), [
            'question'=>'Which figure is correct?', 'options'=>['One','Two','Three','Four'],
            'correct'=>1, 'points'=>3,
        ])->assertRedirect();
        $this->post(route('instructor.questions.store',[$course,$quiz]), [
            'question'=>'Which answer is correct?', 'options'=>['Left','Right','North','South'],
            'correct'=>0, 'points'=>1,
        ])->assertRedirect();
        $this->post(route('instructor.quizzes.publish',[$course,$quiz]))->assertRedirect();

        $ids = $quiz->questions()->orderBy('id')->pluck('id')->all();
        $this->actingAs($learner)->get(route('learning.quiz',$quiz))->assertOk()
            ->assertSee('Start assessment');
        $this->post(route('learning.quiz.start',$quiz))->assertRedirect();
        $active = QuizAttemptSession::query()->firstOrFail();
        $this->get(route('learning.quiz',$quiz))->assertOk()
            ->assertSee('Submit answers for grading')->assertSee('data-quiz-deadline', false);
        $this->post(route('learning.quiz.submit',$quiz), [
            'attempt_token'=>$active->token,
            'answers'=>[$ids[0]=>1, $ids[1]=>2],
        ])->assertRedirect();
        $this->assertDatabaseHas('quiz_submissions', [
            'quiz_id'=>$quiz->id,'user_id'=>$learner->id,
            'attempt_number'=>1,'score'=>75,'passed'=>true,
        ]);
        $this->assertSame('submitted', $active->fresh()->status);
        $this->post(route('learning.quiz.submit',$quiz), [
            'attempt_token'=>$active->token,
            'answers'=>[$ids[0]=>1,$ids[1]=>0],
        ])->assertForbidden();
        $this->assertDatabaseCount('quiz_submissions',1);

        $this->post(route('learning.quiz.start',$quiz))->assertRedirect();
        $second = QuizAttemptSession::query()->latest('id')->firstOrFail();
        $this->travel(6)->minutes();
        $this->post(route('learning.quiz.submit',$quiz), [
            'attempt_token'=>$second->token,
            'answers'=>[$ids[0]=>1,$ids[1]=>0],
        ])->assertForbidden();
        $this->post(route('learning.quiz.start',$quiz))->assertForbidden();
        $this->assertDatabaseCount('quiz_submissions',1);
    }

    public function test_legacy_untimed_quiz_still_accepts_direct_answer_submission(): void
    {
        $teacher = $this->actor('teacher@example.test','instructor');
        $learner = $this->actor('learner@example.test','student');
        $course = Course::query()->create([
            'instructor_id'=>$teacher->id,'title'=>'Legacy Learning',
            'slug'=>'legacy-learning','status'=>'published','visibility'=>'public',
            'published_at'=>now(),'price'=>0,
        ]);
        Enrollment::query()->create(['course_id'=>$course->id,'user_id'=>$learner->id,'status'=>'active']);
        $quiz = Quiz::query()->create([
            'course_id'=>$course->id,'title'=>'Practice',
            'passing_score'=>70,'attempts_allowed'=>3,'is_published'=>true,
        ]);
        $question = $quiz->questions()->create([
            'question'=>'A question?', 'options'=>['No','Yes','Maybe','Unknown'],
            'correct_answer'=>[1], 'points'=>1,
        ]);
        $this->actingAs($learner)->post(route('learning.quiz.submit',$quiz), [
            'answers'=>[$question->id=>1],
        ])->assertRedirect();
        $this->assertSame(1, QuizSubmission::query()->count());
        $this->assertTrue(QuizSubmission::firstOrFail()->passed);
    }
}
