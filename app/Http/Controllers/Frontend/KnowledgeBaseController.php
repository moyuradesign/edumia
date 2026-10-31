<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Helpers\Localization;
use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

final class KnowledgeBaseController extends Controller
{
    public function index(Request $request, ?string $locale = null): View
    {
        $locale = Localization::resolve($locale);
        App::setLocale($locale);
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        return view('frontend.default.help.index', [
            'locale' => $locale, 'languages' => Localization::LANGUAGES,
            'search' => $search,
            'bases' => KnowledgeBase::query()->where('published', true)
                ->when($search !== '', function ($query) use ($search): void {
                    $term = '%'.addcslashes($search, '%_\\').'%';
                    $query->where(function ($q) use ($term): void {
                        $q->where('title', 'like', $term)
                            ->orWhere('content', 'like', $term)
                            ->orWhereHas('topics', fn ($t) => $t->where('title', 'like', $term));
                    });
                })->withCount('topics')->orderBy('title')->paginate(12)->withQueryString(),
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

        return view('frontend.default.help.show', [
            'locale' => $locale, 'languages' => Localization::LANGUAGES,
            'base' => KnowledgeBase::query()->where('published', true)->where('slug', $slug)
                ->with('topics')->firstOrFail(),
        ]);
    }

    public function topic(Request $request, string $slug, string $topicSlug): View
    {
        return $this->renderTopic($slug, $topicSlug, Localization::resolve());
    }

    public function localizedTopic(Request $request, string $locale, string $slug, string $topicSlug): View
    {
        return $this->renderTopic($slug, $topicSlug, Localization::resolve($locale));
    }

    private function renderTopic(string $slug, string $topicSlug, string $locale): View
    {
        App::setLocale($locale);
        $base = KnowledgeBase::query()->where('published', true)->where('slug', $slug)->firstOrFail();

        return view('frontend.default.help.topic', [
            'locale' => $locale, 'languages' => Localization::LANGUAGES,
            'base' => $base,
            'topic' => $base->topics()->where('slug', $topicSlug)->firstOrFail(),
        ]);
    }
}
