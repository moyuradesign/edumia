<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MemberDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $name, string $email, string $role = 'student', string $status = 'active'): User
    {
        $member = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'Strong#Password123',
        ]);
        $member->forceFill(['role' => $role, 'status' => $status])->save();

        return $member;
    }

    public function test_admin_can_browse_a_paginated_member_directory(): void
    {
        $admin = $this->member('Owner', 'owner@example.test', 'admin');
        for ($number = 1; $number <= 24; $number++) {
            $this->member('Member '.$number, 'member'.$number.'@example.test');
        }

        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertOk()
            ->assertSee('Member directory')
            ->assertSee('All members')
            ->assertSee('25 matching accounts')
            ->assertSee('name="role"', false)
            ->assertSee('name="status"', false)
            ->assertSee('name="q"', false)
            ->assertSee('page=2');

        $this->assertSame(20, substr_count((string) $response->getContent(), 'class="admin-member-cell"'));
    }

    public function test_search_and_role_and_status_filters_work_together(): void
    {
        $admin = $this->member('Owner', 'owner@example.test', 'admin');
        $this->member('Findable Teacher', 'teacher@example.test', 'instructor', 'active');
        $this->member('Findable Learner', 'learner@example.test', 'student', 'suspended');
        $this->member('Other Teacher', 'another@example.test', 'instructor');

        $this->actingAs($admin)->get('/admin/users?q=Findable&role=instructor&status=active')
            ->assertOk()
            ->assertSee('Findable Teacher')
            ->assertDontSee('Findable Learner')
            ->assertDontSee('Other Teacher')
            ->assertSee('value="Findable"', false);
    }

    public function test_malformed_filter_arrays_are_ignored_safely(): void
    {
        $admin = $this->member('Owner', 'owner@example.test', 'admin');

        $this->actingAs($admin)->get('/admin/users?q[]=bad&role[]=bad&status[]=bad')
            ->assertOk()
            ->assertSee('Member directory')
            ->assertSee('Owner');
    }

    public function test_member_changes_reuse_the_existing_audited_workflow(): void
    {
        $admin = $this->member('Owner', 'owner@example.test', 'admin');
        $student = $this->member('Learner', 'learner@example.test');

        $this->actingAs($admin)
            ->from('/admin/users?role=student')
            ->put(route('admin.users.update', $student), [
                'role' => 'instructor',
                'status' => 'active',
            ])
            ->assertRedirect('/admin/users?role=student');

        $this->assertDatabaseHas('users', ['id' => $student->id, 'role' => 'instructor']);
        $this->assertDatabaseHas('audit_events', [
            'actor_id' => $admin->id,
            'subject_id' => $student->id,
            'action' => 'users.role_status_updated',
        ]);
    }

    public function test_non_admins_cannot_open_directory_and_admin_cannot_edit_self(): void
    {
        $admin = $this->member('Owner', 'owner@example.test', 'admin');
        $student = $this->member('Learner', 'learner@example.test');

        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->actingAs($student)->get(route('admin.users.index'))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()
            ->assertSee('Your administrator account')
            ->assertDontSee('action="'.route('admin.users.update', $admin).'"', false);
    }
}
