<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\AuditEvent;
use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class MediaStorageService
{
    public function upload(User $actor, UploadedFile $file, ?string $altText): MediaFile
    {
        abort_unless($actor->isAdmin(), 403);

        $disk = Storage::disk('local');
        $path = $disk->putFile('edumia/media', $file);
        if ($path === false) {
            throw new \RuntimeException('Unable to save the uploaded file.');
        }

        try {
            $media = MediaFile::create([
                'user_id' => $actor->getKey(),
                'disk' => 'local',
                'path' => $path,
                'original_name' => basename(str_replace('\\', '/', $file->getClientOriginalName())),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'alt_text' => $altText,
                'visibility' => 'private',
            ]);

            AuditEvent::create([
                'actor_id' => $actor->getKey(),
                'subject_type' => MediaFile::class,
                'subject_id' => $media->getKey(),
                'action' => 'media.uploaded',
                'new_values' => [
                    'original_name' => $media->original_name,
                    'size_bytes' => $media->size_bytes,
                ],
            ]);

            return $media;
        } catch (Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }
    }

    public function delete(User $actor, MediaFile $media): void
    {
        abort_unless($actor->isAdmin(), 403);
        // Keep one well-defined storage disk until a secure multi-disk migration exists.
        abort_unless($media->disk === 'local', 422);

        $media->delete();
        Storage::disk('local')->delete($media->path);

        AuditEvent::create([
            'actor_id' => $actor->getKey(),
            'subject_type' => MediaFile::class,
            'subject_id' => $media->getKey(),
            'action' => 'media.deleted',
            'previous_values' => ['original_name' => $media->original_name],
        ]);
    }
}
