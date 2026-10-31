<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Requests\Student\ReviewRequest;
use App\Models\Course;
use App\Services\Learning\CourseFeedbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class CourseReviewController extends Controller
{
    public function store(ReviewRequest $request, Course $course, CourseFeedbackService $feedback): RedirectResponse
    {
        $feedback->review($request->user(), $course, $request->validated());

        return back()->with('success', 'Your review was submitted for moderation.');
    }
}
