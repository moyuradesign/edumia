<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Models\Course;
use App\Models\QuizSubmission;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        $instructorId = $request->user()->getKey();

        return view('instructor.reports.index', [
            'courses' => Course::query()
                ->where('instructor_id', $instructorId)
                ->withCount(['enrollments', 'enrollments as completed_count' => fn ($q) =>
                    $q->where('status', 'completed')])
                ->orderByDesc('enrollments_count')
                ->paginate(25),
            'quizAttempts' => QuizSubmission::query()
                ->whereHas('quiz', fn ($q) => $q->whereHas('course', fn ($course) =>
                    $course->where('instructor_id', $instructorId)))
                ->count(),
        ]);
    }
}
