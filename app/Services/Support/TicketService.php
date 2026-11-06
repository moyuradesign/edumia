<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Models\AuditEvent;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;

final class TicketService
{
    public function create(User $requester, array $attributes): Ticket
    {
        abort_unless($requester->isActive(), 403);

        return DB::transaction(function () use ($requester, $attributes): Ticket {
            $status = $this->status('open');

            $ticket = Ticket::create([
                'requester_id' => $requester->getKey(),
                'status_id' => $status->getKey(),
                'subject' => $attributes['subject'],
                'description' => $attributes['description'],
            ]);

            // Staff receive only an ID-based notification; customer
            // descriptions are never copied into an admin-wide inbox.
            User::query()->where('role', 'admin')->where('status', 'active')
                ->orderBy('id')->chunkById(100, function ($admins) use ($ticket): void {
                    foreach ($admins as $admin) {
                        app(NotificationService::class)->deliver($admin, 'support_reply',
                            'New support ticket', 'A customer opened a support ticket.',
                            '/admin/tickets/'.$ticket->getKey());
                    }
                });

            return $ticket;
        });
    }

    public function reply(User $actor, Ticket $ticket, string $body): void
    {
        $this->assertCanRead($actor, $ticket);

        DB::transaction(function () use ($actor, $ticket, $body): void {
            $ticket = Ticket::query()->lockForUpdate()->with('status')->findOrFail($ticket->getKey());
            $this->assertCanRead($actor, $ticket);
            abort_if($ticket->status?->is_closed, 422, 'Closed tickets must be reopened before replying.');

            $ticket->messages()->create([
                'sender_id' => $actor->getKey(),
                'body' => $body,
                'is_internal' => false,
            ]);

            if ($actor->getKey() !== $ticket->requester_id) {
                $requester = User::query()->find($ticket->requester_id);
                if ($requester) {
                    app(NotificationService::class)->deliver($requester, 'support_reply',
                        'New support reply', 'Your support ticket has a new reply.',
                        '/support/tickets/'.$ticket->getKey());
                }
            } else {
                User::query()->where('role', 'admin')->where('status', 'active')
                    ->orderBy('id')->chunkById(100, function ($admins) use ($ticket): void {
                        foreach ($admins as $admin) {
                            app(NotificationService::class)->deliver($admin, 'support_reply',
                                'Support ticket updated', 'A customer replied to a support ticket.',
                                '/admin/tickets/'.$ticket->getKey());
                        }
                    });
            }
        });
    }

    public function changeStatus(User $actor, Ticket $ticket, string $status): void
    {
        abort_unless($actor->hasPermission('support.tickets.manage'), 403);

        DB::transaction(function () use ($actor, $ticket, $status): void {
            $ticket = Ticket::query()->lockForUpdate()->with('status')->findOrFail($ticket->getKey());
            $before = $ticket->status?->slug;
            if ($before === $status) {
                return;
            }

            $target = $this->status($status);
            $ticket->forceFill([
                'status_id' => $target->getKey(),
                'closed_at' => $target->is_closed ? now() : null,
            ])->save();

            AuditEvent::create([
                'actor_id' => $actor->getKey(),
                'subject_type' => Ticket::class,
                'subject_id' => $ticket->getKey(),
                'action' => 'tickets.status_updated',
                'previous_values' => ['status' => $before],
                'new_values' => ['status' => $status],
            ]);
            $requester = User::query()->find($ticket->requester_id);
            if ($requester) {
                app(NotificationService::class)->deliver($requester, 'support_status',
                    'Support ticket '.$status,
                    'Your support ticket status has changed.',
                    '/support/tickets/'.$ticket->getKey());
            }
        });
    }

    public function assertCanRead(User $actor, Ticket $ticket): void
    {
        abort_unless($actor->isActive() && ($actor->hasPermission('support.tickets.manage') || $ticket->requester_id === $actor->getKey()), 403);
    }

    private function status(string $slug): TicketStatus
    {
        abort_unless(in_array($slug, ['open', 'closed'], true), 422);

        return TicketStatus::firstOrCreate(
            ['slug' => $slug],
            ['name' => ucfirst($slug), 'is_closed' => $slug === 'closed']
        );
    }
}
