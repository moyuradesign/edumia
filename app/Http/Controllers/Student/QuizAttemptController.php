<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttemptSession;
use App\Models\QuizSubmission;
use App\Models\User;
use App\Services\CourseCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class QuizAttemptController extends Controller
{
    public function show(Request $request, Quiz $quiz): View
    {
        $this->authorizeAccess($request, $quiz);
        abort_unless($quiz->questions()->exists(), 404);
        $id = $request->user()->getKey();
        $past = QuizSubmission::query()->where('quiz_id', $quiz->getKey())
            ->where('user_id', $id)->latest('id')->get();
        $requiresSession = $this->requiresSession($quiz);

        $session = $requiresSession
            ? QuizAttemptSession::query()->where('quiz_id', $quiz->getKey())
                ->where('user_id', $id)->where('status', 'active')
                ->where('expires_at', '>', now())->latest('id')->first()
            : null;

        $questions = $session || ! $requiresSession
            ? $quiz->questions()->get() : collect();

        if ($session && $quiz->randomize_questions) {
            $questions = $questions->sortBy(static fn ($question) =>
                hash_hmac('sha256', (string) $question->getKey(), $session->token))->values();
        }

        $sessionCount = $requiresSession
            ? QuizAttemptSession::query()->where('quiz_id', $quiz->getKey())
                ->where('user_id', $id)->whereIn('status', ['active','expired'])->count()
            : 0;

        return view('student.quiz', [
            'quiz' => $quiz,
            'questions' => $questions,
            'past' => $past,
            'attempts' => $past->count() + $sessionCount,
            'activeSession' => $session,
            'requiresSession' => $requiresSession,
        ]);
    }

    public function start(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorizeAccess($request, $quiz);
        abort_unless($quiz->questions()->exists(), 404);
        if (! $this->requiresSession($quiz)) {
            return to_route('learning.quiz', $quiz);
        }

        DB::transaction(function () use ($request, $quiz): void {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());

            // Every expired started session uses one attempt. Submitted sessions
            // are accounted for through their corresponding graded submission.
            QuizAttemptSession::query()->where('quiz_id', $quiz->getKey())
                ->where('user_id', $user->getKey())->where('status', 'active')
                ->where('expires_at', '<=', now())->update(['status' => 'expired']);

            $active = QuizAttemptSession::query()->where('quiz_id', $quiz->getKey())
                ->where('user_id', $user->getKey())->where('status', 'active')
                ->where('expires_at', '>', now())->exists();
            if ($active) {
                return;
            }

            $used = QuizSubmission::query()->where('quiz_id', $quiz->getKey())
                ->where('user_id', $user->getKey())->count()
                + QuizAttemptSession::query()->where('quiz_id', $quiz->getKey())
                    ->where('user_id', $user->getKey())->where('status', 'expired')->count();

            abort_if($used >= $quiz->attempts_allowed, 403, 'No attempts remain.');

            QuizAttemptSession::query()->create([
                'quiz_id' => $quiz->getKey(),
                'user_id' => $user->getKey(),
                'token' => Str::random(48),
                'status' => 'active',
                'started_at' => now(),
                'expires_at' => now()->addMinutes($quiz->time_limit_minutes ?: 1440),
            ]);
        }, attempts: 3);

        return to_route('learning.quiz', $quiz);
    }

    public function submit(Request $request, Quiz $quiz, CourseCompletionService $completion): RedirectResponse
    {
        $enrollment = $this->authorizeAccess($request, $quiz);
        $requiresSession = $this->requiresSession($quiz);
        $data = $request->validate([
            'attempt_token' => [$requiresSession ? 'required' : 'nullable', 'string', 'max:64'],
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer', 'between:0,3'],
        ]);

        $questions = $quiz->questions()->get();
        abort_unless($questions->isNotEmpty(), 404);
        $selected = $data['answers'];
        $totalPoints = 0;
        $earnedPoints = 0;
        $saved = [];
        foreach ($questions as $question) {
            abort_unless($question->type === 'single_choice'
                && is_array($question->options)
                && count($question->options) === 4, 422, 'Unsupported question type.');
            $answer = $selected[(string) $question->getKey()] ?? null;
            if ($answer === null || ! array_key_exists((int) $answer, $question->options)) {
                throw ValidationException::withMessages(['answers' => 'Answer every question before submitting.']);
            }
            $totalPoints += $question->points;
            $earnedPoints += ((int) $answer === (int) ($question->correct_answer[0] ?? -1))
                ? $question->points : 0;
            $saved[(string) $question->getKey()] = (int) $answer;
        }
        $percentage = $totalPoints > 0 ? round(100 * $earnedPoints / $totalPoints, 2) : 0;

        DB::transaction(function () use ($request, $quiz, $data, $requiresSession, $saved, $percentage): void {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());
            $session = null;
            if ($requiresSession) {
                $session = QuizAttemptSession::query()->where('quiz_id', $quiz->getKey())
                    ->where('user_id', $user->getKey())
                    ->where('token', $data['attempt_token'])->lockForUpdate()->firstOrFail();
                abort_unless($session->status === 'active', 403, 'This quiz attempt is closed.');
                abort_if($session->expires_at->isPast(), 403, 'The time limit has expired.');
            }

            $attempt = QuizSubmission::query()->where('quiz_id', $quiz->getKey())
                ->where('user_id', $user->getKey())->count() + 1;

            $expired = $requiresSession
                ? QuizAttemptSession::query()->where('quiz_id', $quiz->getKey())
                    ->where('user_id', $user->getKey())->where('status', 'expired')->count()
                : 0;
            abort_if($attempt + $expired > $quiz->attempts_allowed, 403, 'No attempts remain.');

            QuizSubmission::query()->create([
                'quiz_id' => $quiz->getKey(), 'user_id' => $user->getKey(),
                'attempt_number' => $attempt, 'answers' => $saved,
                'score' => $percentage, 'passed' => $percentage >= $quiz->passing_score,
                'started_at' => $session?->started_at ?? now(),
                'submitted_at' => now(),
            ]);
            if ($session) {
                $session->update(['status' => 'submitted']);
            }
        }, attempts: 3);

        $completion->refresh($enrollment);

        return to_route('learning.quiz', $quiz)->with('success', 'Quiz graded and your attempt recorded.');
    }

    private function requiresSession(Quiz $quiz): bool
    {
        return (int) $quiz->time_limit_minutes > 0 || (bool) $quiz->randomize_questions;
    }

    private function authorizeAccess(Request $request, Quiz $quiz): Enrollment
    {
        abort_unless($request->user()?->isActive()
            && $quiz->is_published
            && $quiz->course->status === 'published'
            && $quiz->course->visibility === 'public'
            && $quiz->course->published_at !== null, 404);

        return Enrollment::query()->where('course_id', $quiz->course_id)
            ->where('user_id', $request->user()->getKey())
            ->whereIn('status', ['active','completed'])->firstOrFail();
    }
}
