<?php

namespace App\Http\Controllers\Instructor;

use App\Models\Course;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class QuizManagementController extends Controller
{
    public function store(Request $request, Course $course)
    {
        Gate::authorize('update', $course);
        $data = $request->validate(['title' => ['required', 'string', 'max:190'], 'passing_score' => ['required', 'integer', 'min:0', 'max:100'],
            'attempts_allowed' => ['required', 'integer', 'min:1', 'max:20']]);
        $course->quizzes()->create($data + ['is_published' => false]);

        return back()->with('success', 'Quiz created.');
    }

    public function question(Request $request, Course $course, Quiz $quiz)
    {
        Gate::authorize('update', $course);
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        abort_if($quiz->is_published, 403, 'Published quizzes cannot be edited.');
        $data = $request->validate(['question' => ['required', 'string', 'max:2000'], 'options' => ['required', 'array', 'size:4'],
            'options.*' => ['required', 'string', 'max:350'], 'correct' => ['required', 'integer', 'min:0', 'max:3']]);
        $quiz->questions()->create(['question' => $data['question'], 'type' => 'single_choice', 'options' => array_values($data['options']),
            'correct_answer' => [(int) $data['correct']], 'points' => 1, 'sort_order' => (int) $quiz->questions()->max('sort_order') + 1]);

        return back()->with('success', 'Question added.');
    }

    public function publish(Course $course, Quiz $quiz)
    {
        Gate::authorize('update', $course);
        abort_unless((int) $quiz->course_id === (int) $course->id, 404);
        abort_unless($quiz->questions()->exists(), 422, 'Add at least one question before publishing.');
        $quiz->update(['is_published' => true]);

        return back()->with('success','Quiz published.');
    }
}
