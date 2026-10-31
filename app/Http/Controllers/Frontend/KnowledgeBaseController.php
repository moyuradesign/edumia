<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Helpers\Localization;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeBaseTopic;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class KnowledgeBaseController extends Controller
{
    public function index(Request $request): View
    {
        return view('frontend.default.help.index', [
            'locale' => 'en', 'languages' => Localization::LANGUAGES,
            'search' => trim((string) $request->query('q', '')),
            'bases' => KnowledgeBase::query()->where('published', true)
                ->when($request->filled('q'), function ($query) use ($request): void {
                    $term = '%'.addcslashes((string) $request->query('q'), '%_\\').'%';
                    $query->where(function ($q) use ($term): void {
                        $q->where('title', 'like', $term)
                            ->orWhere('content', 'like', $term)
                            ->orWhereHas('topics', fn ($t) => $t->where('title', 'like', $term));
                    });
                })->withCount('topics')->orderBy('title')->paginate(12)->withQueryString(),
        ]);
    }

    public function show(string $slug): View
    {
        return view('frontend.default.help.show', [
            'locale' => 'en', 'languages' => Localization::LANGUAGES,
            'base' => KnowledgeBase::query()->where('published', true)->where('slug', $slug)
                ->with('topics')->firstOrFail(),
        ]);
    }

    public function topic(string $slug, string $topicSlug): View
    {
        $base = KnowledgeBase::query()->where('published', true)->where('slug', $slug)->firstOrFail();

        return view('frontend.default.help.topic', [
            'locale' => 'en', 'languages' => Localization::LANGUAGES,
            'base' => $base,
            'topic' => $base->topics()->where('slug', $topicSlug)->firstOrFail(),
        ]);
    }
}
