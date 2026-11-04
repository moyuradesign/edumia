<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class CourseIndexController extends Controller
{
    public function __invoke(Request $request): View
    {
        $instructor = $request->user();
        abort_unless($instructor?->canTeach(), 403);

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $query = Course::query()->where('instructor_id', $instructor->getKey())->withCount(['lessons', 'enrollments']);

        if ($search !== '') {
            $query->where('title', 'like', '%'.addcslashes(mb_substr($search, 0, 80), '%_\\').'%');
        }
        if (in_array($status, ['draft', 'pending', 'published', 'rejected'], true)) {
            $query->where('status', $status);
        }

        return view('instructor.courses.index', [
            'courses' => $query->latest()->paginate(12)->withQueryString(),
            'search' => $search,
            'status' => $status,
        ]);
    }
}
