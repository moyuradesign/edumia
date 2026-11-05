<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Helpers\Localization;
use App\Models\Blog;
use App\Models\MediaFile;
use App\Services\Content\BlogContentRenderer;
use Illuminate\Support\Facades\Storage;
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
            'posts' => Blog::published()->with(['author', 'category', 'featuredImage'])
                ->latest('published_at')->paginate(12),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        return $this->render($slug, Localization::resolve());
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
            'post' => Blog::published()->with(['author', 'category', 'featuredImage'])
                ->where('slug', $slug)->firstOrFail(),
            'renderer' => app(BlogContentRenderer::class),
        ]);
    }

    public function image(string $slug, MediaFile $media): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $blog = Blog::published()->where('slug', $slug)->firstOrFail();
        abort_unless(
            $media->disk === 'local'
            && in_array($media->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true)
            && (
                (int) $blog->featured_media_id === (int) $media->getKey()
                || in_array((int) $media->getKey(),
                    app(BlogContentRenderer::class)->mediaIds($blog->body), true)
            )
            && Storage::disk('local')->exists($media->path),
            404
        );

        return Storage::disk('local')->response($media->path, null, [
            'Content-Type' => $media->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

}
