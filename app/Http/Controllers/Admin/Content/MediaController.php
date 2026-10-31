<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Http\Requests\Admin\StoreMediaRequest;
use App\Models\MediaFile;
use App\Services\Content\MediaStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MediaController extends Controller
{
    public function index(): View
    {
        return view('admin.content.media.index', [
            'media' => MediaFile::query()->with('uploader')->latest()->paginate(20),
        ]);
    }

    public function store(StoreMediaRequest $request, MediaStorageService $storage): RedirectResponse
    {
        $storage->upload(
            $request->user(),
            $request->file('file'),
            $request->validated('alt_text'),
        );

        return to_route('admin.media.index')->with('success', 'Media uploaded privately.');
    }

    public function download(MediaFile $media): StreamedResponse
    {
        abort_unless($media->disk === 'local', 404);
        abort_unless(Storage::disk('local')->exists($media->path), 404);

        return Storage::disk('local')->download(
            $media->path,
            preg_replace('/[\x00-\x1f\x7f]/', '', $media->original_name)
        );
    }

    public function destroy(Request $request, MediaFile $media, MediaStorageService $storage): RedirectResponse
    {
        $storage->delete($request->user(), $media);

        return to_route('admin.media.index')->with('success', 'Media deleted.');
    }
}
