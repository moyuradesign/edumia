<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role, string $email): User
    {
        $user = User::create([
            'name' => ucfirst($role),
            'email' => $email,
            'password' => 'Password#12345',
        ]);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    public function test_administrator_can_assign_an_instructor_role_with_an_audit_record(): void
    {
        $admin = $this->account('admin', 'admin@example.test');
        $student = $this->account('student', 'student@example.test');

        $this->actingAs($admin)
            ->put('/admin/users/'.$student->id, ['role' => 'instructor', 'status' => 'active'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $student->id, 'role' => 'instructor']);
        $this->assertDatabaseHas('audit_events', [
            'actor_id' => $admin->id,
            'subject_id' => $student->id,
            'action' => 'users.role_status_updated',
        ]);
    }

    public function test_students_cannot_update_roles(): void
    {
        $student = $this->account('student', 'student@example.test');

        $this->actingAs($student)
            ->put('/admin/users/'.$student->id, ['role' => 'admin', 'status' => 'active'])
            ->assertForbidden();

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_administrator_cannot_change_their_own_role_or_status(): void
    {
        $admin = $this->account('admin', 'admin@example.test');

        $this->actingAs($admin)
            ->put('/admin/users/'.$admin->id, ['role' => 'student', 'status' => 'suspended'])
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin', 'status' => 'active']);
    }

    public function test_no_duplicate_audit_event_for_unchanged_values(): void
    {
        $admin = $this->account('admin', 'admin@example.test');
        $student = $this->account('student', 'student@example.test');

        $this->actingAs($admin)
            ->put('/admin/users/'.$student->id, ['role' => 'student', 'status' => 'active'])
            ->assertRedirect();

        $this->assertDatabaseCount('audit_events', 0);
    }
}
