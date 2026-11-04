<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Models\TeamTrainingPackage;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TeamTrainingController extends Controller
{
    public function index(): View
    {
        return view('frontend.default.teams.index', [
            'packages' => TeamTrainingPackage::query()->where('status', 'active')->where('price', 0)
                ->whereHas('course', fn ($query) => $query->published()->where('price', 0))
                ->with('course:id,title,slug')->orderByDesc('id')->paginate(12),
            'locale' => \App\Helpers\Localization::resolve(),
        ]);
    }
}
