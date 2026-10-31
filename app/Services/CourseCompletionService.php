<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\QuizSubmission;
use App\Models\SubmittedAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CourseCompletionService
{
    public function refresh(Enrollment $enrollment): void
    {
        DB::transaction(function () use ($enrollment) {
            $enrollment = Enrollment::query()->whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            if (! in_array($enrollment->status, ['active', 'completed'], true)) {
                return;
            }
            $course = $enrollment->course;
            $lessons = $course->lessons()->where('is_published', true)->whereHas('section', fn ($q) => $q->where('is_published', true));
            $total = $lessons->count();
            $finished = LessonProgress::where('user_id', $enrollment->user_id)->whereIn('lesson_id', $lessons->select('id'))->count();
            $percent = $total > 0 ? (int) floor($finished * 100 / $total) : 0;
            $enrollment->progress_percent = $percent;
            $publishedQuizzes = $course->quizzes()->where('is_published', true)->pluck('id');
            $passed = QuizSubmission::where('user_id', $enrollment->user_id)->where('passed', true)->whereIn('quiz_id', $publishedQuizzes)->distinct('quiz_id')->count('quiz_id');
            $publishedAssignments = $course->assignments()->where('published', true)->pluck('id');
            $gradedAssignments = SubmittedAssignment::query()
                ->where('user_id', $enrollment->user_id)
                ->whereNotNull('graded_at')
                ->whereIn('assignment_id', $publishedAssignments)
                ->distinct('assignment_id')
                ->count('assignment_id');

            if ($total > 0 && $finished === $total
                && $publishedQuizzes->count() === $passed
                && $publishedAssignments->count() === $gradedAssignments) {
                $enrollment->completed_at ??= now();
                $enrollment->status = 'completed';
                Certificate::firstOrCreate(['course_id' => $course->id, 'user_id' => $enrollment->user_id],
                    ['certificate_number' => 'EDM-'.strtoupper(Str::random(16)), 'verification_token' => Str::random(48), 'issued_at' => now()]);
            } else {
                // If a course changes after certification, keep previously issued credentials as historical records.
                $enrollment->status = 'active';
                $enrollment->completed_at = null;
            }
            $enrollment->save();
        });
    }
}
