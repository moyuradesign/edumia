<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Requests\Student\SubmitAssignmentRequest;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\SubmittedAssignment;
use App\Services\Learning\AssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class AssignmentController extends Controller
{
    public function show(Request $request, Course $course, Assignment $assignment): View
    {
        abort_unless((int) $assignment->course_id === (int) $course->getKey(), 404);
        abort_unless(
            $assignment->published && $course->status === 'published'
            && $course->published_at !== null && $course->visibility === 'public',
            404
        );
        Enrollment::query()->where('user_id', $request->user()->getKey())
            ->where('course_id', $course->getKey())->whereIn('status', ['active', 'completed'])->firstOrFail();

        return view('student.assignments.show', [
            'course' => $course,
            'assignment' => $assignment,
            'submission' => SubmittedAssignment::query()
                ->where('assignment_id', $assignment->getKey())
                ->where('user_id', $request->user()->getKey())->first(),
        ]);
    }

    public function submit(
        SubmitAssignmentRequest $request,
        Course $course,
        Assignment $assignment,
        AssignmentService $service
    ): RedirectResponse {
        abort_unless((int) $assignment->course_id === (int) $course->getKey(), 404);
        $service->submit($request->user(), $assignment, $request->validated('response'));

        return to_route('learning.assignments.show', [$course, $assignment])
            ->with('success', 'Assignment submitted.');
    }
}
