<?php

namespace App\Http\Controllers\frontend;

use App\Helpers\Common_helper as Text;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

final class CourseController extends Controller
{
    public function index(Request $request, string $locale = 'en'): View
    {
        $locale = Text::language($locale);
        App::setLocale($locale);
        $query = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        return view('frontend.default.course.index', [
            'locale' => $locale, 'languages' => Text::LANGUAGES,
            'query' => $query, 'courses' => Course::searchPreviews($query),
        ]);
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
        $course = Course::findPreview($slug);
        abort_unless($course, 404);
        return view('frontend.default.course.course_details', [
            'locale' => $locale, 'languages' => Text::LANGUAGES, 'course' => $course,
        ]);
    }
}
