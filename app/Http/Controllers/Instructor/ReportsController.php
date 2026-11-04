<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\QuizSubmission;
use App\Models\SubmittedAssignment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->canTeach(), 403);
        $instructorId = (int) $request->user()->getKey();

        $ownedEnrollments = Enrollment::query()
            ->whereHas('course', fn ($query) => $query->where('instructor_id', $instructorId));

        $quizSubmissions = QuizSubmission::query()
            ->whereHas('quiz.course', fn ($query) => $query->where('instructor_id', $instructorId));

        return view('instructor.reports.index', [
            'courses' => Course::query()
                ->where('instructor_id', $instructorId)
                ->withCount(['enrollments', 'enrollments as completed_count' => fn ($query) =>
                    $query->where('status', 'completed')])
                ->withAvg('enrollments as average_progress', 'progress_percent')
                ->orderByDesc('enrollments_count')
                ->paginate(25),
            'courseCount' => Course::query()->where('instructor_id', $instructorId)->count(),
            'enrollmentCount' => (clone $ownedEnrollments)->count(),
            'completedCount' => (clone $ownedEnrollments)->where('status', 'completed')->count(),
            'averageProgress' => round((float) (clone $ownedEnrollments)->avg('progress_percent'), 1),
            'quizAttempts' => (clone $quizSubmissions)->count(),
            'quizPasses' => (clone $quizSubmissions)->where('passed', true)->count(),
            'ungradedSubmissions' => SubmittedAssignment::query()
                ->whereNull('graded_at')
                ->whereHas('assignment.course', fn ($query) =>
                    $query->where('instructor_id', $instructorId))
                ->count(),
        ]);
    }
}
