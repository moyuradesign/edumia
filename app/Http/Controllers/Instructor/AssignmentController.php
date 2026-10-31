<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Http\Requests\Instructor\AssignmentRequest;
use App\Http\Requests\Instructor\GradeAssignmentRequest;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\SubmittedAssignment;
use App\Services\Learning\AssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class AssignmentController extends Controller
{
    public function store(AssignmentRequest $request, Course $course, AssignmentService $service): RedirectResponse
    {
        $service->save($request->user(), $course, $request->validated());

        return to_route('instructor.courses.edit', $course)->with('success', 'Assignment created.');
    }

    public function update(
        AssignmentRequest $request,
        Course $course,
        Assignment $assignment,
        AssignmentService $service
    ): RedirectResponse {
        $service->save($request->user(), $course, $request->validated(), $assignment);

        return to_route('instructor.courses.edit', $course)->with('success', 'Assignment updated.');
    }

    public function gradebook(Course $course, Assignment $assignment): View
    {
        Gate::authorize('update', $course);
        abort_unless((int) $assignment->course_id === (int) $course->getKey(), 404);

        return view('instructor.assignments.gradebook', [
            'course' => $course,
            'assignment' => $assignment,
            'submissions' => $assignment->submissions()
                ->with(['student', 'grader'])->latest('submitted_at')->paginate(25),
        ]);
    }

    public function grade(
        GradeAssignmentRequest $request,
        Course $course,
        Assignment $assignment,
        SubmittedAssignment $submission,
        AssignmentService $service
    ): RedirectResponse {
        $service->grade($request->user(), $course, $assignment, $submission, $request->validated());

        return to_route('instructor.assignments.gradebook', [$course, $assignment])
            ->with('success', 'Submission graded.');
    }
}
