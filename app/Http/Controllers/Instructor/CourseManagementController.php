<?php

namespace App\Http\Controllers\Instructor;

use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CourseManagementController extends Controller
{
    public function create()
    {
        Gate::authorize('create', Course::class);

        return view('instructor.course-form', ['course' => new Course, 'categories' => Category::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Course::class);
        $data = $this->validated($request);
        $data['instructor_id'] = $request->user()->id;
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['status'] = 'draft';
        $data['price'] = 0; // Paid checkout is not implemented: free courses only.
        $course = Course::create($data);

        return redirect()->route('instructor.courses.edit', $course)->with('success', 'Course draft created.');
    }

    public function edit(Course $course)
    {
        Gate::authorize('update', $course);

        return view('instructor.course-form', ['course' => $course->load(['sections.lessons', 'quizzes.questions']), 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Course $course)
    {
        Gate::authorize('update', $course);
        $data = $this->validated($request);
        if ($course->status === 'published' && ! $request->user()->isAdmin()) {
            $data['status'] = 'draft';
        }
        $course->update($data);

        return back()->with('success', 'Course updated.');
    }

    public function submit(Course $course)
    {
        Gate::authorize('update', $course);
        abort_unless($course->sections()->whereHas('lessons')->exists(), 422, 'Add at least one section with a lesson.');
        $course->update(['status' => 'pending']);

        return back()->with('success', 'Course submitted for administrator approval.');
    }

    public function publish(Request $request, Course $course)
    {
        Gate::authorize('publish', $course);
        abort_unless($course->sections()->whereHas('lessons')->exists(), 422, 'Course needs at least one lesson.');
        $course->sections()->update(['is_published' => true]);
        $course->lessons()->update(['is_published' => true]);
        $course->update(['status' => 'published', 'published_at' => now()]);

        return back()->with('success', 'Course published.');
    }

    public function unpublish(Course $course)
    {
        Gate::authorize('publish', $course);
        $course->update(['status' => 'draft', 'published_at' => null]);

        return back()->with('success', 'Course unpublished.');
    }

    public function destroy(Course $course)
    {
        Gate::authorize('delete', $course);
        abort_unless(in_array($course->status, ['draft', 'pending'], true), 403, 'Unpublish before deleting.');
        $course->delete();

        return redirect()->route('dashboard')->with('success', 'Course deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['title' => ['required', 'string', 'max:190'], 'summary' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:20000'], 'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'level' => ['required', 'in:beginner,intermediate,advanced'], 'language_code' => ['required', 'in:en,zh,fr,de,ja,km,ko,vi']]);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'course';
        $slug = $base;
        $i = 1;
        while (Course::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

return $slug;
    }
}
