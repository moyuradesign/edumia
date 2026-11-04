<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Models\Assignment;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StudentDirectoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $instructorId = $this->teacherId($request);
        [$query, $search, $selectedCourse, $status] = $this->filteredEnrollments($request, $instructorId);

        return view('instructor.students.index', [
            'enrollments' => $query->latest('id')->paginate(20)->withQueryString(),
            'courses' => Course::query()->where('instructor_id', $instructorId)
                ->orderBy('title')->get(['id', 'title']),
            'selectedCourse' => $selectedCourse,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function show(Request $request, Enrollment $enrollment): View
    {
        $instructorId = $this->teacherId($request);

        // Resolve the enrollment through an owned course before accessing
        // any learner's progress, grade, or certificate data.
        $enrollment = Enrollment::query()
            ->whereKey($enrollment->getKey())
            ->whereHas('course', fn (Builder $query) =>
                $query->where('instructor_id', $instructorId))
            ->whereHas('user')
            ->with(['course', 'user'])
            ->firstOrFail();

        $studentId = $enrollment->user_id;
        $courseId = $enrollment->course_id;

        $lessons = Lesson::query()->where('course_id', $courseId)
            ->where('is_published', true)
            ->whereHas('section', fn (Builder $query) => $query->where('is_published', true))
            ->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'section_id', 'title', 'sort_order']);

        $completedLessonIds = LessonProgress::query()
            ->where('user_id', $studentId)
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->pluck('lesson_id')->map(fn ($id) => (int) $id)->all();

        $quizzes = Quiz::query()->where('course_id', $courseId)
            ->where('is_published', true)
            ->orderBy('id')->get(['id', 'course_id', 'title', 'passing_score']);

        $quizAttempts = QuizSubmission::query()
            ->where('user_id', $studentId)
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->orderByDesc('submitted_at')->orderByDesc('id')
            ->get(['id', 'quiz_id', 'score', 'passed', 'submitted_at'])
            ->groupBy('quiz_id');

        $assignments = Assignment::query()->where('course_id', $courseId)
            ->where('published', true)
            ->with(['submissions' => fn ($query) =>
                $query->where('user_id', $studentId)
                    ->select(['id', 'assignment_id', 'user_id', 'score', 'submitted_at', 'graded_at'])])
            ->orderBy('id')->get();

        $certificate = Certificate::query()->where('course_id', $courseId)
            ->where('user_id', $studentId)->whereNull('revoked_at')->first();

        return view('instructor.students.show', compact(
            'enrollment', 'lessons', 'completedLessonIds',
            'quizzes', 'quizAttempts', 'assignments', 'certificate'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $instructorId = $this->teacherId($request);
        [$query] = $this->filteredEnrollments($request, $instructorId);

        return response()->streamDownload(static function () use ($query): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new \RuntimeException('Could not open CSV export stream.');
            }

            fputcsv($output, [
                'Student', 'Course', 'Enrollment status', 'Progress (%)',
                'Enrolled at', 'Completed at',
            ], ',', '"', '');

            $query->orderBy('id')->chunkById(250, static function ($enrollments) use ($output): void {
                foreach ($enrollments as $enrollment) {
                    fputcsv($output, [
                        self::safeCsvCell($enrollment->user->name),
                        self::safeCsvCell($enrollment->course->title),
                        self::safeCsvCell($enrollment->status),
                        (int) $enrollment->progress_percent,
                        $enrollment->enrolled_at?->toDateTimeString() ?? '',
                        $enrollment->completed_at?->toDateTimeString() ?? '',
                    ], ',', '"', '');
                }
            });
            fclose($output);
        }, 'edumia-my-students.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function teacherId(Request $request): int
    {
        abort_unless($request->user()?->canTeach(), 403);

        return (int) $request->user()->getKey();
    }

    private function filteredEnrollments(Request $request, int $instructorId): array
    {
        $rawSearch = $request->query('q');
        $search = is_string($rawSearch) ? trim(mb_substr($rawSearch, 0, 80)) : '';

        $rawCourse = $request->query('course');
        $selectedCourse = is_string($rawCourse) && ctype_digit($rawCourse)
            ? (int) $rawCourse : null;

        $rawStatus = $request->query('status');
        $status = is_string($rawStatus) && in_array($rawStatus, ['active', 'completed'], true)
            ? $rawStatus : '';

        $query = Enrollment::query()
            ->whereHas('course', fn (Builder $courses) =>
                $courses->where('instructor_id', $instructorId))
            ->whereHas('user')
            ->with(['course:id,instructor_id,title', 'user:id,name']);

        if ($selectedCourse !== null) {
            $query->where('course_id', $selectedCourse);
        }
        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($search !== '') {
            $term = addcslashes($search, '%_\\');
            $query->where(static function (Builder $builder) use ($term): void {
                $builder->whereHas('user', fn (Builder $users) =>
                    $users->where('name', 'like', '%'.$term.'%')
                )->orWhereHas('course', fn (Builder $courses) =>
                    $courses->where('title', 'like', '%'.$term.'%'));
            });
        }

        return [$query, $search, $selectedCourse, $status];
    }

    // Spreadsheet applications otherwise interpret untrusted names as formulas.
    private static function safeCsvCell(string $value): string
    {
        return preg_match('/^[\s\x{FEFF}]*[=+@\-]/u', $value) ? "'".$value : $value;
    }
}
