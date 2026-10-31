<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Helpers\Localization;
use App\Models\Bootcamp;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

final class BootcampController extends Controller
{
    public function index(Request $request, ?string $locale = null): View
    {
        $locale = Localization::resolve($locale);
        App::setLocale($locale);

        return view('frontend.default.bootcamps.index', [
            'locale' => $locale, 'languages' => Localization::LANGUAGES,
            'bootcamps' => Bootcamp::published()->with('instructor')->latest()->paginate(12),
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

        return view('frontend.default.bootcamps.show', [
            'locale' => $locale, 'languages' => Localization::LANGUAGES,
            'bootcamp' => Bootcamp::published()->with(['instructor', 'modules'])->where('slug', $slug)->firstOrFail(),
        ]);
    }
}
