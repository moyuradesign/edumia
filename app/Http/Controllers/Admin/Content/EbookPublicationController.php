<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Models\Ebook;
use App\Services\Content\EbookLibraryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class EbookPublicationController extends Controller
{
    public function index(): View
    {
        return view('admin.content.ebooks.index', [
            'ebooks' => Ebook::query()->with('author')->latest()->paginate(20),
        ]);
    }

    public function update(Request $request, Ebook $ebook, EbookLibraryService $service): RedirectResponse
    {
        $data = $request->validate(['published' => ['required', 'boolean']]);
        $service->publish($request->user(), $ebook, (bool) $data['published']);

        return to_route('admin.ebooks.index')->with('success', 'Ebook publication updated.');
    }
}
