<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Models\AuditEvent;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ContactManagementService
{
    public function updateStatus(User $actor, Contact $contact, string $status): void
    {
        abort_unless($actor->isAdmin(), 403);

        DB::transaction(function () use ($actor, $contact, $status): void {
            $contact = Contact::query()->lockForUpdate()->findOrFail($contact->getKey());
            $previous = $contact->status;

            if ($previous === $status) {
                return;
            }

            $contact->forceFill([
                'status' => $status,
                'responded_at' => $status === 'responded' ? ($contact->responded_at ?? now()) : null,
            ])->save();

            // Deliberately exclude names, email addresses and private message text.
            AuditEvent::create([
                'actor_id' => $actor->getKey(),
                'subject_type' => Contact::class,
                'subject_id' => $contact->getKey(),
                'action' => 'contacts.status_updated',
                'previous_values' => ['status' => $previous],
                'new_values' => ['status' => $status],
            ]);
        });
    }
}
