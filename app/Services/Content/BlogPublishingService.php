<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\AuditEvent;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class BlogPublishingService
{
    public function save(User $actor, array $attributes, ?Blog $blog = null): Blog
    {
        abort_unless($actor->isAdmin(), 403);

        return DB::transaction(function () use ($actor, $attributes, $blog): Blog {
            $blog = $blog
                ? Blog::query()->lockForUpdate()->findOrFail($blog->getKey())
                : new Blog;

            $before = $blog->exists ? $blog->only(['title', 'slug', 'status', 'published_at']) : null;
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
                'new_values' => $blog->only(['title', 'slug', 'status', 'published_at']),
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
