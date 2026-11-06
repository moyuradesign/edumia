<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\AuditEvent;
use App\Models\Course;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Administrative instructor-only changes. Never promote to an administrator
 * or transfer courses with learner/commercial entitlements.
 */
final class InstructorManagementService
{
    public function changeStatus(User $actor, User $instructor, string $status, string $reason): void
    {
        abort_unless($actor->isAdmin(), 403);
        abort_unless(in_array($status, ['active', 'suspended'], true), 422);

        DB::transaction(function () use ($actor, $instructor, $status, $reason): void {
            $locked = User::query()->lockForUpdate()->findOrFail($instructor->getKey());
            abort_unless($locked->role === 'instructor', 404);

            if ($locked->status === $status) {
                throw ValidationException::withMessages([
                    'status' => 'This instructor already has the selected status.',
                ]);
            }

            $previous = $locked->status;
            $locked->forceFill(['status' => $status])->save();

            AuditEvent::create([
                'actor_id' => $actor->getKey(),
                'subject_type' => User::class,
                'subject_id' => $locked->getKey(),
                'action' => 'instructors.status_changed',
                'previous_values' => ['status' => $previous],
                'new_values' => ['status' => $status, 'reason' => trim($reason)],
            ]);
        }, 3);
    }

    public function reassign(User $actor, User $from, Course $course, User $to, string $reason): void
    {
        abort_unless($actor->isAdmin(), 403);

        DB::transaction(function () use ($actor, $from, $course, $to, $reason): void {
            // Lock course first, then instructor accounts in a stable order.
            $lockedCourse = Course::query()->lockForUpdate()->findOrFail($course->getKey());
            abort_unless((int) $lockedCourse->instructor_id === (int) $from->getKey(), 404);

            $instructors = User::query()
                ->whereIn('id', [$from->getKey(), $to->getKey()])
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $source = $instructors->get($from->getKey());
            $target = $instructors->get($to->getKey());
            abort_unless($source?->role === 'instructor', 404);

            if (! $target || $target->role !== 'instructor' || ! $target->isActive()
                || $target->is($source)) {
                throw ValidationException::withMessages([
                    'new_instructor_id' => 'Choose another active instructor as the destination.',
                ]);
            }

            // A published or financially/academically committed course is not
            // a simple staff assignment. Preserve learners and revenue ownership.
            if (! in_array($lockedCourse->status, ['draft', 'pending'], true)
                || $lockedCourse->published_at !== null
                || $lockedCourse->enrollments()->exists()
                || OrderItem::query()->where('course_id', $lockedCourse->getKey())->exists()) {
                throw ValidationException::withMessages([
                    'new_instructor_id' => 'Only unpublished draft or pending courses with no enrollments or orders can be reassigned.',
                ]);
            }

            $lockedCourse->forceFill(['instructor_id' => $target->getKey()])->save();

            AuditEvent::create([
                'actor_id' => $actor->getKey(),
                'subject_type' => Course::class,
                'subject_id' => $lockedCourse->getKey(),
                'action' => 'instructors.course_reassigned',
                'previous_values' => ['instructor_id' => $source->getKey()],
                'new_values' => [
                    'instructor_id' => $target->getKey(),
                    'reason' => trim($reason),
                    'status' => $lockedCourse->status,
                ],
            ]);
        }, 3);
    }
}
