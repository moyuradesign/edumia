<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Helpers\Localization;
use App\Models\Bootcamp;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class BootcampController extends Controller
{
    public function index(): View
    {
        return view('frontend.default.bootcamps.index', [
            'locale' => 'en', 'languages' => Localization::LANGUAGES,
            'bootcamps' => Bootcamp::published()->with('instructor')->latest()->paginate(12),
        ]);
    }

    public function show(string $slug): View
    {
        return view('frontend.default.bootcamps.show', [
            'locale' => 'en', 'languages' => Localization::LANGUAGES,
            'bootcamp' => Bootcamp::published()->with(['instructor', 'modules'])->where('slug', $slug)->firstOrFail(),
        ]);
    }
}
