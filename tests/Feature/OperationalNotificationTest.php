<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendOperationalMail;
use App\Mail\OperationalMail;
use App\Models\Application;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class OperationalNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role, string $email): User
    {
        $user = User::create([
            'name' => ucfirst($role), 'email' => $email,
            'password' => 'Password#12345',
        ]);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    public function test_notification_inbox_is_private_and_read_actions_are_account_scoped(): void
    {
        $owner = $this->member('student', 'inbox-owner@edumia.test');
        $intruder = $this->member('student', 'other-reader@edumia.test');

        app(NotificationService::class)->deliver($owner, 'support_reply',
            'Private support update', 'Your ticket was answered.', '/support/tickets/12');
        $notice = $owner->notifications()->firstOrFail();
        $this->actingAs($intruder)->get('/account/notifications')
            ->assertOk()->assertDontSee('Private support update');
        $this->patch('/account/notifications/'.$notice->id.'/read')->assertNotFound();
        $this->assertNull($notice->fresh()->read_at);

        $this->actingAs($owner)->get('/account/notifications')
            ->assertOk()->assertSee('Private support update')
            ->assertSee('Delivery preferences');
        $this->patch('/account/notifications/'.$notice->id.'/read')->assertRedirect();
        $this->assertNotNull($notice->fresh()->read_at);
        $this->get('/account/notifications')->assertSee('Read');

        $this->actingAs($intruder)->get('/admin/notifications/delivery')->assertForbidden();
        $this->post('/admin/notifications/delivery/test')->assertForbidden();
    }

    public function test_preference_controls_block_invalid_events_and_preserve_security_alerts(): void
    {
        $owner = $this->member('student', 'preference-owner@edumia.test');
        $this->actingAs($owner)->get('/account/notifications/preferences')->assertOk()
            ->assertSee('Notification preferences')
            ->assertSee('Outbound operational email is disabled')
            ->assertSee('account_security');

        $payload = [
            'preferences' => [
                'instructor_decision' => ['database' => '0', 'mail' => '0'],
                'support_reply' => ['database' => '0', 'mail' => '1'],
                'support_status' => ['database' => '1', 'mail' => '0'],
                'account_security' => ['database' => '0', 'mail' => '0'],
            ],
        ];
        $this->put('/account/notifications/preferences', $payload)
            ->assertRedirect(route('account.notifications.preferences.edit'));
        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $owner->id,
            'event_key' => 'support_reply', 'channel' => 'database', 'enabled' => 0,
        ]);
        $this->assertTrue(app(NotificationService::class)
            ->enabled($owner, 'account_security', 'database'));
        app(NotificationService::class)->deliver($owner, 'support_reply',
            'Muted in-app notice', 'You have a reply.', '/support/tickets/2');
        $this->assertDatabaseCount('notifications', 0);

        $count = \App\Models\AuditEvent::query()
            ->where('action', 'notifications.preference_changed')->count();
        $this->put('/account/notifications/preferences', $payload)->assertRedirect();
        $this->assertSame($count, \App\Models\AuditEvent::query()
            ->where('action', 'notifications.preference_changed')->count());

        $this->put('/account/notifications/preferences', [
            'preferences' => ['admin_access' => ['database' => '1']],
        ])->assertSessionHasErrors('preferences');
        $this->put('/account/notifications/preferences', [
            'preferences' => ['support_reply' => ['database' => '1', 'webhook' => '1']],
        ])->assertSessionHasErrors('preferences.support_reply');
    }

    public function test_instructor_decision_support_messages_and_password_change_generate_safe_alerts(): void
    {
        $admin = $this->member('admin', 'notice-admin@edumia.test');
        $learner = $this->member('student', 'notice-learner@edumia.test');

        $application = Application::query()->create([
            'user_id' => $learner->id, 'type' => 'instructor',
            'details' => ['private_note' => 'Do not expose applicant information'],
            'status' => 'pending',
        ]);
        $this->actingAs($admin)->patch('/admin/instructor-applications/'.$application->id, [
            'decision' => 'approved', 'review_note' => 'Private reviewer note is confidential',
        ])->assertRedirect();
        $this->assertSame('instructor', $learner->fresh()->role);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $learner->id, 'notifiable_type' => User::class,
        ]);
        $this->actingAs($learner->fresh())->get('/account/notifications')->assertOk()
            ->assertSee('Instructor application approved')
            ->assertDontSee('Private reviewer note is confidential')
            ->assertDontSee('Do not expose applicant information');

        $this->post('/support/tickets', [
            'subject' => 'My certificate', 'description' => 'Confidential course performance',
        ])->assertRedirect();
        $ticket = Ticket::query()->firstOrFail();
        $this->actingAs($admin)->post('/support/tickets/'.$ticket->id.'/reply', [
            'body' => 'Confidential customer-facing reply content',
        ])->assertRedirect();
        $this->patch('/admin/tickets/'.$ticket->id, ['status' => 'closed'])
            ->assertRedirect();

        $this->actingAs($learner->fresh())->get('/account/notifications')
            ->assertOk()->assertSee('New support reply')
            ->assertSee('Support ticket closed')
            ->assertDontSee('Confidential customer-facing reply content')
            ->assertDontSee('Confidential course performance');

        $this->put('/account/password', [
            'current_password' => 'Password#12345',
            'password' => 'NewPassword#67891',
            'password_confirmation' => 'NewPassword#67891',
        ])->assertRedirect();
        $this->get('/account/notifications')->assertOk()
            ->assertSee('Account password changed');
    }

    public function test_outbound_email_is_disabled_without_a_verified_provider_and_worker(): void
    {
        Queue::fake();
        $owner = $this->member('student', 'disabled-mail@edumia.test');
        app(NotificationService::class)->deliver($owner, 'support_status',
            'Support update', 'Your ticket changed status.', '/support/tickets/1');

        Queue::assertNothingPushed();
        $this->assertSame('log', config('mail.default'));
        $this->assertFalse(app(NotificationService::class)->mailReady());
        $this->assertDatabaseCount('failed_jobs', 0);
    }

    public function test_admin_delivery_dashboard_reports_read_only_queue_health(): void
    {
        $admin = $this->member('admin', 'queue-health-admin@edumia.test');
        $this->actingAs($admin)->get('/admin/notifications/delivery')
            ->assertOk()
            ->assertSee('Pending notification jobs')
            ->assertSee('Failed notification jobs')
            ->assertSee('Not independently verified');
        $this->assertSame('sync', config('queue.default'));
        $this->assertFalse((bool) config('notifications.queue_worker_verified', false));
    }

    public function test_smtp_probe_requires_operator_opt_in_and_avoids_credentials_in_audit(): void
    {
        Mail::fake();
        $admin = $this->member('admin', 'mail-operator@edumia.test');
        $this->actingAs($admin)->post('/admin/notifications/delivery/test')->assertForbidden();

        config()->set('mail.default', 'smtp');
        config()->set('notifications.smtp_test_enabled', true);
        $this->get('/admin/notifications/delivery')->assertOk()
            ->assertSee('Send SMTP test to my account');
        $this->post('/admin/notifications/delivery/test')
            ->assertRedirect(route('admin.notifications.delivery'));

        Mail::assertSent(OperationalMail::class);
        $this->assertDatabaseHas('audit_events', [
            'actor_id' => $admin->id,
            'action' => 'notifications.smtp_test_accepted',
        ]);
        $this->assertFalse(app(NotificationService::class)->mailReady());
    }

    public function test_verified_mail_job_uses_id_scoped_delivery_and_redacted_audit(): void
    {
        Mail::fake();
        $owner = $this->member('student', 'mail-recipient@edumia.test');
        config()->set('mail.default', 'smtp');
        config()->set('queue.default', 'database');
        config()->set('notifications.provider_verified', true);
        config()->set('notifications.transactional_mail_enabled', true);

        $notice = new \App\Notifications\OperationalNotice(
            'support_reply', 'Your ticket has a reply', 'Sign in to read it.',
            '/support/tickets/8'
        );
        $owner->notify($notice);
        $id = $owner->notifications()->firstOrFail()->id;

        $job = new SendOperationalMail($owner->id, $id);
        $job->handle(app(NotificationService::class));
        Mail::assertSent(OperationalMail::class, 1);
        $job->handle(app(NotificationService::class));
        Mail::assertSent(OperationalMail::class, 1);
        $this->assertDatabaseHas('audit_events', [
            'subject_id' => $owner->id, 'action' => 'notifications.mail_accepted',
        ]);
    }
    public function test_failed_mail_audit_omits_provider_exception_details(): void
    {
        $owner = $this->member('student', 'secure-failure@edumia.test');
        $job = new SendOperationalMail($owner->id, '5eef164c-c91d-43ac-8e95-101ef2d0b872');
        $job->failed(new \RuntimeException('SMTP password=super-private-secret'));
        $event = \App\Models\AuditEvent::query()
            ->where('action', 'notifications.mail_failed')->firstOrFail();
        $this->assertSame('RuntimeException', $event->new_values['error_category']);
        $this->assertStringNotContainsString('super-private-secret',
            json_encode($event->toArray(), JSON_THROW_ON_ERROR));
    }

    public function test_mail_only_preference_queues_without_exposing_an_in_app_message(): void
    {
        Queue::fake();
        Mail::fake();
        $owner = $this->member('student', 'mail-only@edumia.test');
        config()->set('mail.default', 'smtp');
        config()->set('queue.default', 'database');
        config()->set('notifications.provider_verified', true);
        config()->set('notifications.transactional_mail_enabled', true);
        $this->actingAs($owner)->put('/account/notifications/preferences', [
            'preferences' => [
                'support_reply' => ['database' => '0', 'mail' => '1'],
                'support_status' => ['database' => '1', 'mail' => '0'],
                'instructor_decision' => ['database' => '1', 'mail' => '0'],
                'account_security' => ['database' => '1', 'mail' => '0'],
            ],
        ])->assertRedirect();

        app(NotificationService::class)->deliver($owner, 'support_reply',
            'Email-only ticket reply', 'Sign in to view the update.', '/support/tickets/4');

        $this->assertDatabaseCount('notifications', 0);
        Queue::assertPushed(SendOperationalMail::class, function (SendOperationalMail $job) use ($owner) {
            $this->assertSame($owner->id, $job->userId);
            $this->assertSame('support_reply', $job->snapshot['event']);
            $job->handle(app(NotificationService::class));

            return true;
        });
        Mail::assertSent(OperationalMail::class, 1);
    }

}
