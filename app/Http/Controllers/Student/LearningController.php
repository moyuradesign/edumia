<?php

namespace App\Http\Controllers\Student;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\SubmittedAssignment;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\CourseCompletionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LearningController extends Controller
{
    public function course(Request $request, Course $course)
    {
        $enrollment = Enrollment::where('course_id', $course->id)->where('user_id', $request->user()->id)->firstOrFail();
        $sections = $course->sections()->where('is_published', true)->with(['lessons' => fn ($q) => $q->where('is_published', true)])->get();
        $completed = LessonProgress::where('user_id', $request->user()->id)->whereIn('lesson_id', $course->lessons()->select('id'))->pluck('lesson_id')->all();
        $quizzes = $course->quizzes()->where('is_published', true)->withCount('questions')->get();
        $assignments = Assignment::query()->where('course_id', $course->getKey())
            ->where('published', true)->orderBy('id')->get();
        $assignmentSubmissions = SubmittedAssignment::query()
            ->where('user_id', $request->user()->getKey())
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->get()->keyBy('assignment_id');

        return view('student.learning', ['course' => $course, 'enrollment' => $enrollment, 'sections' => $sections, 'completed' => $completed, 'quizzes' => $quizzes, 'assignments' => $assignments, 'assignmentSubmissions' => $assignmentSubmissions]);
    }

    public function lesson(Request $request, Course $course, Lesson $lesson)
    {
        abort_unless((int) $lesson->course_id === (int) $course->id && $lesson->is_published && $lesson->section->is_published, 404);
        Enrollment::where('course_id', $course->id)->where('user_id', $request->user()->id)->firstOrFail();

        return view('student.lesson', ['course' => $course, 'lesson' => $lesson]);
    }

    public function complete(Request $request, Course $course, Lesson $lesson, CourseCompletionService $service)
    {
        abort_unless((int) $lesson->course_id === (int) $course->id && $lesson->is_published && $lesson->section->is_published, 404);
        $enrollment = Enrollment::where('course_id', $course->id)->where('user_id', $request->user()->id)->firstOrFail();
        LessonProgress::firstOrCreate(['user_id' => $request->user()->id, 'lesson_id' => $lesson->id], ['completed_at' => now()]);
        $service->refresh($enrollment);

        return redirect()->route('learning.course',$course)->with('success','Lesson marked complete.');
    }
}
