<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $instructor = $request->user();
        abort_unless($instructor?->canTeach(), 403);

        return view('dashboard.instructor', [
            'courses' => Course::where('instructor_id', $instructor->id)
                ->withCount(['enrollments', 'lessons'])
                ->latest()
                ->get(),
            'enrollments' => Enrollment::whereHas(
                'course',
                fn ($query) => $query->where('instructor_id', $instructor->id)
            )->count(),
        ]);
    }
}
