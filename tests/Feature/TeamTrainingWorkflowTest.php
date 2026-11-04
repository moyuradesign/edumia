<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\TeamPackageMember;
use App\Models\TeamPackagePurchase;
use App\Models\TeamTrainingPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeamTrainingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $email, string $role = 'student'): User
    {
        $user = User::query()->create(['name' => ucfirst(explode('@', $email)[0]),
            'email' => $email, 'password' => 'SecurePass#123']);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();
        return $user;
    }

    private function course(User $teacher): Course
    {
        return Course::query()->create(['instructor_id' => $teacher->getKey(),
            'title' => 'Workplace Communication', 'slug' => 'workplace-communication',
            'price' => '0.00', 'status' => 'published', 'visibility' => 'public',
            'published_at' => now()]);
    }

    public function test_free_team_activation_requires_real_acceptance_and_enforces_seat_capacity(): void
    {
        $admin = $this->actor('admin@example.test','admin');
        $teacher = $this->actor('teacher@example.test','instructor');
        $owner = $this->actor('owner@example.test');
        $member = $this->actor('member@example.test');
        $outsider = $this->actor('outsider@example.test');
        $course = $this->course($teacher);

        $this->actingAs($admin)->post(route('admin.team-training.store'), [
            'course_id' => $course->getKey(), 'name' => 'Communication for Teams',
            'seats' => 2, 'description' => 'Shared professional learning',
        ])->assertRedirect();
        $package = TeamTrainingPackage::query()->firstOrFail();
        $this->get(route('admin.team-training.index'))->assertOk()
            ->assertSee('Communication for Teams');
        $this->actingAs($owner)->get(route('teams.index'))
            ->assertOk()->assertSee('Communication for Teams');

        $this->post(route('team.packages.activate',$package), [
            'organization_name' => 'Our Operations Team',
        ])->assertRedirect(route('team.workspaces.index'));
        $team = TeamPackagePurchase::query()->firstOrFail();
        $this->assertSame('active', $team->status);
        $this->assertEquals(0, $team->amount);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $owner->getKey(), 'course_id' => $course->getKey(),
        ]);

        $this->post(route('team.members.invite',$team), [
            'email' => $member->email,
        ])->assertRedirect();
        $invite = TeamPackageMember::query()->where('user_id',$member->getKey())->firstOrFail();
        $this->assertSame('pending', $invite->status);
        $this->assertFalse(Enrollment::query()->where('user_id',$member->getKey())->exists());

        $this->post(route('team.members.invite',$team), [
            'email' => $outsider->email,
        ])->assertSessionHasErrors('email');

        $this->actingAs($outsider)->patch(route('team.invitations.respond',$invite), [
            'decision' => 'accept',
        ])->assertForbidden();
        $this->actingAs($member)->get(route('team.workspaces.index'))
            ->assertOk()->assertSee('Our Operations Team');
        $this->patch(route('team.invitations.respond',$invite), [
            'decision' => 'accept',
        ])->assertRedirect();
        $this->assertSame('active', $invite->fresh()->status);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $member->getKey(), 'course_id' => $course->getKey(),
        ]);
        $this->patch(route('team.invitations.respond',$invite), [
            'decision' => 'accept',
        ])->assertStatus(422);
        $this->assertDatabaseCount('enrollments', 2);
    }

    public function test_other_teams_cannot_invite_and_unverified_paid_packages_cannot_activate(): void
    {
        $admin = $this->actor('admin@example.test','admin');
        $instructor = $this->actor('teacher@example.test','instructor');
        $owner = $this->actor('owner@example.test');
        $other = $this->actor('other@example.test');
        $course = $this->course($instructor);

        $package = TeamTrainingPackage::query()->create([
            'course_id' => $course->id, 'name' => 'Operations Cohort',
            'seats' => 3, 'price' => 0, 'status' => 'active',
        ]);
        $this->actingAs($owner)->post(route('team.packages.activate',$package), [
            'organization_name' => 'Team Blue',
        ])->assertRedirect();
        $team = TeamPackagePurchase::firstOrFail();
        $this->actingAs($other)->post(route('team.members.invite',$team), [
            'email' => $admin->email,
        ])->assertForbidden();

        $package->update(['price' => 55]);
        $this->post(route('team.packages.activate',$package), [
            'organization_name' => 'Unverified Payment Team',
        ])->assertStatus(422);
        $this->assertDatabaseCount('team_package_purchases', 1);
        $this->actingAs($admin)->get(route('admin.team-training.index'))->assertOk();
    }
}
