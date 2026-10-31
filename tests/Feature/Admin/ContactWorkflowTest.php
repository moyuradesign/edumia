<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ContactWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        $user = User::create([
            'name' => ucfirst($role),
            'email' => $role.'@edumia.test',
            'password' => 'Password#12345',
        ]);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    public function test_guest_can_submit_an_inquiry_for_admin_review(): void
    {
        $this->get('/contact')->assertOk()->assertSee('Send message');
        $this->post('/contact', [
            'name' => 'A Learner',
            'email' => 'learner@example.test',
            'subject' => 'Course availability',
            'message' => 'Can you tell me when the next course starts?',
            'status' => 'responded',
        ])->assertRedirect(route('contact.create'));

        $contact = Contact::firstOrFail();
        $this->assertSame('new', $contact->status);

        $admin = $this->account('admin');
        $this->actingAs($admin)->get('/admin/contacts')->assertOk()->assertSee('Course availability');
        $this->get('/admin/contacts/'.$contact->id)->assertOk()->assertSee('A Learner');
        $this->patch('/admin/contacts/'.$contact->id, ['status' => 'responded'])
            ->assertRedirect(route('admin.contacts.show', $contact));
        $this->assertNotNull($contact->fresh()->responded_at);
        $this->assertDatabaseHas('audit_events', ['subject_id' => $contact->id, 'action' => 'contacts.status_updated']);
    }

    public function test_invalid_message_is_rejected(): void
    {
        $this->post('/contact', [
            'name' => 'Test',
            'email' => 'not-an-email',
            'message' => 'short',
        ])->assertSessionHasErrors(['email', 'message']);

        $this->assertDatabaseCount('contacts', 0);
    }

    public function test_student_cannot_read_private_inquiries_or_update_status(): void
    {
        $contact = Contact::create([
            'name' => 'Someone',
            'email' => 'someone@example.test',
            'message' => 'This message must remain private.',
        ]);
        $student = $this->account('student');

        $this->actingAs($student)->get('/admin/contacts')->assertForbidden();
        $this->get('/admin/contacts/'.$contact->id)->assertForbidden();
        $this->patch('/admin/contacts/'.$contact->id, ['status' => 'responded'])->assertForbidden();
        $this->assertSame('new', $contact->fresh()->status);
    }
}
