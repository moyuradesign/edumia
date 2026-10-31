<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\AuditEvent;
use App\Models\Bootcamp;
use App\Models\BootcampModule;
use App\Models\BootcampPurchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BootcampService
{
    public function assertOwner(User $actor, Bootcamp $bootcamp): void
    {
        abort_unless($actor->isAdmin() || ($actor->canTeach()
            && (int) $bootcamp->instructor_id === (int) $actor->getKey()), 403);
    }

    public function save(User $actor, array $data, ?Bootcamp $bootcamp = null): Bootcamp
    {
        abort_unless($actor->canTeach(), 403);
        if ($bootcamp) {
            $this->assertOwner($actor, $bootcamp);
        }

        return DB::transaction(function () use ($actor, $data, $bootcamp): Bootcamp {
            $bootcamp = $bootcamp
                ? Bootcamp::query()->lockForUpdate()->findOrFail($bootcamp->getKey())
                : new Bootcamp;

            if ($bootcamp->exists) {
                $this->assertOwner($actor, $bootcamp);
                $enrolled = $bootcamp->registrations()->where('status', 'completed')->count();
                if ($enrolled > (int) $data['capacity']) {
                    throw ValidationException::withMessages([
                        'capacity' => 'Capacity cannot be lower than confirmed registrations.',
                    ]);
                }
                if ($enrolled > 0 && (float) $data['price'] !== (float) $bootcamp->price) {
                    throw ValidationException::withMessages([
                        'price' => 'Price cannot change after confirmed registrations.',
                    ]);
                }
            } else {
                $bootcamp->instructor_id = $actor->getKey();
            }

            $before = $bootcamp->exists ? $bootcamp->only(['title', 'status']) : null;
            $bootcamp->fill($data);
            $bootcamp->status = 'draft';
            $bootcamp->save();

            AuditEvent::create([
                'actor_id' => $actor->getKey(),
                'subject_type' => Bootcamp::class,
                'subject_id' => $bootcamp->getKey(),
                'action' => $before ? 'bootcamps.updated' : 'bootcamps.created',
                'previous_values' => $before,
                'new_values' => $bootcamp->only(['title', 'status', 'capacity']),
            ]);

            return $bootcamp;
        });
    }

    public function module(User $actor, Bootcamp $bootcamp, array $data): BootcampModule
    {
        $this->assertOwner($actor, $bootcamp);

        return DB::transaction(fn (): BootcampModule =>
            $bootcamp->modules()->create($data)
        );
    }

    public function session(User $actor, Bootcamp $bootcamp, array $data): void
    {
        $this->assertOwner($actor, $bootcamp);

        DB::transaction(function () use ($actor, $bootcamp, $data): void {
            $bootcamp->sessions()->create(array_merge($data, [
                'instructor_id' => $bootcamp->instructor_id,
            ]));
        });
    }

    public function publish(User $admin, Bootcamp $bootcamp, bool $published): void
    {
        abort_unless($admin->isAdmin(), 403);

        DB::transaction(function () use ($admin, $bootcamp, $published): void {
            $bootcamp = Bootcamp::query()->lockForUpdate()->findOrFail($bootcamp->getKey());
            if ($published) {
                if (! $bootcamp->isFree()) {
                    throw ValidationException::withMessages([
                        'bootcamp' => 'Paid bootcamps require verified checkout, which is not configured.',
                    ]);
                }
                if (! $bootcamp->modules()->exists()) {
                    throw ValidationException::withMessages(['bootcamp' => 'Create at least one module first.']);
                }
            }
            $before = $bootcamp->status;
            $bootcamp->status = $published ? 'published' : 'draft';
            $bootcamp->save();
            AuditEvent::create([
                'actor_id' => $admin->getKey(),
                'subject_type' => Bootcamp::class,
                'subject_id' => $bootcamp->getKey(),
                'action' => $published ? 'bootcamps.published' : 'bootcamps.unpublished',
                'previous_values' => ['status' => $before],
                'new_values' => ['status' => $bootcamp->status],
            ]);
        });
    }

    public function enroll(User $student, Bootcamp $bootcamp): BootcampPurchase
    {
        abort_unless($student->isActive(), 403);

        return DB::transaction(function () use ($student, $bootcamp): BootcampPurchase {
            $bootcamp = Bootcamp::query()->lockForUpdate()->findOrFail($bootcamp->getKey());
            abort_unless($bootcamp->status === 'published', 404);
            abort_unless($bootcamp->isFree(), 403, 'Paid bootcamps require verified checkout.');

            $existing = BootcampPurchase::query()->where('bootcamp_id', $bootcamp->getKey())
                ->where('user_id', $student->getKey())->first();

            if ($existing) {
                abort_unless($existing->status === 'completed' && (float) $existing->amount === 0.0, 403);
                return $existing;
            }

            $count = $bootcamp->registrations()->where('status', 'completed')->count();
            if ($bootcamp->capacity !== null && $count >= $bootcamp->capacity) {
                throw ValidationException::withMessages(['bootcamp' => 'This cohort is full.']);
            }

            return BootcampPurchase::create([
                'bootcamp_id' => $bootcamp->getKey(),
                'user_id' => $student->getKey(),
                'amount' => 0,
                'currency_code' => 'USD',
                'status' => 'completed',
            ]);
        }, 3);
    }

    public function assertCanAttend(User $actor, Bootcamp $bootcamp): void
    {
        abort_unless($actor->isActive(), 403);
        if ($actor->isAdmin() || $bootcamp->instructor_id === $actor->getKey()) {
            return;
        }

        abort_unless(BootcampPurchase::query()
            ->where('bootcamp_id', $bootcamp->getKey())
            ->where('user_id', $actor->getKey())
            ->where('status', 'completed')->exists(), 403);
    }
}
