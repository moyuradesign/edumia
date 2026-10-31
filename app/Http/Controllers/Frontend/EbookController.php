<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Helpers\Localization;
use App\Models\Ebook;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class EbookController extends Controller
{
    public function index(): View
    {
        return view('frontend.default.ebooks.index', [
            'locale' => 'en', 'languages' => Localization::LANGUAGES,
            'ebooks' => Ebook::published()->with('author')->latest('published_at')->paginate(12),
        ]);
    }

    public function show(string $slug): View
    {
        return view('frontend.default.ebooks.show', [
            'locale' => 'en', 'languages' => Localization::LANGUAGES,
            'ebook' => Ebook::published()->with('author')->where('slug', $slug)->firstOrFail(),
        ]);
    }
}
