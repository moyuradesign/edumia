<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Assignment;
use App\Models\AuditEvent;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\SubmittedAssignment;
use App\Models\User;
use App\Services\CourseCompletionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AssignmentService
{
    public function save(User $actor, Course $course, array $attributes, ?Assignment $assignment = null): Assignment
    {
        Gate::forUser($actor)->authorize('update', $course);

        return DB::transaction(function () use ($actor, $course, $attributes, $assignment): Assignment {
            if ($assignment !== null) {
                $assignment = Assignment::query()->lockForUpdate()->findOrFail($assignment->getKey());
                abort_unless($assignment->course_id === $course->getKey(), 404);
            } else {
                $assignment = new Assignment;
                $assignment->course_id = $course->getKey();
            }

            if (! empty($attributes['lesson_id'])) {
                abort_unless($course->lessons()->whereKey($attributes['lesson_id'])->exists(), 422, 'Lesson does not belong to this course.');
            }

            if ($assignment->exists && $assignment->submissions()->exists()
                && ((int) $attributes['max_score'] !== (int) $assignment->max_score)) {
                throw ValidationException::withMessages([
                    'max_score' => 'The maximum score cannot change once students have submitted.',
                ]);
            }

            $before = $assignment->exists
                ? $assignment->only(['title', 'max_score', 'published', 'due_at']) : null;

            $assignment->fill($attributes);
            $assignment->published = (bool) ($attributes['published'] ?? false);
            $assignment->save();

            AuditEvent::create([
                'actor_id' => $actor->getKey(),
                'subject_type' => Assignment::class,
                'subject_id' => $assignment->getKey(),
                'action' => $before ? 'assignments.updated' : 'assignments.created',
                'previous_values' => $before,
                'new_values' => $assignment->only(['title', 'max_score', 'published', 'due_at']),
            ]);

            return $assignment;
        });
    }

    public function submit(User $student, Assignment $assignment, string $response): SubmittedAssignment
    {
        abort_unless($student->isActive(), 403);
        abort_unless(
            $assignment->published && $assignment->course->status === 'published'
            && $assignment->course->visibility === 'public'
            && $assignment->course->published_at !== null,
            404
        );
        abort_unless(
            Enrollment::query()->where('user_id', $student->getKey())
                ->where('course_id', $assignment->course_id)->whereIn('status', ['active', 'completed'])->exists(),
            403
        );

        return DB::transaction(function () use ($student, $assignment, $response): SubmittedAssignment {
            $assignment = Assignment::query()->lockForUpdate()->findOrFail($assignment->getKey());

            abort_unless($assignment->published, 404);
            if ($assignment->due_at !== null && now()->greaterThan($assignment->due_at)) {
                throw ValidationException::withMessages(['response' => 'The submission deadline has passed.']);
            }

            $submission = SubmittedAssignment::query()
                ->where('assignment_id', $assignment->getKey())
                ->where('user_id', $student->getKey())
                ->lockForUpdate()
                ->first();

            if ($submission && $submission->graded_at !== null) {
                throw ValidationException::withMessages(['response' => 'A graded submission cannot be replaced.']);
            }

            $submission ??= new SubmittedAssignment([
                'assignment_id' => $assignment->getKey(),
                'user_id' => $student->getKey(),
            ]);

            $submission->response = $response;
            $submission->score = null;
            $submission->feedback = null;
            $submission->graded_by = null;
            $submission->graded_at = null;
            $submission->submitted_at = now();
            $submission->save();

            return $submission;
        }, attempts: 3);
    }

    public function grade(User $actor, Course $course, Assignment $assignment, SubmittedAssignment $submission, array $data): void
    {
        Gate::forUser($actor)->authorize('update', $course);
        abort_unless($assignment->course_id === $course->getKey(), 404);
        abort_unless($submission->assignment_id === $assignment->getKey(), 404);

        DB::transaction(function () use ($actor, $assignment, $submission, $data): void {
            $assignment = Assignment::query()->lockForUpdate()->findOrFail($assignment->getKey());
            $submission = SubmittedAssignment::query()->lockForUpdate()->findOrFail($submission->getKey());
            abort_unless($submission->assignment_id === $assignment->getKey(), 404);

            if ((float) $data['score'] > (float) $assignment->max_score) {
                throw ValidationException::withMessages(['score' => 'The score exceeds the maximum score.']);
            }

            $before = ['score' => $submission->score, 'graded' => $submission->graded_at !== null];
            $submission->forceFill([
                'score' => $data['score'],
                'feedback' => $data['feedback'] ?? null,
                'graded_by' => $actor->getKey(),
                'graded_at' => now(),
            ])->save();

            AuditEvent::create([
                'actor_id' => $actor->getKey(),
                'subject_type' => SubmittedAssignment::class,
                'subject_id' => $submission->getKey(),
                'action' => 'assignments.graded',
                'previous_values' => $before,
                'new_values' => ['score' => $submission->score, 'graded' => true],
            ]);
        });

        // Recalculate completion after grading. A certificate requires every
        // published assignment to be graded, not merely submitted.
        $enrollment = Enrollment::query()
            ->where('course_id', $course->getKey())
            ->where('user_id', $submission->user_id)->first();

        if ($enrollment !== null) {
            app(CourseCompletionService::class)->refresh($enrollment);
        }
    }
}
