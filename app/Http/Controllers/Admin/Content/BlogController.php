<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Http\Requests\Admin\BlogRequest;
use App\Models\Blog;
use App\Services\Content\BlogPublishingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class BlogController extends Controller
{
    public function index(): View
    {
        return view('admin.content.blog.index', [
            'posts' => Blog::query()->with('author')->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.content.blog.form', ['blog' => new Blog]);
    }

    public function store(BlogRequest $request, BlogPublishingService $publishing): RedirectResponse
    {
        $publishing->save($request->user(), $request->validated());

        return to_route('admin.blogs.index')->with('success', 'Article created.');
    }

    public function edit(Blog $blog): View
    {
        return view('admin.content.blog.form', ['blog' => $blog]);
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
}
