<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\AuditEvent;
use App\Models\Ebook;
use App\Models\EbookPurchase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class EbookLibraryService
{
    public function save(User $actor, array $data, ?UploadedFile $upload = null, ?Ebook $ebook = null): Ebook
    {
        abort_unless($actor->canTeach(), 403);
        abort_if($ebook && ! $actor->isAdmin() && $ebook->author_id !== $actor->getKey(), 403);

        $path = $upload ? Storage::disk('local')->putFile('edumia/ebooks', $upload) : null;
        if ($upload && $path === false) {
            throw new \RuntimeException('Unable to store the PDF privately.');
        }
        $oldFile = null;

        try {
            $saved = DB::transaction(function () use ($actor, $data, $path, $ebook, &$oldFile): Ebook {
                if ($ebook !== null) {
                    $ebook = Ebook::query()->lockForUpdate()->findOrFail($ebook->getKey());
                    abort_unless($actor->isAdmin() || $ebook->author_id === $actor->getKey(), 403);
                    $oldFile = $path ? $ebook->file_path : null;
                } else {
                    $ebook = new Ebook;
                    $ebook->author_id = $actor->getKey();
                }
                $before = $ebook->exists ? $ebook->only(['title', 'slug', 'price', 'status']) : null;
                $ebook->fill([
                    'title' => $data['title'],
                    'slug' => $data['slug'],
                    'description' => $data['description'] ?? null,
                    'price' => $data['price'],
                ]);
                if ($path) {
                    $ebook->file_path = $path;
                }
                // Changed content is never published without a fresh admin review.
                $ebook->status = 'draft';
                $ebook->published_at = null;
                $ebook->save();

                AuditEvent::create([
                    'actor_id' => $actor->getKey(),
                    'subject_type' => Ebook::class,
                    'subject_id' => $ebook->getKey(),
                    'action' => $before ? 'ebooks.updated' : 'ebooks.created',
                    'previous_values' => $before,
                    'new_values' => $ebook->only(['title', 'slug', 'price', 'status']),
                ]);

                return $ebook;
            });
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        if ($oldFile) {
            Storage::disk('local')->delete($oldFile);
        }

        return $saved;
    }

    public function publish(User $admin, Ebook $ebook, bool $published): void
    {
        abort_unless($admin->isAdmin(), 403);

        DB::transaction(function () use ($admin, $ebook, $published): void {
            $ebook = Ebook::query()->lockForUpdate()->findOrFail($ebook->getKey());
            if ($published) {
                if (! $ebook->isFree()) {
                    throw ValidationException::withMessages([
                        'ebook' => 'Paid ebooks require verified checkout, which is not implemented.',
                    ]);
                }
                if (! $ebook->file_path || ! Storage::disk('local')->exists($ebook->file_path)) {
                    throw ValidationException::withMessages(['ebook' => 'Upload a PDF before publishing.']);
                }
            }
            $before = $ebook->status;
            $ebook->forceFill([
                'status' => $published ? 'published' : 'draft',
                'published_at' => $published ? now() : null,
            ])->save();

            AuditEvent::create([
                'actor_id' => $admin->getKey(),
                'subject_type' => Ebook::class,
                'subject_id' => $ebook->getKey(),
                'action' => $published ? 'ebooks.published' : 'ebooks.unpublished',
                'previous_values' => ['status' => $before],
                'new_values' => ['status' => $ebook->status],
            ]);
        });
    }

    public function claim(User $reader, Ebook $ebook): EbookPurchase
    {
        abort_unless($reader->isActive(), 403);
        abort_unless($ebook->status === 'published' && $ebook->published_at?->isPast(), 404);
        abort_unless($ebook->isFree(), 403, 'Paid ebooks require verified checkout.');

        return DB::transaction(function () use ($reader, $ebook): EbookPurchase {
            $ebook = Ebook::query()->lockForUpdate()->findOrFail($ebook->getKey());
            abort_unless($ebook->status === 'published' && $ebook->published_at?->isPast(), 404);
            abort_unless($ebook->isFree(), 403);
            $existing = EbookPurchase::query()->where('ebook_id', $ebook->getKey())
                ->where('user_id', $reader->getKey())->lockForUpdate()->first();

            if ($existing) {
                abort_unless($existing->status === 'completed' && (float) $existing->amount === 0.0, 403);
                return $existing;
            }

            return EbookPurchase::create([
                'ebook_id' => $ebook->getKey(), 'user_id' => $reader->getKey(),
                'amount' => 0, 'currency_code' => 'USD',
                'status' => 'completed', 'purchased_at' => now(),
            ]);
        }, 3);
    }

    public function authorizeDownload(User $reader, Ebook $ebook): void
    {
        abort_unless($reader->isActive(), 403);
        abort_unless(
            $reader->isAdmin() || $ebook->author_id === $reader->getKey()
            || EbookPurchase::query()->where('ebook_id', $ebook->getKey())
                ->where('user_id', $reader->getKey())->where('status', 'completed')->exists(),
            403
        );
        abort_unless($ebook->file_path && Storage::disk('local')->exists($ebook->file_path), 404);
    }
}
