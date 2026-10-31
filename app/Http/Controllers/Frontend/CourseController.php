<?php

namespace App\Http\Controllers\Frontend;

use App\Helpers\Localization as Text;
use App\Models\Course;
use App\Models\Review;
use App\Models\Enrollment;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request, string $locale = 'en'): View
    {
        $locale = Text::language($locale);
        App::setLocale($locale);
        $query = substr(trim((string) $request->query('q', '')), 0, 100);
        $courses = Course::published()->with('category')->withCount('lessons')
            ->when($query !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('title', 'like', '%'.$query.'%')->orWhere('summary', 'like', '%'.$query.'%')))
            ->latest('published_at')->paginate(12)->withQueryString();

        return view('frontend.default.course.index', compact('locale', 'query', 'courses') + ['languages' => Text::LANGUAGES]);
    }

    public function show(Request $request, string $slug): View
    {
        return $this->renderShow($slug, 'en');
    }

    public function localizedShow(Request $request, string $locale, string $slug): View
    {
        return $this->renderShow($slug, $locale);
    }

    private function renderShow(string $slug, string $locale): View
    {
        $locale = Text::language($locale);
        App::setLocale($locale);
        $course = Course::published()->where('slug', $slug)->with(['instructor', 'category', 'sections' => fn ($q) => $q->where('is_published', true),
            'sections.lessons' => fn ($q) => $q->where('is_published', true)])->withCount('lessons')->firstOrFail();

        $userId = auth()->id();
        $reviews = Review::query()->approved()
            ->where('course_id', $course->getKey())->with('author')->latest()->limit(20)->get();
        $existingReview = $userId
            ? Review::query()->where('course_id', $course->getKey())->where('user_id', $userId)->first()
            : null;
        $enrolled = $userId
            ? Enrollment::query()->where('user_id', $userId)->where('course_id', $course->getKey())
                ->where('status', 'active')->exists()
            : false;
        $saved = $userId
            ? Wishlist::query()->where('user_id', $userId)->where('course_id', $course->getKey())->exists()
            : false;

        return view('frontend.default.course.course_details', [
            'locale' => $locale,
            'languages' => Text::LANGUAGES,
            'course' => $course,
            'reviews' => $reviews,
            'existingReview' => $existingReview,
            'enrolled' => $enrolled,
            'saved' => $saved,
        ]);
    }
}
