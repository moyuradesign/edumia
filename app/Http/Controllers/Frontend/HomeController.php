<?php

namespace App\Http\Controllers\Frontend;

use App\Helpers\Localization as Text;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request, string $locale = 'en'): View
    {
        $locale = Text::language($locale);
        App::setLocale($locale);

        return view('frontend.default.home.index', ['locale' => $locale, 'languages' => Text::LANGUAGES,
            'courses' => Course::published()->with('category')->withCount('lessons')->latest('published_at')->limit(6)->get()]);
    }
}
