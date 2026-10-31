<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Requests\Student\InstructorApplicationRequest;
use App\Models\Application;
use App\Services\Identity\InstructorApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class InstructorApplicationController extends Controller
{
    public function index(Request $request): View
    {
        return view('student.instructor-application', [
            'applications' => Application::query()
                ->where('type', 'instructor')->where('user_id', $request->user()->getKey())
                ->latest()->get(),
        ]);
    }

    public function store(InstructorApplicationRequest $request, InstructorApplicationService $service): RedirectResponse
    {
        $service->submit($request->user(), $request->validated());

        return to_route('instructor-application.index')
            ->with('success', 'Your application has been submitted for administrator review.');
    }
}
