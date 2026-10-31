<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Helpers\Localization;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

final class BlogController extends Controller
{
    public function index(Request $request, ?string $locale = null): View
    {
        $locale = Localization::resolve($locale);
        App::setLocale($locale);

        return view('frontend.default.blog.index', [
            'locale' => $locale,
            'languages' => Localization::LANGUAGES,
            'posts' => Blog::published()->with('author')->latest('published_at')->paginate(12),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        return $this->render($slug, 'en');
    }

    public function localizedShow(Request $request, string $locale, string $slug): View
    {
        return $this->render($slug, $locale);
    }

    private function render(string $slug, string $locale): View
    {
        $locale = Localization::language($locale);
        App::setLocale($locale);

        return view('frontend.default.blog.show', [
            'locale' => $locale,
            'languages' => Localization::LANGUAGES,
            'post' => Blog::published()->with('author')->where('slug', $slug)->firstOrFail(),
        ]);
    }
}
