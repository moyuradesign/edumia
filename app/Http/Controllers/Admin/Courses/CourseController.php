<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Courses;

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CourseController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $rawSearch = $request->query('q');
        $search = is_string($rawSearch) ? mb_substr(trim($rawSearch), 0, 100) : '';
        $rawStatus = $request->query('status');
        $status = is_string($rawStatus) && in_array($rawStatus, ['all', 'published', 'pending', 'draft'], true)
            ? $rawStatus : 'all';
        $rawInstructor = $request->query('instructor');
        $instructor = is_string($rawInstructor) && ctype_digit($rawInstructor) && (int) $rawInstructor > 0
            ? (int) $rawInstructor : null;
        $rawSort = $request->query('sort');
        $sort = is_string($rawSort) && in_array($rawSort, ['newest', 'oldest', 'title'], true)
            ? $rawSort : 'newest';

        $query = Course::query()->with(['instructor', 'category'])
            ->withCount(['lessons', 'enrollments'])
            ->when($search !== '', fn ($q) => $q->where(
                fn ($inner) => $inner->where('title', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhereHas('instructor', fn ($member) => $member
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%'))
            ))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($instructor !== null, fn ($q) => $q->where('instructor_id', $instructor));

        match ($sort) {
            'oldest' => $query->oldest('created_at')->orderBy('id'),
            'title' => $query->orderBy('title')->orderBy('id'),
            default => $query->latest('created_at')->latest('id'),
        };

        $base = Course::query();
        return view('admin.courses.index', [
            'courses' => $query->paginate(20)->withQueryString(),
            'search' => $search, 'status' => $status, 'instructor' => $instructor, 'sort' => $sort,
            'instructors' => User::query()->whereHas('courses')->orderBy('name')->get(['id', 'name']),
            'courseCounts' => [
                'all' => (clone $base)->count(),
                'published' => (clone $base)->where('status', 'published')->count(),
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'draft' => (clone $base)->where('status', 'draft')->count(),
            ],
        ]);
    }
}
