<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Http\Requests\Instructor\EbookRequest;
use App\Models\Ebook;
use App\Services\Content\EbookLibraryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class EbookController extends Controller
{
    public function index(Request $request): View
    {
        return view('instructor.ebooks.index', [
            'ebooks' => Ebook::query()
                ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('author_id', $request->user()->getKey()))
                ->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('instructor.ebooks.form', ['ebook' => new Ebook]);
    }

    public function store(EbookRequest $request, EbookLibraryService $service): RedirectResponse
    {
        $service->save($request->user(), $request->safe()->except('file'), $request->file('file'));

        return to_route('instructor.ebooks.index')->with('success', 'Ebook draft saved for administrator review.');
    }

    public function edit(Request $request, Ebook $ebook): View
    {
        abort_unless($request->user()->isAdmin() || $ebook->author_id === $request->user()->getKey(), 403);

        return view('instructor.ebooks.form', ['ebook' => $ebook]);
    }

    public function update(EbookRequest $request, Ebook $ebook, EbookLibraryService $service): RedirectResponse
    {
        $service->save($request->user(), $request->safe()->except('file'), $request->file('file'), $ebook);

        return to_route('instructor.ebooks.index')->with('success', 'Ebook updated; administrator approval is required again.');
    }
}
