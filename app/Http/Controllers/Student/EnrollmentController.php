<?php

namespace App\Http\Controllers\Student;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class EnrollmentController extends Controller
{
    public function store(Request $request, Course $course)
    {
        abort_unless($request->user()->isActive(), 403);
        abort_unless($course->status === 'published' && $course->visibility === 'public' && $course->published_at, 404);
        abort_unless($course->isFree(), 403, 'Paid courses require verified checkout; purchase is not implemented.');
        Enrollment::firstOrCreate(['course_id' => $course->id, 'user_id' => $request->user()->id],
            ['enrolled_at' => now(), 'progress_percent' => 0, 'status' => 'active']);

        return redirect()->route('learning.course', $course)->with('success', 'Enrolled successfully.');
    }
}
