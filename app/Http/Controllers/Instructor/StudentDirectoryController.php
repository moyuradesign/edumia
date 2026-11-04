<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class StudentDirectoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->canTeach(), 403);
        $instructorId = $request->user()->getKey();

        $courses = Course::query()
            ->where('instructor_id', $instructorId)
            ->orderBy('title')
            ->get(['id', 'title']);

        $rawCourse = $request->query('course', '');
        $selectedCourse = is_string($rawCourse) && ctype_digit($rawCourse)
            ? (int) $rawCourse : null;

        $rawSearch = $request->query('q', '');
        $search = is_string($rawSearch) ? trim(mb_substr($rawSearch, 0, 80)) : '';

        $enrollments = Enrollment::query()
            ->whereHas('course', fn ($query) =>
                $query->where('instructor_id', $instructorId)
            )->whereHas('user')
            ->with(['course:id,instructor_id,title', 'user:id,name']);

        if ($selectedCourse !== null) {
            $enrollments->where('course_id', $selectedCourse);
        }

        if ($search !== '') {
            $term = addcslashes($search, '%_\\');
            $enrollments->where(static function ($query) use ($term): void {
                $query->whereHas('user', fn ($users) =>
                    $users->where('name', 'like', '%'.$term.'%')
                )->orWhereHas('course', fn ($courses) =>
                    $courses->where('title', 'like', '%'.$term.'%')
                );
            });
        }

        return view('instructor.students.index', [
            'enrollments' => $enrollments->latest()->paginate(20)->withQueryString(),
            'courses' => $courses,
            'selectedCourse' => $selectedCourse,
            'search' => $search,
        ]);
    }
}
