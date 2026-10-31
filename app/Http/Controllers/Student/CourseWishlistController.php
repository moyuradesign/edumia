<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Models\Course;
use App\Models\Wishlist;
use App\Services\Learning\CourseFeedbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CourseWishlistController extends Controller
{
    public function index(Request $request): View
    {
        return view('student.saved-courses', [
            'savedCourses' => Wishlist::query()
                ->where('user_id', $request->user()->getKey())
                ->whereHas('course', fn ($query) => $query->published())
                ->with('course')->latest()->paginate(12),
        ]);
    }

    public function store(Request $request, Course $course, CourseFeedbackService $feedback): RedirectResponse
    {
        $feedback->saveWishlist($request->user(), $course);

        return back()->with('success', 'Course saved.');
    }

    public function destroy(Request $request, Course $course, CourseFeedbackService $feedback): RedirectResponse
    {
        $feedback->removeWishlist($request->user(), $course);

        return back()->with('success', 'Course removed from your list.');
    }
}
