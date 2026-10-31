<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InstructorApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role, string $email): User
    {
        $user = User::create(['name' => ucfirst($role), 'email' => $email, 'password' => 'Password#12345']);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    private function application(): array
    {
        return [
            'specialization' => 'Leadership and learning',
            'experience' => 'I have facilitated classroom learning for eight years.',
            'motivation' => 'I want to help learners apply practical leadership skills.',
            'portfolio_url' => 'https://example.test/portfolio',
            'role' => 'admin',
            'status' => 'approved',
        ];
    }

    public function test_student_applies_and_administrator_approves_without_privilege_escalation(): void
    {
        $student = $this->member('student', 'learner@edumia.test');
        $admin = $this->member('admin', 'admin@edumia.test');

        $this->actingAs($student)->get('/instructor-application')->assertOk()
            ->assertSee('Become an instructor');
        $this->post('/instructor-application', $this->application())
            ->assertRedirect(route('instructor-application.index'));

        $application = Application::firstOrFail();
        $this->assertSame('pending', $application->status);
        $this->assertSame('student', $student->fresh()->role);
        $this->assertArrayNotHasKey('role', $application->details);

        $this->post('/instructor-application', $this->application())->assertSessionHasErrors('application');
        $this->assertDatabaseCount('applications', 1);

        $this->actingAs($admin)->get('/admin/instructor-applications')
            ->assertOk()->assertSee('Leadership and learning');
        $this->patch('/admin/instructor-applications/'.$application->id, [
            'decision' => 'approved',
            'review_note' => 'Approved for the first course.',
        ])->assertRedirect(route('admin.instructor-applications.index'));

        $this->assertSame('instructor', $student->fresh()->role);
        $this->assertSame('approved', $application->fresh()->status);
        $this->assertDatabaseHas('audit_events', ['action' => 'instructor_application.reviewed']);
        $this->actingAs($student->fresh())->get('/instructor/courses/create')->assertOk();
    }

    public function test_rejection_keeps_student_role_and_allows_fresh_application(): void
    {
        $student = $this->member('student', 'learner@edumia.test');
        $admin = $this->member('admin', 'admin@edumia.test');
        $this->actingAs($student)->post('/instructor-application', $this->application())->assertRedirect();
        $application = Application::firstOrFail();
        $this->actingAs($admin)
            ->patch('/admin/instructor-applications/'.$application->id, ['decision' => 'rejected'])
            ->assertRedirect();
        $this->assertSame('student', $student->fresh()->role);

        $this->actingAs($student)->post('/instructor-application', $this->application())->assertRedirect();
        $this->assertDatabaseCount('applications', 2);

        $this->actingAs($admin)->patch('/admin/instructor-applications/'.$application->id, [
            'decision' => 'approved',
        ])->assertSessionHasErrors('decision');
        $this->assertSame('student', $student->fresh()->role);
    }

    public function test_students_cannot_approve_or_read_others_private_applications(): void
    {
        $student = $this->member('student', 'student@edumia.test');
        $other = $this->member('student', 'other@edumia.test');
        $this->actingAs($other)->post('/instructor-application', $this->application())->assertRedirect();
        $application = Application::firstOrFail();

        $this->actingAs($student)->get('/instructor-application')
            ->assertOk()->assertDontSee('Leadership and learning');
        $this->get('/admin/instructor-applications')->assertForbidden();
        $this->patch('/admin/instructor-applications/'.$application->id, [
            'decision' => 'approved',
        ])->assertForbidden();
        $this->assertSame('pending', $application->fresh()->status);
    }

    public function test_instructor_application_requires_valid_experience_and_https_portfolio(): void
    {
        $student = $this->member('student', 'student@edumia.test');
        $this->actingAs($student)->post('/instructor-application', [
            'specialization' => 'Teaching',
            'experience' => 'short',
            'motivation' => 'short',
            'portfolio_url' => 'http://example.test',
        ])->assertSessionHasErrors(['experience', 'motivation', 'portfolio_url']);
        $this->assertDatabaseCount('applications', 0);
    }
}
