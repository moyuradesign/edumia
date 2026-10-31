<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\QuizSubmission;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $student = $request->user();
        abort_unless($student?->isActive(), 403);

        return view('dashboard.student', [
            'enrollments' => Enrollment::where('user_id', $student->id)
                ->with('course')->latest()->get(),
            'certificates' => Certificate::where('user_id', $student->id)
                ->whereNull('revoked_at')->with('course')->get(),
            'passed' => QuizSubmission::where('user_id', $student->id)
                ->where('passed', true)->count(),
        ]);
    }
}
