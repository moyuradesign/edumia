<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Http\Requests\Admin\StoreMediaRequest;
use App\Models\MediaFile;
use App\Services\Content\MediaStorageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $requestedSearch = $request->query('q');
        $search = is_string($requestedSearch) ? mb_substr(trim($requestedSearch), 0, 100) : '';
        $requestedType = $request->query('type');
        $type = is_string($requestedType) && in_array($requestedType, ['all', 'image', 'document'], true)
            ? $requestedType : 'all';

        $rawDescription = $request->query('description');
        $description = is_string($rawDescription) && in_array($rawDescription, ['all', 'missing'], true)
            ? $rawDescription : 'all';

        $files = MediaFile::query()->with('uploader')->latest('created_at')->latest('id');

        if ($search !== '') {
            $files->where(static function (Builder $query) use ($search): void {
                $query->where('original_name', 'like', '%'.$search.'%')
                    ->orWhere('alt_text', 'like', '%'.$search.'%');
            });
        }

        if ($description === 'missing') {
            $files->where(static function (Builder $query): void {
                $query->whereNull('alt_text')->orWhere('alt_text', '');
            });
        }

        if ($type === 'image') {
            $files->where('mime_type', 'like', 'image/%');
        } elseif ($type === 'document') {
            $files->where('mime_type', 'application/pdf');
        }

        return view('admin.content.media.index', [
            'media' => $files->paginate(20)->withQueryString(),
            'search' => $search,
            'type' => $type,
            'description' => $description,
            'fileCount' => MediaFile::query()->count(),
            'imageCount' => MediaFile::query()->where('mime_type', 'like', 'image/%')->count(),
            'documentCount' => MediaFile::query()->where('mime_type', 'application/pdf')->count(),
            'totalBytes' => (int) MediaFile::query()->sum('size_bytes'),
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

    public function preview(MediaFile $media): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless(
            $media->disk === 'local' && $media->visibility === 'private'
                && in_array($media->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true),
            404
        );
        $disk = Storage::disk('local');
        abort_unless($disk->exists($media->path), 404);

        return $disk->response($media->path, null, [
            'Content-Type' => $media->mime_type,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ], 'inline');
    }

    public function update(Request $request, MediaFile $media, MediaStorageService $storage): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('media.manage'), 403);
        $validated = $request->validate([
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);
        $storage->updateDescription($request->user(), $media, $validated['alt_text'] ?? null);

        return to_route('admin.media.index')->with('success', 'Media description updated.');
    }

    public function destroy(Request $request, MediaFile $media, MediaStorageService $storage): RedirectResponse
    {
        $storage->delete($request->user(), $media);

        return to_route('admin.media.index')->with('success', 'Media deleted.');
    }
}
