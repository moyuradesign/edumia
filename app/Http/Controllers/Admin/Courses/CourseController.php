<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Courses;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CourseController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $query = $request->query('q');
        $search = is_string($query) ? mb_substr(trim($query), 0, 100) : '';
        $requestedStatus = $request->query('status');
        $status = is_string($requestedStatus) && in_array($requestedStatus,
            ['all', 'published', 'pending', 'draft'], true) ? $requestedStatus : 'all';

        $courses = Course::query()
            ->with(['instructor', 'category'])
            ->withCount('lessons')
            ->when($search !== '', fn ($q) => $q->where(
                fn ($inner) => $inner->where('title', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
            ))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest('created_at')->paginate(20)->withQueryString();

        return view('admin.courses.index', compact('courses', 'search', 'status'));
    }
}
