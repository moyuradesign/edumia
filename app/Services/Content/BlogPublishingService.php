<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\AuditEvent;
use App\Models\Blog;
use App\Models\MediaFile;
use App\Services\Content\BlogContentRenderer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class BlogPublishingService
{
    public function save(User $actor, array $attributes, ?Blog $blog = null): Blog
    {
        abort_unless($actor->isAdmin(), 403);

        $ids = app(BlogContentRenderer::class)->mediaIds($attributes['body']);
        if (! empty($attributes['featured_media_id'])) {
            $ids[] = (int) $attributes['featured_media_id'];
        }
        $ids = array_values(array_unique($ids));
        if (count($ids) > 40) {
            throw ValidationException::withMessages(['body' => 'A post may use up to 40 library images.']);
        }
        if ($ids !== []) {
            $available = MediaFile::query()->whereIn('id', $ids)->where('disk', 'local')
                ->whereIn('mime_type', ['image/jpeg', 'image/png', 'image/webp'])->get();
            if ($available->count() !== count($ids) ||
                $available->contains(fn (MediaFile $image) => $image->visibility !== 'private'
                    || ! str_starts_with($image->path, 'edumia/media/')
                    || str_contains($image->path, '..')
                    || ! Storage::disk('local')->exists($image->path))) {
                throw ValidationException::withMessages(['featured_media_id' => 'Select available image files from the Media Library.']);
            }
        }

        return DB::transaction(function () use ($actor, $attributes, $blog): Blog {
            $blog = $blog
                ? Blog::query()->lockForUpdate()->findOrFail($blog->getKey())
                : new Blog;

            $before = $blog->exists ? $blog->only(['title', 'slug', 'status', 'published_at', 'category_id', 'featured_media_id']) : null;
            $wasPublished = $blog->exists && $blog->status === 'published';

            $blog->fill($attributes);
            if (! $blog->exists) {
                $blog->author_id = $actor->getKey();
            }
            $blog->published_at = $attributes['status'] === 'published'
                ? ($wasPublished ? $blog->published_at : now())
                : null;
            $blog->save();

            AuditEvent::create([
                'actor_id' => $actor->getKey(),
                'subject_type' => Blog::class,
                'subject_id' => $blog->getKey(),
                'action' => $before === null ? 'blogs.created' : 'blogs.updated',
                'previous_values' => $before,
                'new_values' => $blog->only(['title', 'slug', 'status', 'published_at', 'category_id', 'featured_media_id']),
            ]);

            return $blog;
        });
    }

    public function delete(User $actor, Blog $blog): void
    {
        abort_unless($actor->isAdmin(), 403);

        DB::transaction(function () use ($actor, $blog): void {
            $blog = Blog::query()->lockForUpdate()->findOrFail($blog->getKey());
            $before = $blog->only(['title', 'slug', 'status']);
            $id = $blog->getKey();
            $blog->delete();
            AuditEvent::create([
                'actor_id' => $actor->getKey(),
                'subject_type' => Blog::class,
                'subject_id' => $id,
                'action' => 'blogs.deleted',
                'previous_values' => $before,
            ]);
        });
    }
}
