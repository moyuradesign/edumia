<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Http\Requests\Admin\BlogRequest;
use App\Http\Requests\Admin\StoreMediaRequest;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\MediaFile;
use App\Services\Content\BlogContentRenderer;
use App\Services\Content\BlogPublishingService;
use App\Services\Content\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class BlogController extends Controller
{
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function index(): View
    {
        return view('admin.content.blog.index', [
            'posts' => Blog::query()->with(['author', 'category'])->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Blog);
    }

    public function edit(Blog $blog): View
    {
        return $this->form($blog);
    }

    private function form(Blog $blog): View
    {
        $images = MediaFile::query()->where('disk', 'local')
            ->whereIn('mime_type', self::IMAGE_MIMES)
            ->latest('id')->limit(24)->get();

        $selected = $blog->featured_media_id
            ? MediaFile::query()->whereKey($blog->featured_media_id)
                ->where('disk', 'local')->whereIn('mime_type', self::IMAGE_MIMES)->first()
            : null;

        return view('admin.content.blog.form', [
            'blog' => $blog,
            'categories' => BlogCategory::query()->orderBy('name')->get(),
            'images' => $images,
            'featured' => $selected,
        ]);
    }

    public function store(BlogRequest $request, BlogPublishingService $publishing): RedirectResponse
    {
        $publishing->save($request->user(), $request->validated());

        return to_route('admin.blogs.index')->with('success', 'Article created.');
    }

    public function update(BlogRequest $request, Blog $blog, BlogPublishingService $publishing): RedirectResponse
    {
        $publishing->save($request->user(), $request->validated(), $blog);

        return to_route('admin.blogs.index')->with('success', 'Article updated.');
    }

    public function destroy(Request $request, Blog $blog, BlogPublishingService $publishing): RedirectResponse
    {
        $publishing->delete($request->user(), $blog);

        return to_route('admin.blogs.index')->with('success', 'Article deleted.');
    }

    public function preview(Request $request, BlogContentRenderer $renderer): JsonResponse
    {
        $request->validate(['body' => ['required', 'string', 'max:100000']]);

        return response()->json(['html' => $renderer->render((string) $request->input('body'))]);
    }

    public function addCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('blog_categories', 'name')],
        ]);
        $slug = Str::slug($data['name']);
        if ($slug === '') {
            return response()->json(['message' => 'Choose a category name that can form a URL slug.'], 422);
        }
        $base = mb_substr($slug, 0, 178);
        $slug = $base;
        $number = 2;
        while (BlogCategory::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$number++;
        }
        $category = BlogCategory::query()->create(['name' => $data['name'], 'slug' => $slug]);

        return response()->json([
            'id' => $category->getKey(), 'name' => $category->name,
        ], 201);
    }

    public function mediaSearch(Request $request): JsonResponse
    {
        $term = $request->query('q');
        $search = is_string($term) ? trim(mb_substr($term, 0, 80)) : '';
        $page = $request->query('page');
        $page = is_string($page) && ctype_digit($page) ? max(1, min(1000, (int) $page)) : 1;

        $query = MediaFile::query()->where('disk', 'local')
            ->whereIn('mime_type', self::IMAGE_MIMES);
        if ($search !== '') {
            $escaped = addcslashes($search, '%_\\');
            $query->where(fn ($q) => $q->where('original_name', 'like', '%'.$escaped.'%')
                ->orWhere('alt_text', 'like', '%'.$escaped.'%'));
        }
        $images = $query->orderByDesc('id')->paginate(24, ['id', 'original_name', 'alt_text'], 'page', $page);

        return response()->json([
            'images' => $images->map(fn (MediaFile $item) => [
                'id' => $item->getKey(),
                'name' => $item->original_name,
                'alt' => $item->alt_text ?: $item->original_name,
                'url' => route('admin.blogs.media.preview', $item),
            ]),
            'next_page' => $images->hasMorePages() ? $images->currentPage() + 1 : null,
        ]);
    }

    public function uploadImage(StoreMediaRequest $request, MediaStorageService $media): JsonResponse
    {
        if (! in_array($request->file('file')->getMimeType(), self::IMAGE_MIMES, true)) {
            return response()->json(['message' => 'Choose a JPEG, PNG or WebP image.'], 422);
        }
        $image = $media->upload($request->user(), $request->file('file'), $request->validated('alt_text'));

        return response()->json([
            'id' => $image->getKey(),
            'name' => $image->original_name,
            'alt' => $image->alt_text ?: $image->original_name,
            'url' => route('admin.blogs.media.preview', $image),
        ], 201);
    }

    public function image(MediaFile $media): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless($media->disk === 'local'
            && in_array($media->mime_type, self::IMAGE_MIMES, true)
            && Storage::disk('local')->exists($media->path), 404);

        return Storage::disk('local')->response($media->path, null, [
            'Content-Type' => $media->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
