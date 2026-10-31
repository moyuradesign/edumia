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
    ];

    public function save(User $actor, array $data, ?Role $role = null): Role
    {
        abort_unless($actor->isAdmin(), 403);
        $permissions = array_values(array_unique($data['permissions'] ?? []));

        return DB::transaction(function () use ($actor, $data, $permissions, $role): Role {
            $role = $role
                ? Role::query()->lockForUpdate()->findOrFail($role->getKey())
                : new Role;

            $existing = $role->exists;
            $role->fill([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
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
            AuditEvent::create([
                'actor_id' => $actor->getKey(),
                'subject_type' => Role::class,
                'subject_id' => $role->getKey(),
                'action' => $existing ? 'roles.updated' : 'roles.created',
                'new_values' => ['slug' => $role->slug, 'permissions' => $permissions],
            ]);

            return $role;
        });
    }

    public function assign(User $actor, Role $role, User $member, bool $grant): void
    {
        abort_unless($actor->isAdmin(), 403);

        DB::transaction(function () use ($actor, $role, $member, $grant): void {
            $role = Role::query()->lockForUpdate()->findOrFail($role->getKey());
            $member = User::query()->lockForUpdate()->findOrFail($member->getKey());
            abort_unless($member->isActive(), 422, 'Only active users can receive a staff role.');
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
                'new_values' => ['role_slug' => $role->slug],
            ]);
        });
    }
}
