<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Courses;

use App\Http\Requests\Admin\ModerateReviewRequest;
use App\Models\Review;
use App\Services\Learning\CourseFeedbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ReviewModerationController extends Controller
{
    public function index(): View
    {
        return view('admin.courses.reviews.index', [
            'reviews' => Review::query()->with(['author', 'course'])
                ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
                ->latest()->paginate(20),
        ]);
    }

    public function update(
        ModerateReviewRequest $request,
        Review $review,
        CourseFeedbackService $feedback
    ): RedirectResponse {
        $feedback->moderate($request->user(), $review, $request->validated('status'));

        return to_route('admin.reviews.index')->with('success', 'Review moderation updated.');
    }
}
