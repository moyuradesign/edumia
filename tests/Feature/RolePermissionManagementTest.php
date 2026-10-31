<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RolePermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        $u = User::create(['name' => $role, 'email' => $email, 'password' => 'Password#12345']);
        $u->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $u;
    }

    public function test_admin_can_delegate_knowledge_management_without_admin_privileges(): void
    {
        $admin = $this->user('admin', 'admin@edumia.test');
        $student = $this->user('student', 'staff@edumia.test');

        $this->actingAs($admin)->get('/admin/roles')->assertOk()->assertSee('Staff roles');
        $this->post('/admin/roles', [
            'name' => 'Help Center Editor',
            'slug' => 'help-editor',
            'description' => 'Manage knowledge base content only.',
            'permissions' => ['knowledge.manage'],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::firstOrFail();
        $this->assertTrue($role->permissions()->where('name', 'knowledge.manage')->exists());
        $this->post('/admin/roles/'.$role->id.'/members', [
            'user_id' => $student->id, 'grant' => 1,
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertSame('student', $student->fresh()->role);
        $this->assertTrue($student->fresh()->hasPermission('knowledge.manage'));

        $this->actingAs($student->fresh())->get('/admin/knowledge-base')->assertOk();
        $this->post('/admin/knowledge-base', [
            'title' => 'Staff Writing Guide', 'slug' => 'staff-writing-guide', 'published' => 1,
        ])->assertRedirect();
        $this->get('/admin/roles')->assertForbidden();
        $this->get('/admin/categories')->assertForbidden();

        $this->actingAs($admin)->post('/admin/roles/'.$role->id.'/members', [
            'user_id' => $student->id, 'grant' => 0,
        ])->assertRedirect();
        $this->actingAs($student->fresh())->get('/admin/knowledge-base')->assertForbidden();
    }

    public function test_role_permissions_are_whitelisted_not_user_defined(): void
    {
        $admin = $this->user('admin', 'admin@edumia.test');
        $student = $this->user('student', 'student@edumia.test');

        $this->actingAs($admin)->post('/admin/roles', [
            'name' => 'Dangerous role',
            'slug' => 'danger',
            'permissions' => ['users.promote_to_admin'],
        ])->assertSessionHasErrors('permissions.0');
        $this->assertDatabaseCount('roles', 0);

        $this->actingAs($student)->post('/admin/roles', [
            'name' => 'Attacker',
            'slug' => 'attacker',
            'permissions' => ['knowledge.manage'],
        ])->assertForbidden();
    }

    public function test_permission_revocation_and_suspension_both_deny_access(): void
    {
        $admin = $this->user('admin', 'admin@edumia.test');
        $student = $this->user('student', 'staff@edumia.test');
        $this->actingAs($admin)->post('/admin/roles', [
            'name' => 'Knowledge', 'slug' => 'knowledge', 'permissions' => ['knowledge.manage'],
        ])->assertRedirect();
        $role = Role::firstOrFail();
        $this->post('/admin/roles/'.$role->id.'/members', [
            'user_id' => $student->id, 'grant' => 1,
        ])->assertRedirect();

        $student->forceFill(['status' => 'suspended'])->save();
        $this->actingAs($student->fresh())->get('/admin/knowledge-base')->assertForbidden();

        $this->actingAs($admin)->put('/admin/roles/'.$role->id, [
            'name' => 'Knowledge', 'slug' => 'knowledge', 'permissions' => [],
        ])->assertRedirect();
        $this->assertFalse($student->fresh()->hasPermission('knowledge.manage'));
    }
}
