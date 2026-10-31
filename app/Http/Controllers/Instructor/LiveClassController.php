<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Http\Requests\Instructor\LiveClassRequest;
use App\Models\Course;
use App\Models\LiveClass;
use App\Services\Learning\LiveClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class LiveClassController extends Controller
{
    public function index(Course $course): View
    {
        Gate::authorize('update', $course);

        return view('instructor.live-classes.index', [
            'course' => $course,
            'classes' => $course->liveClasses()->orderBy('starts_at')->get(),
        ]);
    }

    public function store(LiveClassRequest $request, Course $course, LiveClassService $service): RedirectResponse
    {
        $service->save($request->user(), $course, $request->validated());

        return to_route('instructor.live-classes.index', $course)->with('success', 'Class scheduled.');
    }

    public function update(
        LiveClassRequest $request, Course $course, LiveClass $liveClass, LiveClassService $service
    ): RedirectResponse {
        $service->save($request->user(), $course, $request->validated(), $liveClass);

        return to_route('instructor.live-classes.index', $course)->with('success', 'Class updated.');
    }
}
