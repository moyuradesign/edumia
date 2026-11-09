<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SharedAccountNotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role): User
    {
        $user = User::create([
            'name' => ucfirst($role).' Account',
            'email' => 'center-'.$role.'@edumia.test',
            'password' => 'Password#12345',
        ]);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    public function test_all_workspaces_offer_a_private_notification_bell_and_account_navigation(): void
    {
        $path = ['admin' => '/dashboard', 'instructor' => '/instructor', 'student' => '/account'];

        foreach ($path as $role => $url) {
            $user = $this->member($role);
            app(NotificationService::class)->deliver(
                $user, 'support_reply', 'Private '.$role.' preview', 'Account-specific message',
                '/support/tickets/1'
            );

            $res = $this->actingAs($user)->get($url)->assertOk()
                ->assertSee('data-account-bell', false)
                ->assertSee('1 unread')
                ->assertSee('Private '.$role.' preview')
                ->assertSee('Open notification inbox')
                ->assertSee('href="'.route('account.notifications.index').'"', false);
            foreach (array_keys($path) as $otherRole) {
                if ($role !== $otherRole) {
                    $res->assertDontSee('Private '.$otherRole.' preview');
                }
            }
        }
    }

    public function test_notification_can_be_marked_unread_by_owner_only(): void
    {
        $owner = $this->member('student');
        $other = $this->member('instructor');

        app(NotificationService::class)->deliver($owner, 'account_security',
            'Private owner security alert', 'An account security change occurred.', '/account/password');
        $notice = $owner->unreadNotifications()->firstOrFail();

        $this->actingAs($owner)->patch('/account/notifications/'.$notice->id.'/read')
            ->assertRedirect();
        $this->assertNotNull($notice->fresh()->read_at);

        $this->actingAs($other)->patch('/account/notifications/'.$notice->id.'/unread')
            ->assertNotFound();
        $this->assertNotNull($notice->fresh()->read_at);

        $this->actingAs($owner)->get('/account/notifications?status=read')
            ->assertOk()->assertSee('Mark unread');
        $this->patch('/account/notifications/'.$notice->id.'/unread?status=read&event=account_security')
            ->assertRedirect(route('account.notifications.index', [
                'status' => 'read', 'event' => 'account_security',
            ]));
        $this->assertNull($notice->fresh()->read_at);
        $this->get('/account/notifications?status=unread&event=account_security')
            ->assertOk()->assertSee('Private owner security alert');
    }

    public function test_account_activity_summary_shows_only_safe_own_event_labels(): void
    {
        $owner = $this->member('student');
        $other = $this->member('instructor');

        AuditEvent::create([
            'actor_id' => $owner->id,
            'subject_type' => User::class,
            'subject_id' => $owner->id,
            'action' => 'account.name_updated',
            'previous_values' => ['name' => 'Secret old value'],
            'new_values' => ['name' => 'Secret new value'],
        ]);
        AuditEvent::create([
            'actor_id' => $other->id,
            'subject_type' => User::class,
            'subject_id' => $other->id,
            'action' => 'account.locale_updated',
            'previous_values' => ['locale' => 'sensitive-token-from-other'],
        ]);

        $this->actingAs($owner)->get('/account/preferences')
            ->assertOk()
            ->assertSee('Recent preference changes')
            ->assertSee('Display name changed')
            ->assertDontSee('Language preference changed')
            ->assertDontSee('Secret old value')
            ->assertDontSee('Secret new value')
            ->assertDontSee('sensitive-token-from-other')
            ->assertSee('Unread notifications');
    }

    public function test_account_activity_is_scoped_filterable_and_paged_for_every_role(): void
    {
        $student = $this->member('student');
        $instructor = $this->member('instructor');
        $admin = $this->member('admin');

        for ($i = 1; $i <= 12; $i++) {
            AuditEvent::create([
                'actor_id' => $student->id, 'subject_type' => User::class,
                'subject_id' => $student->id, 'action' => 'account.name_updated',
                'previous_values' => ['name' => 'Secret student '.$i],
            ]);
        }
        AuditEvent::create([
            'actor_id' => $instructor->id, 'subject_type' => User::class,
            'subject_id' => $instructor->id, 'action' => 'account.locale_updated',
            'new_values' => ['locale' => 'Private instructor'],
        ]);
        AuditEvent::create([
            'actor_id' => $admin->id, 'subject_type' => User::class,
            'subject_id' => $admin->id, 'action' => 'notifications.preference_changed',
            'new_values' => ['enabled' => 'Private admin'],
        ]);

        $this->get('/account/activity')->assertRedirect('/login');
        $this->actingAs($student)->get('/account/activity')->assertOk()
            ->assertSee('1–10')
            ->assertSee('of 12 matching changes')
            ->assertSee('Go to page')
            ->assertDontSee('Secret student')
            ->assertDontSee('Private instructor')
            ->assertDontSee('Private admin');

        $this->get('/account/activity?page=2')->assertOk()
            ->assertSee('11–12')->assertSee('of 12 matching changes');
        $this->get('/account/activity?type=account.locale_updated')->assertOk()
            ->assertSee('No matching account activity');
        $this->get('/account/activity?type=invalid')->assertOk()
            ->assertSee('of 12 matching changes');
        $this->actingAs($instructor)->get('/account/activity')->assertOk()
            ->assertSee('of 1 matching changes')
            ->assertDontSee('Private instructor');
        $this->actingAs($admin)->get('/account/activity')->assertOk()
            ->assertSee('of 1 matching changes')
            ->assertDontSee('Private admin');
    }

    public function test_inbox_keyword_search_remains_private_and_preserves_filters(): void
    {
        $student = $this->member('student');
        $other = $this->member('instructor');
        app(NotificationService::class)->deliver($student, 'support_reply',
            'Needle support update', 'The answer is ready.', '/support/tickets/12');
        app(NotificationService::class)->deliver($student, 'account_security',
            'Needle security alert', 'Private security action.', '/account/password');
        app(NotificationService::class)->deliver($other, 'support_reply',
            'Needle foreign alert', 'Another account.', '/support/tickets/99');

        $res = $this->actingAs($student)->get(
            '/account/notifications?status=unread&event=support_reply&q=Needle'
        )->assertOk()->assertSee('Needle support update')
            ->assertSee('of 1 matching updates')
            ->assertDontSee('Needle foreign alert')
            ->assertSee('name="q"', false)
            ->assertSee('value="Needle"', false);
        // Bell is global to this account; only the actual inbox feed is filtered.
        $feed = \Illuminate\Support\Str::between(
            $res->getContent(),
            '<div class="edumia-notification-feed">',
            '<div class="admin-course-pagination">'
        );
        $this->assertStringNotContainsString('Needle security alert', $feed);
        $this->get('/account/notifications?event=support_reply&q=unmatched')
            ->assertOk()->assertSee('of 0 matching updates');
    }
}
