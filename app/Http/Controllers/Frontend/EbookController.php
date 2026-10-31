<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Helpers\Localization;
use App\Models\Ebook;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

final class EbookController extends Controller
{
    public function index(Request $request, ?string $locale = null): View
    {
        $locale = Localization::resolve($locale);
        App::setLocale($locale);

        return view('frontend.default.ebooks.index', [
            'locale' => $locale, 'languages' => Localization::LANGUAGES,
            'ebooks' => Ebook::published()->with('author')->latest('published_at')->paginate(12),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        return $this->render($slug, Localization::resolve());
    }

    public function localizedShow(Request $request, string $locale, string $slug): View
    {
        return $this->render($slug, Localization::resolve($locale));
    }

    private function render(string $slug, string $locale): View
    {
        App::setLocale($locale);

        return view('frontend.default.ebooks.show', [
            'locale' => $locale, 'languages' => Localization::LANGUAGES,
            'ebook' => Ebook::published()->with('author')->where('slug', $slug)->firstOrFail(),
        ]);
    }
}
