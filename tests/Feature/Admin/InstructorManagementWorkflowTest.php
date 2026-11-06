<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Application;
use App\Models\AuditEvent;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class InstructorManagementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role, string $email, string $status = 'active'): User
    {
        $user = User::query()->create([
            'name' => ucfirst($role).' '.explode('@', $email)[0],
            'email' => $email,
            'password' => 'Valid#Password123',
        ]);
        $user->forceFill(['role' => $role, 'status' => $status])->save();

        return $user;
    }

    private function course(User $owner, string $title, string $status = 'draft'): Course
    {
        return Course::query()->create([
            'instructor_id' => $owner->id,
            'title' => $title,
            'slug' => Str::slug($title),
            'status' => $status,
            'visibility' => $status === 'published' ? 'public' : 'private',
            'published_at' => $status === 'published' ? now() : null,
        ]);
    }

    public function test_reviewers_are_filterable_and_audit_records_preserve_reviewer_and_role(): void
    {
        $adminOne = $this->member('admin', 'review-one@edumia.test');
        $adminTwo = $this->member('admin', 'review-two@edumia.test');
        $candidate = $this->member('student', 'candidate@edumia.test');
        $another = $this->member('student', 'another@edumia.test');

        $first = Application::query()->create([
            'user_id' => $candidate->id, 'type' => 'instructor', 'status' => 'pending',
            'details' => ['specialization' => 'UX Teaching'],
        ]);
        $second = Application::query()->create([
            'user_id' => $another->id, 'type' => 'instructor', 'status' => 'pending',
            'details' => ['specialization' => 'Mathematics'],
        ]);

        $this->actingAs($adminOne)->patch('/admin/instructor-applications/'.$first->id, [
            'decision' => 'approved', 'review_note' => 'Verified the teaching experience.',
        ])->assertRedirect();
        $this->actingAs($adminTwo)->patch('/admin/instructor-applications/'.$second->id, [
            'decision' => 'rejected', 'review_note' => 'Portfolio not sufficient.',
        ])->assertRedirect();

        $this->get('/admin/instructor-applications?reviewer='.$adminOne->id.'&sort=reviewed')
            ->assertOk()->assertSee('candidate@edumia.test')->assertDontSee('another@edumia.test')
            ->assertSee('Verified the teaching experience.')
            ->assertSee('instructor-reviewer')->assertSee('instructor-sort');
        $this->get('/admin/instructor-applications?reviewer='.$adminTwo->id.'&status=rejected')
            ->assertOk()->assertSee('another@edumia.test')->assertDontSee('candidate@edumia.test');
        $this->get('/admin/instructor-applications?reviewer[]=invalid&sort[]=reviewed')
            ->assertOk();

        $reviewAudit = AuditEvent::query()
            ->where('subject_type', Application::class)
            ->where('subject_id', $first->id)
            ->where('action', 'instructor_application.reviewed')->firstOrFail();
        $this->assertSame('student', $reviewAudit->previous_values['applicant_role']);
        $this->assertSame('instructor', $reviewAudit->new_values['applicant_role']);
        $this->assertSame($adminOne->id, $reviewAudit->new_values['reviewed_by']);
        $this->assertTrue($reviewAudit->new_values['note_recorded']);
        $this->assertSame('instructor', $candidate->fresh()->role);
        $this->assertSame('student', $another->fresh()->role);
    }

    public function test_admin_can_suspend_and_restore_instructor_with_reason_and_audit(): void
    {
        $admin = $this->member('admin', 'status-admin@edumia.test');
        $teacher = $this->member('instructor', 'status-teacher@edumia.test');
        $student = $this->member('student', 'status-student@edumia.test');
        $this->course($teacher, 'Instructor Audit Course');

        $this->actingAs($admin)->get('/admin/instructors')->assertOk()
            ->assertSee('Manage assignments')
            ->assertSee('faculty-activity')
            ->assertSee('faculty-sort');
        $this->patch('/admin/instructors/'.$teacher->id.'/status', [
            'status' => 'suspended', 'reason' => 'Account requires identity review.',
        ])->assertRedirect();
        $this->assertSame('suspended', $teacher->fresh()->status);
        $this->assertFalse($teacher->fresh()->canTeach());
        $this->assertDatabaseHas('audit_events', [
            'actor_id' => $admin->id, 'subject_type' => User::class,
            'subject_id' => $teacher->id, 'action' => 'instructors.status_changed',
        ]);
        $this->get('/admin/instructors?status=suspended&activity=with_courses')
            ->assertOk()->assertSee('status-teacher@edumia.test')
            ->assertSee('Account requires identity review.');

        $this->patch('/admin/instructors/'.$teacher->id.'/status', [
            'status' => 'suspended', 'reason' => 'A duplicate status decision.',
        ])->assertSessionHasErrors('status');
        $this->assertSame(1, AuditEvent::where('action', 'instructors.status_changed')->count());

        $this->patch('/admin/instructors/'.$teacher->id.'/status', [
            'status' => 'active', 'reason' => 'Identity review is now complete.',
        ])->assertRedirect();
        $this->assertTrue($teacher->fresh()->canTeach());
        $this->assertSame(2, AuditEvent::where('action', 'instructors.status_changed')->count());

        $this->patch('/admin/instructors/'.$student->id.'/status', [
            'status' => 'suspended', 'reason' => 'Should never update students here.',
        ])->assertNotFound();
        $this->actingAs($student)->patch('/admin/instructors/'.$teacher->id.'/status', [
            'status' => 'suspended', 'reason' => 'Unauthorized status update.',
        ])->assertForbidden();
    }

    public function test_transfer_of_unused_draft_course_is_audited_and_visible_to_new_instructor(): void
    {
        $admin = $this->member('admin', 'assignment-admin@edumia.test');
        $source = $this->member('instructor', 'assignment-from@edumia.test');
        $target = $this->member('instructor', 'assignment-to@edumia.test');
        $course = $this->course($source, 'Unassigned Draft Curriculum');

        $this->actingAs($admin)->get('/admin/instructors/'.$source->id.'/assignments')
            ->assertOk()->assertSee('Unassigned Draft Curriculum')
            ->assertSee('Transfer assignment')->assertSee('assignment-to@edumia.test');

        $this->post('/admin/instructors/'.$source->id.'/assignments/'.$course->id, [
            'new_instructor_id' => $target->id,
            'reason' => 'Reallocated to the primary subject teacher.',
        ])->assertRedirect(route('admin.instructors.assignments', $source));

        $this->assertSame($target->id, $course->fresh()->instructor_id);
        $audit = AuditEvent::query()->where('action', 'instructors.course_reassigned')->firstOrFail();
        $this->assertSame($admin->id, $audit->actor_id);
        $this->assertSame($source->id, $audit->previous_values['instructor_id']);
        $this->assertSame($target->id, $audit->new_values['instructor_id']);

        $this->get('/admin/instructors/'.$target->id.'/assignments')
            ->assertOk()->assertSee('Unassigned Draft Curriculum');
        $this->get('/admin/instructors/'.$source->id.'/assignments')
            ->assertOk()->assertDontSee('Unassigned Draft Curriculum');
        $this->actingAs($source)->get('/admin/instructors/'.$target->id.'/assignments')->assertForbidden();
    }

    public function test_transfer_refuses_published_enrolled_ordered_and_suspended_destinations(): void
    {
        $admin = $this->member('admin', 'guard-admin@edumia.test');
        $source = $this->member('instructor', 'guard-from@edumia.test');
        $target = $this->member('instructor', 'guard-to@edumia.test');
        $suspended = $this->member('instructor', 'guard-suspended@edumia.test', 'suspended');
        $student = $this->member('student', 'guard-student@edumia.test');
        $published = $this->course($source, 'Guard Published', 'published');
        $enrolled = $this->course($source, 'Guard Enrolled');
        $ordered = $this->course($source, 'Guard Ordered');
        $regular = $this->course($source, 'Guard Regular');

        Enrollment::query()->create([
            'course_id' => $enrolled->id, 'user_id' => $student->id,
            'status' => 'active', 'progress_percent' => 0,
        ]);
        $order = Order::query()->create([
            'user_id' => $student->id, 'reference' => (string) Str::uuid(),
            'status' => 'pending', 'currency_code' => 'USD',
            'subtotal' => '0.00', 'total' => '0.00',
        ]);
        $order->items()->create([
            'course_id' => $ordered->id, 'title' => 'Guard Ordered',
            'quantity' => 1, 'unit_price' => '0.00', 'line_total' => '0.00',
        ]);

        $this->actingAs($admin);
        foreach ([$published, $enrolled, $ordered] as $course) {
            $this->post('/admin/instructors/'.$source->id.'/assignments/'.$course->id, [
                'new_instructor_id' => $target->id,
                'reason' => 'Should not transfer an active commitment.',
            ])->assertSessionHasErrors('new_instructor_id');
        }
        $this->post('/admin/instructors/'.$source->id.'/assignments/'.$regular->id, [
            'new_instructor_id' => $suspended->id,
            'reason' => 'Invalid destination must be rejected.',
        ])->assertSessionHasErrors('new_instructor_id');
        $this->post('/admin/instructors/'.$source->id.'/assignments/'.$regular->id, [
            'new_instructor_id' => $source->id,
            'reason' => 'Cannot assign a teacher to themselves.',
        ])->assertSessionHasErrors('new_instructor_id');
        $this->post('/admin/instructors/'.$target->id.'/assignments/'.$regular->id, [
            'new_instructor_id' => $target->id,
            'reason' => 'Must not change courses from other instructors.',
        ])->assertNotFound();
        $this->assertSame(0, AuditEvent::query()->where('action', 'instructors.course_reassigned')->count());
        $this->assertSame($source->id, $regular->fresh()->instructor_id);

        $this->actingAs($student)->post('/admin/instructors/'.$source->id.'/assignments/'.$regular->id, [
            'new_instructor_id' => $target->id,
            'reason' => 'Unauthorized transfer must be rejected.',
        ])->assertForbidden();
    }
}
