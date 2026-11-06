<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Requests\Admin\ReviewInstructorApplicationRequest;
use App\Models\Application;
use App\Models\AuditEvent;
use App\Models\Course;
use App\Models\User;
use App\Services\Identity\InstructorApplicationService;
use App\Services\Identity\InstructorManagementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class InstructorApplicationController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $rawSearch = $request->query('q');
        $search = is_string($rawSearch) ? mb_substr(trim($rawSearch), 0, 100) : '';
        $rawStatus = $request->query('status');
        $status = is_string($rawStatus) && in_array($rawStatus, ['all', 'pending', 'approved', 'rejected'], true)
            ? $rawStatus : 'all';
        $rawReviewer = $request->query('reviewer');
        $reviewer = is_string($rawReviewer) && ctype_digit($rawReviewer) && (int) $rawReviewer > 0
            ? (int) $rawReviewer : null;
        $rawSort = $request->query('sort');
        $sort = is_string($rawSort) && in_array($rawSort, ['queue', 'newest', 'oldest', 'reviewed'], true)
            ? $rawSort : 'queue';

        $query = Application::query()->where('type', 'instructor')->with(['applicant', 'reviewer'])
            ->when($search !== '', fn (Builder $q) => $q->where(static function (Builder $inner) use ($search): void {
                $inner->whereHas('applicant', static function (Builder $member) use ($search): void {
                    $member->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                })->orWhere('details', 'like', '%'.$search.'%');
            }))
            ->when($status !== 'all', fn (Builder $q) => $q->where('status', $status))
            ->when($reviewer !== null, fn (Builder $q) => $q->where('reviewed_by', $reviewer));

        match ($sort) {
            'oldest' => $query->oldest('created_at')->orderBy('id'),
            'newest' => $query->latest('created_at')->latest('id'),
            'reviewed' => $query->orderByDesc('reviewed_at')->latest('id'),
            default => $query->orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
                ->latest('created_at')->latest('id'),
        };

        $all = Application::query()->where('type', 'instructor');
        return view('admin.users.instructor-applications', [
            'applications' => $query->paginate(20)->withQueryString(),
            'search' => $search, 'status' => $status, 'reviewer' => $reviewer, 'sort' => $sort,
            'reviewers' => User::query()
                ->whereIn('id', Application::query()->where('type', 'instructor')
                    ->whereNotNull('reviewed_by')->select('reviewed_by'))
                ->orderBy('name')->get(['id', 'name']),
            'allApplications' => (clone $all)->count(),
            'pendingApplications' => (clone $all)->where('status', 'pending')->count(),
            'approvedApplications' => (clone $all)->where('status', 'approved')->count(),
            'rejectedApplications' => (clone $all)->where('status', 'rejected')->count(),
        ]);
    }

    public function faculty(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $rawSearch = $request->query('q');
        $search = is_string($rawSearch) ? mb_substr(trim($rawSearch), 0, 100) : '';
        $rawStatus = $request->query('status');
        $status = is_string($rawStatus) && in_array($rawStatus, ['all', 'active', 'suspended'], true)
            ? $rawStatus : 'all';
        $rawActivity = $request->query('activity');
        $activity = is_string($rawActivity) && in_array($rawActivity, ['all', 'with_courses', 'without_courses'], true)
            ? $rawActivity : 'all';
        $rawSort = $request->query('sort');
        $sort = is_string($rawSort) && in_array($rawSort, ['name', 'newest', 'most_courses'], true)
            ? $rawSort : 'name';

        $query = User::query()->where('role', 'instructor')
            ->withCount([
                'courses',
                'courses as published_courses_count' => fn (Builder $q) => $q->where('status', 'published'),
                'courses as pending_courses_count' => fn (Builder $q) => $q->where('status', 'pending'),
            ])
            ->when($search !== '', fn (Builder $q) => $q
                ->where(fn (Builder $inner) => $inner->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')))
            ->when($status !== 'all', fn (Builder $q) => $q->where('status', $status))
            ->when($activity === 'with_courses', fn (Builder $q) => $q->whereHas('courses'))
            ->when($activity === 'without_courses', fn (Builder $q) => $q->whereDoesntHave('courses'));

        match ($sort) {
            'newest' => $query->latest('created_at')->latest('id'),
            'most_courses' => $query->orderByDesc('courses_count')->orderBy('name')->orderBy('id'),
            default => $query->orderBy('name')->orderBy('id'),
        };

        $teachers = $query->paginate(20)->withQueryString();
        $recentStatusChanges = AuditEvent::query()
            ->with('actor')
            ->where('subject_type', User::class)
            ->where('action', 'instructors.status_changed')
            ->whereIn('subject_id', $teachers->getCollection()->modelKeys())
            ->latest('id')->get()->unique('subject_id')->keyBy('subject_id');

        $all = User::query()->where('role', 'instructor');
        return view('admin.users.instructors', [
            'instructors' => $teachers,
            'recentStatusChanges' => $recentStatusChanges,
            'search' => $search, 'status' => $status, 'activity' => $activity, 'sort' => $sort,
            'allInstructors' => (clone $all)->count(),
            'activeInstructors' => (clone $all)->where('status', 'active')->count(),
            'suspendedInstructors' => (clone $all)->where('status', 'suspended')->count(),
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

    public function changeStatus(Request $request, User $user, InstructorManagementService $service): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        $service->changeStatus($request->user(), $user, $data['status'], $data['reason']);

        return back()->with('success', 'Instructor access updated and audited.');
    }

    public function assignments(Request $request, User $user): View
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($user->role === 'instructor', 404);

        return view('admin.users.instructor-assignments', [
            'instructor' => $user,
            'courses' => Course::query()->where('instructor_id', $user->getKey())
                ->withCount(['enrollments'])->latest('created_at')->latest('id')
                ->paginate(15)->withQueryString(),
            'targets' => User::query()->where('role', 'instructor')
                ->where('status', 'active')->whereKeyNot($user->getKey())
                ->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function reassign(
        Request $request,
        User $user,
        Course $course,
        InstructorManagementService $service
    ): RedirectResponse {
        abort_unless($request->user()?->isAdmin(), 403);
        $data = $request->validate([
            'new_instructor_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        $target = User::query()->findOrFail($data['new_instructor_id']);
        $service->reassign($request->user(), $user, $course, $target, $data['reason']);

        return to_route('admin.instructors.assignments', $user)
            ->with('success', 'Eligible draft course reassigned and audited.');
    }
}
