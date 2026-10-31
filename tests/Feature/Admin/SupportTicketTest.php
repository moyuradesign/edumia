<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SupportTicketTest extends TestCase
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

    private function createTicket(User $owner): Ticket
    {
        $this->actingAs($owner)->post('/support/tickets', [
            'subject' => 'Course progress not updating',
            'description' => 'The progress indicator is not moving after I finish a lesson.',
        ])->assertRedirect();

        return Ticket::firstOrFail();
    }

    public function test_learner_can_open_ticket_and_administrator_can_reply_and_close(): void
    {
        $student = $this->account('student', 'student@edumia.test');
        $admin = $this->account('admin', 'admin@edumia.test');
        $ticket = $this->createTicket($student);

        $this->get('/support/tickets')->assertOk()->assertSee('Course progress not updating');
        $this->get('/support/tickets/'.$ticket->id)->assertOk()->assertSee('The progress indicator');
        $this->post('/support/tickets/'.$ticket->id.'/reply', [
            'body' => 'I tried several more lessons.',
        ])->assertRedirect(route('support.tickets.show', $ticket));

        $this->actingAs($admin)->get('/admin/tickets')->assertOk()->assertSee('Course progress not updating');
        $this->get('/admin/tickets/'.$ticket->id)->assertOk()->assertSee('I tried several more lessons.');
        $this->post('/support/tickets/'.$ticket->id.'/reply', ['body' => 'We are investigating.'])
            ->assertRedirect();
        $this->patch('/admin/tickets/'.$ticket->id, ['status' => 'closed'])
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $this->assertNotNull($ticket->fresh()->closed_at);
        $this->assertDatabaseHas('audit_events', [
            'subject_id' => $ticket->id,
            'action' => 'tickets.status_updated',
        ]);
        $this->post('/support/tickets/'.$ticket->id.'/reply', ['body' => 'Another reply.'])
            ->assertStatus(422);
        $this->patch('/admin/tickets/'.$ticket->id, ['status' => 'open'])
            ->assertRedirect();
        $this->assertNull($ticket->fresh()->closed_at);
    }

    public function test_another_student_cannot_view_or_reply_to_private_ticket(): void
    {
        $owner = $this->account('student', 'owner@edumia.test');
        $other = $this->account('student', 'other@edumia.test');
        $ticket = $this->createTicket($owner);

        $this->actingAs($other)->get('/support/tickets/'.$ticket->id)->assertForbidden();
        $this->post('/support/tickets/'.$ticket->id.'/reply', [
            'body' => 'Trying to read another ticket.',
        ])->assertForbidden();
        $this->get('/admin/tickets')->assertForbidden();

        $this->assertDatabaseCount('ticket_messages', 0);
    }

    public function test_internal_notes_are_not_shown_to_learner(): void
    {
        $student = $this->account('student', 'student@edumia.test');
        $admin = $this->account('admin', 'admin@edumia.test');
        $ticket = $this->createTicket($student);
        $ticket->messages()->create([
            'sender_id' => $admin->id,
            'body' => 'Private internal administrative notes',
            'is_internal' => true,
        ]);

        $this->get('/support/tickets/'.$ticket->id)
            ->assertOk()
            ->assertDontSee('Private internal administrative notes');

        $this->actingAs($admin)->get('/admin/tickets/'.$ticket->id)
            ->assertOk()
            ->assertSee('Private internal administrative notes');
    }
}
