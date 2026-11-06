<?php

declare(strict_types=1);

namespace App\Services\Identity;

use App\Models\AuditEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RoleManagementService
{
    public const DELEGATABLE = [
        'knowledge.manage' => 'Manage Help Center articles',
        'media.manage' => 'Upload, download and manage private Media Library files',
        'support.tickets.manage' => 'Review, reply to and update customer support tickets',
    ];

    public function save(User $actor, array $data, ?Role $role = null): Role
    {
        abort_unless($actor->isAdmin(), 403);
        $permissions = array_values(array_unique($data['permissions'] ?? []));
        sort($permissions, SORT_STRING);

        return DB::transaction(function () use ($actor, $data, $permissions, $role): Role {
            $role = $role
                ? Role::query()->lockForUpdate()->findOrFail($role->getKey())
                : new Role;

            $existing = $role->exists;
            $previous = $existing ? [
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'permissions' => $role->permissions()->pluck('permissions.name')->sort()->values()->all(),
            ] : null;
            $current = [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'permissions' => $permissions,
            ];
            $role->fill([
                'name' => $current['name'],
                'slug' => $current['slug'],
                'description' => $current['description'],
            ])->save();

            $ids = [];
            foreach ($permissions as $permission) {
                abort_unless(array_key_exists($permission, self::DELEGATABLE), 422);
                $ids[] = Permission::query()->firstOrCreate(
                    ['name' => $permission, 'guard_name' => 'web'],
                    ['description' => self::DELEGATABLE[$permission]]
                )->getKey();
            }

            $role->permissions()->sync($ids);
            if (! $existing || $previous !== $current) {
                AuditEvent::create([
                    'actor_id' => $actor->getKey(),
                    'subject_type' => Role::class,
                    'subject_id' => $role->getKey(),
                    'action' => $existing ? 'roles.updated' : 'roles.created',
                    'previous_values' => $previous,
                    'new_values' => $current,
                ]);
            }

            return $role;
        });
    }

    public function assign(User $actor, Role $role, User $member, bool $grant): void
    {
        abort_unless($actor->isAdmin(), 403);

        DB::transaction(function () use ($actor, $role, $member, $grant): void {
            $role = Role::query()->lockForUpdate()->findOrFail($role->getKey());
            $member = User::query()->lockForUpdate()->findOrFail($member->getKey());
            // Revocation is allowed even if a member is suspended; grants
            // require an active non-admin member and never change user.role.
            if ($grant) {
                abort_unless($member->isActive() && ! $member->isAdmin(), 422,
                    'Only active non-administrator members can receive staff roles.');
            }
            $wasAssigned = $role->users()->where('users.id', $member->getKey())->exists();
            if ($wasAssigned === $grant) {
                return;
            }

            if ($grant) {
                $member->customRoles()->syncWithoutDetaching([$role->getKey()]);
            } else {
                $member->customRoles()->detach($role->getKey());
            }

            AuditEvent::create([
                'actor_id' => $actor->getKey(),
                'subject_type' => User::class,
                'subject_id' => $member->getKey(),
                'action' => $grant ? 'roles.assigned' : 'roles.revoked',
                'previous_values' => [
                    'role_id' => $role->getKey(), 'role_slug' => $role->slug,
                    'assigned' => $wasAssigned,
                ],
                'new_values' => [
                    'role_id' => $role->getKey(), 'role_slug' => $role->slug,
                    'assigned' => $grant,
                    'permissions' => $role->permissions()->pluck('permissions.name')->sort()->values()->all(),
                ],
            ]);
        });
    }
}
