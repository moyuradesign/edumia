<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Requests\Admin\ReviewInstructorApplicationRequest;
use App\Models\Application;
use App\Services\Identity\InstructorApplicationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class InstructorApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $requestedSearch = $request->query('q');
        $search = is_string($requestedSearch) ? mb_substr(trim($requestedSearch), 0, 100) : '';
        $requestedStatus = $request->query('status');
        $status = is_string($requestedStatus) && in_array($requestedStatus, ['all', 'pending', 'approved', 'rejected'], true)
            ? $requestedStatus : 'all';

        $applications = Application::query()->where('type', 'instructor')->with(['applicant', 'reviewer']);

        if ($search !== '') {
            $applications->where(static function (Builder $query) use ($search): void {
                $query->whereHas('applicant', static function (Builder $member) use ($search): void {
                    $member->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                })->orWhere('details', 'like', '%'.$search.'%');
            });
        }
        if ($status !== 'all') {
            $applications->where('status', $status);
        }

        $base = Application::query()->where('type', 'instructor');
        return view('admin.users.instructor-applications', [
            'applications' => $applications
                ->orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
                ->latest('created_at')->latest('id')->paginate(20)->withQueryString(),
            'search' => $search,
            'status' => $status,
            'allApplications' => (clone $base)->count(),
            'pendingApplications' => (clone $base)->where('status', 'pending')->count(),
            'approvedApplications' => (clone $base)->where('status', 'approved')->count(),
            'rejectedApplications' => (clone $base)->where('status', 'rejected')->count(),
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
