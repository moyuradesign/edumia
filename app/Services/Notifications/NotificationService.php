<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Jobs\SendOperationalMail;
use App\Models\AuditEvent;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Notifications\OperationalNotice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class NotificationService
{
    public const EVENTS = [
        'instructor_decision' => 'Instructor application decisions',
        'support_reply' => 'Support ticket replies',
        'support_status' => 'Support ticket status updates',
        'account_security' => 'Important account security changes',
    ];

    public function enabled(User $user, string $event, string $channel): bool
    {
        abort_unless(array_key_exists($event, self::EVENTS), 422);
        abort_unless(in_array($channel, ['database', 'mail'], true), 422);

        // Essential security alerts cannot be disabled in the account inbox.
        if ($event === 'account_security' && $channel === 'database') {
            return true;
        }

        $stored = NotificationSetting::query()
            ->where('user_id', $user->getKey())->where('event_key', $event)
            ->where('channel', $channel)->value('enabled');

        return $stored === null ? true : (bool) $stored;
    }

    public function deliver(
        User $recipient,
        string $event,
        string $headline,
        string $summary,
        string $path
    ): void {
        abort_unless(array_key_exists($event, self::EVENTS), 422);
        abort_unless(str_starts_with($path, '/') && ! str_starts_with($path, '//')
            && ! str_contains($path, "\r") && ! str_contains($path, "\n"), 422);

        if ($recipient->trashed()) {
            return;
        }

        $notice = new OperationalNotice($event, $headline, $summary, $path);
        // Visible in-app messages share the business transaction. Opting
        // out leaves no database notification behind.
        $visible = $this->enabled($recipient, $event, 'database');
        if ($visible) {
            // Laravel sends a cloned notification; assign its ID before dispatch
            // so the database notice and queued email share the same UUID.
            $notice->id = (string) Str::uuid();
            $recipient->notify($notice);
        }

        if (! $this->mailReady() || ! $this->enabled($recipient, $event, 'mail')) {
            return;
        }

        // When in-app messages are off, queue a minimal event summary
        // rather than creating (then deleting) a private inbox record.
        $userId = $recipient->getKey();
        $notificationId = $visible ? $notice->id : (string) Str::uuid();
        $mailSnapshot = $visible ? null : $notice->toDatabase($recipient);
        $queue = static function () use ($userId, $notificationId, $mailSnapshot): void {
            SendOperationalMail::dispatch($userId, $notificationId, $mailSnapshot)
                ->onQueue((string) config('notifications.queue_name', 'notifications'));
            AuditEvent::create([
                'actor_id' => null, 'subject_type' => User::class,
                'subject_id' => $userId, 'action' => 'notifications.mail_queued',
                'new_values' => ['notification_id' => $notificationId],
            ]);
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($queue);
        } else {
            $queue();
        }
    }

    public function mailReady(): bool
    {
        // Configuration is resolved from protected runtime environment only.
        // Never read, log or display MAIL_PASSWORD in the web interface.
        return (bool) config('notifications.transactional_mail_enabled', false)
            && (bool) config('notifications.provider_verified', false)
            && (bool) config('notifications.queue_worker_verified', false)
            && config('queue.default') === 'database'
            && config('mail.default') === 'smtp'
            && filled(config('mail.mailers.smtp.host'))
            && filled(config('mail.from.address'));
    }

    public function savePreferences(User $user, array $preferences): void
    {
        $unknown = array_diff(array_keys($preferences), array_keys(self::EVENTS));
        if ($unknown !== []) {
            throw ValidationException::withMessages(['preferences' => 'Unknown notification preference.']);
        }

        DB::transaction(function () use ($user, $preferences): void {
            foreach (self::EVENTS as $event => $label) {
                foreach (['database', 'mail'] as $channel) {
                    $flag = $event === 'account_security' && $channel === 'database'
                        ? true : (bool) ($preferences[$event][$channel] ?? false);
                    $old = $this->enabled($user, $event, $channel);
                    if ($old === $flag) {
                        continue;
                    }
                    NotificationSetting::query()->updateOrCreate(
                        ['user_id' => $user->getKey(), 'event_key' => $event, 'channel' => $channel],
                        ['enabled' => $flag]
                    );
                    AuditEvent::create([
                        'actor_id' => $user->getKey(),
                        'subject_type' => User::class,
                        'subject_id' => $user->getKey(),
                        'action' => 'notifications.preference_changed',
                        'previous_values' => ['event' => $event, 'channel' => $channel, 'enabled' => $old],
                        'new_values' => ['event' => $event, 'channel' => $channel, 'enabled' => $flag],
                    ]);
                }
            }
        });
    }
}
