<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Requests\Admin\ReviewInstructorApplicationRequest;
use App\Models\Application;
use App\Services\Identity\InstructorApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class InstructorApplicationController extends Controller
{
    public function index(): View
    {
        return view('admin.users.instructor-applications', [
            'applications' => Application::query()->where('type', 'instructor')
                ->with(['applicant', 'reviewer'])
                ->orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
                ->latest()->paginate(20),
        ]);
    }

    public function review(
        ReviewInstructorApplicationRequest $request,
        Application $application,
        InstructorApplicationService $service
    ): RedirectResponse {
        $service->decide($request->user(), $application,
            $request->validated('decision'), $request->validated('review_note'));

        return to_route('admin.instructor-applications.index')
            ->with('success', 'Instructor application reviewed.');
    }
}
