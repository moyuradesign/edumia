<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class LiveClassController extends Controller
{
    public function index(Request $request, Course $course): View
    {
        abort_unless($course->status === 'published' && $course->visibility === 'public'
            && $course->published_at !== null, 404);

        Enrollment::query()->where('course_id', $course->getKey())
            ->where('user_id', $request->user()->getKey())
            ->whereIn('status', ['active', 'completed'])->firstOrFail();

        return view('student.live-classes.index', [
            'course' => $course,
            'classes' => $course->liveClasses()
                ->where('status', 'scheduled')->orderBy('starts_at')->get(),
        ]);
    }
}
