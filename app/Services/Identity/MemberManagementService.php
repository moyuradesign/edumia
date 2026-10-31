<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class MemberManagementService
{
    public function update(User $actor, User $target, array $attributes): void
    {
        abort_unless($actor->isAdmin(), 403);
        abort_if($actor->is($target), 422, 'You cannot change your own administrator role.');

        DB::transaction(function () use ($actor, $target, $attributes): void {
            // Lock active administrators in a consistent order so concurrent
            // role changes cannot remove the last active administrator.
            $activeAdministrators = User::query()
                ->where('role', 'admin')
                ->where('status', 'active')
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id');

            $member = User::query()->lockForUpdate()->findOrFail($target->getKey());
            $before = $member->only(['role', 'status']);

            if ($member->isAdmin()
                && ($attributes['role'] !== 'admin' || $attributes['status'] !== 'active')) {
                abort_if(
                    $activeAdministrators->count() < 2,
                    422,
                    'At least one active administrator must remain.'
                );
            }

            $member->forceFill([
                'role' => $attributes['role'],
                'status' => $attributes['status'],
            ])->save();

            if ($before !== $member->only(['role', 'status'])) {
                AuditEvent::create([
                    'actor_id' => $actor->getKey(),
                    'subject_type' => User::class,
                    'subject_id' => $member->getKey(),
                    'action' => 'users.role_status_updated',
                    'previous_values' => $before,
                    'new_values' => $member->only(['role', 'status']),
                ]);
            }
        }, attempts: 3);
    }
}
