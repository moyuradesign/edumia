<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Jobs\SendOperationalMail;
use App\Models\AuditEvent;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Notifications\OperationalNotice;
use Illuminate\Support\Facades\DB;
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
        // Database notifications share the current DB transaction; rollback
        // removes them if the triggering approval/ticket change fails.
        if ($this->enabled($recipient, $event, 'database')) {
            $recipient->notify($notice);
        }

        if (! $this->mailReady() || ! $this->enabled($recipient, $event, 'mail')) {
            return;
        }

        // Explicitly create a database notification for the mail job even
        // when the recipient disabled the visible in-app channel.
        if ($notice->id === null) {
            $recipient->notify($notice);
            $recipient->notifications()->whereKey($notice->id)->delete();
        }
        $userId = $recipient->getKey();
        $notificationId = $notice->id;
        $queue = static function () use ($userId, $notificationId): void {
            SendOperationalMail::dispatch($userId, $notificationId)
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
