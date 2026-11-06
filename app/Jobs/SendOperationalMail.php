<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\OperationalMail;
use App\Models\AuditEvent;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SendOperationalMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        public readonly int $userId,
        public readonly string $notificationId,
        public readonly ?array $snapshot = null,
    ) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(NotificationService $notifications): void
    {
        if (! $notifications->mailReady()) {
            return;
        }

        $user = User::query()->find($this->userId);
        if (! $user || ! $user->isActive() || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $notice = $user->notifications()->whereKey($this->notificationId)->first();
        if (! $notice && $this->snapshot === null) {
            return;
        }
        $data = $notice?->data ?? $this->snapshot;
        $event = data_get($data, 'event');
        if (! is_string($event) || ! array_key_exists($event, NotificationService::EVENTS)
            || ! $notifications->enabled($user, $event, 'mail')) {
            return;
        }

        // A retry after a confirmed successful send must not send twice.
        $alreadySent = AuditEvent::query()
            ->where('subject_type', User::class)->where('subject_id', $user->getKey())
            ->where('action', 'notifications.mail_accepted')
            ->where('new_values->notification_id', $this->notificationId)->exists();
        if ($alreadySent) {
            return;
        }

        $relative = (string) data_get($data, 'path', '/dashboard');
        if (! str_starts_with($relative, '/') || str_starts_with($relative, '//')
            || str_contains($relative, "\r") || str_contains($relative, "\n")) {
            $relative = '/dashboard';
        }
        $destination = rtrim((string) config('app.url'), '/').$relative;

        try {
            Mail::to($user->email)->send(new OperationalMail(
                (string) data_get($data, 'title', 'Account update'),
                (string) data_get($data, 'message', 'There is an update to your account.'),
                $destination
            ));
        } catch (Throwable $exception) {
            // Laravel persists failed-job exception messages. Do not chain
            // the original SMTP exception, whose text may reveal secrets.
            throw new \RuntimeException('Operational mail transport unavailable.');
        }
        // Accepted by the configured SMTP transport, NOT proof of inbox delivery.
        AuditEvent::create([
            'actor_id' => null, 'subject_type' => User::class,
            'subject_id' => $user->getKey(), 'action' => 'notifications.mail_accepted',
            'new_values' => ['notification_id' => $this->notificationId, 'event' => $event,
                'status' => 'transport_accepted'],
        ]);
    }

    public function failed(Throwable $exception): void
    {
        // NEVER log the exception text: SMTP errors sometimes contain URLs,
        // credentials, addresses, or private server details.
        AuditEvent::create([
            'actor_id' => null, 'subject_type' => User::class,
            'subject_id' => $this->userId, 'action' => 'notifications.mail_failed',
            'new_values' => ['notification_id' => $this->notificationId,
                'error_category' => class_basename($exception)],
        ]);
    }
}
