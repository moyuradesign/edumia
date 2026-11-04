<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CourseIndexController extends Controller
{
    public function __invoke(Request $request): View
    {
        $student = $request->user();
        abort_unless($student?->isActive(), 403);

        $filter = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));

        $query = Enrollment::query()
            ->where('user_id', $student->getKey())
            ->whereHas('course')
            ->with('course');

        if (in_array($filter, ['in_progress', 'completed'], true)) {
            $query->where('status', $filter);
        }

        if ($search !== '') {
            $term = addcslashes(mb_substr($search, 0, 80), '%_\\');
            $query->whereHas('course', static fn ($course) =>
                $course->where('title', 'like', '%'.$term.'%')
            );
        }

        return view('student.courses.index', [
            'enrollments' => $query->latest()->paginate(12)->withQueryString(),
            'search' => $search,
            'filter' => $filter,
        ]);
    }
}
