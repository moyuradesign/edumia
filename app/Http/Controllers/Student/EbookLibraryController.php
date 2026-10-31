<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Models\Ebook;
use App\Models\EbookPurchase;
use App\Services\Content\EbookLibraryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class EbookLibraryController extends Controller
{
    public function index(Request $request): View
    {
        return view('student.ebooks.index', [
            'books' => EbookPurchase::query()
                ->where('user_id', $request->user()->getKey())->where('status', 'completed')
                ->with('ebook')->latest()->paginate(20),
        ]);
    }

    public function claim(Request $request, Ebook $ebook, EbookLibraryService $service): RedirectResponse
    {
        $service->claim($request->user(), $ebook);

        return to_route('ebooks.library')->with('success', 'The free ebook was added to your library.');
    }

    public function download(Request $request, Ebook $ebook, EbookLibraryService $service): StreamedResponse
    {
        $service->authorizeDownload($request->user(), $ebook);

        return Storage::disk('local')->download($ebook->file_path, $ebook->slug.'.pdf', [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
