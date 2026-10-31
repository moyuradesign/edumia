<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Models\Bootcamp;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\QuizSubmission;
use App\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class LearningReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports.learning', [
            'totals' => [
                'users' => User::query()->count(),
                'instructors' => User::query()->where('role', 'instructor')->count(),
                'courses' => Course::query()->count(),
                'enrollments' => Enrollment::query()->count(),
                'completed_enrollments' => Enrollment::query()->where('status', 'completed')->count(),
                'certificates' => Certificate::query()->whereNull('revoked_at')->count(),
                'quiz_attempts' => QuizSubmission::query()->count(),
                'quiz_passes' => QuizSubmission::query()->where('passed', true)->count(),
                'published_bootcamps' => Bootcamp::query()->where('status', 'published')->count(),
            ],
            'courses' => Course::query()
                ->with('instructor')
                ->withCount(['enrollments', 'enrollments as completed_count' => fn ($q) =>
                    $q->where('status', 'completed')])
                ->orderByDesc('enrollments_count')
                ->paginate(25),
        ]);
    }
}
