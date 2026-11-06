<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class OperationalNotice extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $eventKey,
        public readonly string $headline,
        public readonly string $summary,
        public readonly string $path,
    ) {}

    public function via(object $notifiable): array
    {
        // Store the in-app event immediately as part of the business transaction.
        // External email is handled by a separately gated/retried queue job.
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'event' => $this->eventKey,
            'title' => $this->headline,
            'message' => $this->summary,
            'path' => $this->path,
        ];
    }
}
