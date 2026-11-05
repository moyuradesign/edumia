<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MediaFile;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_staff_role_workspace_renders_real_grants_and_existing_assignment_actions(): void
    {
        $admin = $this->user('admin', 'owner@edumia.test');
        $this->actingAs($admin)->post('/admin/roles', [
            'name' => 'Help Desk Editor', 'slug' => 'help-desk-editor',
            'permissions' => ['knowledge.manage'],
        ])->assertRedirect(route('admin.roles.index'));
        $role = Role::query()->firstOrFail();

        $this->get('/admin/roles')->assertOk()
            ->assertSee('data-admin-shell', false)
            ->assertSee('Roles & permissions', false)
            ->assertSee('Delegatable permissions')
            ->assertSee('Help Desk Editor')
            ->assertSee('Manage Help Center articles')
            ->assertSee('action="'.route('admin.roles.update', $role).'"', false)
            ->assertSee('action="'.route('admin.roles.members', $role).'"', false)
            ->assertSee('name="user_id"', false)
            ->assertSee('This form cannot create new system permissions');
    }

    public function test_media_management_role_grants_private_file_workflow_without_admin_access(): void
    {
        Storage::fake('local');
        $admin = $this->user('admin', 'media-admin@edumia.test');
        $staff = $this->user('student', 'media-staff@edumia.test');
        $this->actingAs($admin)->post('/admin/roles', [
            'name' => 'Media manager', 'slug' => 'media-manager', 'permissions' => ['media.manage'],
        ])->assertRedirect(route('admin.roles.index'));
        $role = Role::query()->where('slug', 'media-manager')->firstOrFail();
        $this->post('/admin/roles/'.$role->id.'/members', [
            'user_id' => $staff->id, 'grant' => 1,
        ])->assertRedirect(route('admin.roles.index'));

        $this->actingAs($staff->fresh())->get('/admin/media')->assertOk()
            ->assertSee('Staff tools')
            ->assertSee('Media library')
            ->assertDontSee('Staff roles');
        $this->get('/admin/tickets')->assertForbidden();
        $this->get('/admin/roles')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/blogs')->assertForbidden();

        $this->post('/admin/media', [
            'file' => UploadedFile::fake()->createWithContent(
                'staff-guide.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF"
            ),
            'alt_text' => 'Staff guide',
        ])->assertRedirect(route('admin.media.index'));

        $media = MediaFile::query()->firstOrFail();
        $this->assertSame($staff->id, $media->user_id);
        Storage::disk('local')->assertExists($media->path);
        $this->get('/admin/media/'.$media->id.'/download')->assertOk();
        $this->delete('/admin/media/'.$media->id)->assertRedirect(route('admin.media.index'));
        Storage::disk('local')->assertMissing($media->path);
        $this->assertDatabaseHas('audit_events', [
            'actor_id' => $staff->id, 'action' => 'media.uploaded',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'actor_id' => $staff->id, 'action' => 'media.deleted',
        ]);

        $this->actingAs($admin)->post('/admin/roles/'.$role->id.'/members', [
            'user_id' => $staff->id, 'grant' => 0,
        ])->assertRedirect();
        $this->actingAs($staff->fresh())->get('/admin/media')->assertForbidden();
        $this->post('/admin/media', [])->assertForbidden();
    }

    public function test_support_staff_can_process_tickets_but_not_manage_admin_or_media(): void
    {
        $owner = $this->user('student', 'ticket-owner@edumia.test');
        $this->actingAs($owner)->post('/support/tickets', [
            'subject' => 'Course completion not reflected',
            'description' => 'My lesson completion status is not updating.',
        ])->assertRedirect();
        $ticket = Ticket::query()->firstOrFail();

        $admin = $this->user('admin', 'support-admin@edumia.test');
        $staff = $this->user('student', 'support-staff@edumia.test');
        $this->actingAs($admin)->post('/admin/roles', [
            'name' => 'Support agent', 'slug' => 'support-agent',
            'permissions' => ['support.tickets.manage'],
        ])->assertRedirect(route('admin.roles.index'));
        $role = Role::query()->where('slug', 'support-agent')->firstOrFail();
        $this->post('/admin/roles/'.$role->id.'/members', [
            'user_id' => $staff->id, 'grant' => 1,
        ])->assertRedirect();

        $this->actingAs($staff->fresh())->get('/admin/tickets')->assertOk()
            ->assertSee('Support inbox')
            ->assertSee('Course completion not reflected');
        $this->get('/admin/tickets/'.$ticket->id)->assertOk()
            ->assertSee('My lesson completion status');
        $this->get('/support/tickets/'.$ticket->id)->assertOk();
        $this->post('/support/tickets/'.$ticket->id.'/reply', [
            'body' => 'We are reviewing your completion record.',
        ])->assertRedirect(route('support.tickets.show', $ticket));
        $this->patch('/admin/tickets/'.$ticket->id, [
            'status' => 'closed',
        ])->assertRedirect(route('admin.tickets.show', $ticket));
        $this->assertNotNull($ticket->fresh()->closed_at);
        $this->assertDatabaseHas('audit_events', [
            'actor_id' => $staff->id, 'action' => 'tickets.status_updated',
        ]);

        $this->get('/admin/media')->assertForbidden();
        $this->get('/admin/roles')->assertForbidden();
        $this->get('/admin/settings/general')->assertForbidden();

        $this->actingAs($admin)->put('/admin/roles/'.$role->id, [
            'name' => 'Support agent', 'slug' => 'support-agent', 'permissions' => [],
        ])->assertRedirect(route('admin.roles.index'));
        $this->actingAs($staff->fresh())->get('/admin/tickets')->assertForbidden();
        $this->get('/support/tickets/'.$ticket->id)->assertForbidden();
    }

}
