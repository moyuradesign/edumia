<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Courses;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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

        $rawCategory = $request->query('category');
        $category = is_string($rawCategory) && ctype_digit($rawCategory) && (int) $rawCategory > 0
            ? (int) $rawCategory : null;

        $rawContent = $request->query('content');
        $content = is_string($rawContent) && in_array($rawContent, ['all', 'with_lessons', 'no_lessons'], true)
            ? $rawContent : 'all';

        $rawSort = $request->query('sort');
        $sort = is_string($rawSort) && in_array($rawSort, ['newest', 'oldest', 'title', 'most_enrolled', 'most_lessons'], true)
            ? $rawSort : 'newest';

        // One shared filtered base avoids mismatches between the status cards and results.
        // Status itself is applied only to the listing so each card displays a useful count.
        $base = Course::query()
            ->when($search !== '', fn (Builder $q) => $q->where(
                fn (Builder $inner) => $inner->where('title', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhereHas('instructor', fn (Builder $member) => $member
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%'))
            ))
            ->when($instructor !== null, fn (Builder $q) => $q->where('instructor_id', $instructor))
            ->when($category !== null, fn (Builder $q) => $q->where('category_id', $category))
            ->when($content === 'with_lessons', fn (Builder $q) => $q->whereHas('lessons'))
            ->when($content === 'no_lessons', fn (Builder $q) => $q->whereDoesntHave('lessons'));

        $query = (clone $base)
            ->with(['instructor', 'category'])
            ->withCount(['lessons', 'enrollments'])
            ->when($status !== 'all', fn (Builder $q) => $q->where('status', $status));

        match ($sort) {
            'oldest' => $query->oldest('created_at')->orderBy('id'),
            'title' => $query->orderBy('title')->orderBy('id'),
            'most_enrolled' => $query->orderByDesc('enrollments_count')->latest('id'),
            'most_lessons' => $query->orderByDesc('lessons_count')->latest('id'),
            default => $query->latest('created_at')->latest('id'),
        };

        return view('admin.courses.index', [
            'courses' => $query->paginate(20)->withQueryString(),
            'search' => $search,
            'status' => $status,
            'instructor' => $instructor,
            'category' => $category,
            'content' => $content,
            'sort' => $sort,
            'instructors' => User::query()->whereHas('courses')->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->whereHas('courses')->orderBy('name')->get(['id', 'name']),
            'courseCounts' => [
                'all' => (clone $base)->count(),
                'published' => (clone $base)->where('status', 'published')->count(),
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'draft' => (clone $base)->where('status', 'draft')->count(),
            ],
            'hasActiveFilters' => $search !== '' || $instructor !== null || $category !== null || $content !== 'all',
        ]);
    }
}
