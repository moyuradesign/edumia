<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\System;

use App\Mail\OperationalMail;
use App\Models\AuditEvent;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

final class MailDeliveryController extends Controller
{
    public function index(Request $request, NotificationService $notices): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('admin.system.mail-delivery', [
            'provider' => config('mail.default'),
            'transportReady' => $notices->mailReady(),
            'smtpTestEnabled' => (bool) config('notifications.smtp_test_enabled'),
            'providerVerified' => (bool) config('notifications.provider_verified'),
            'queueDriver' => config('queue.default'),
            // Read-only queue telemetry. Counts do not imply that a worker is running.
            'pendingNotifications' => DB::table('jobs')->where('queue', 'notifications')->count(),
            'failedNotifications' => DB::table('failed_jobs')->where('queue', 'notifications')->count(),
            'lastTransportAcceptance' => AuditEvent::query()
                ->where('action', 'notifications.mail_accepted')
                ->latest('id')->value('created_at'),
            'workerVerified' => (bool) config('notifications.queue_worker_verified', false),
            'recentActivity' => AuditEvent::query()->with('actor')
                ->whereIn('action', ['notifications.mail_accepted', 'notifications.mail_failed',
                    'notifications.smtp_test_accepted', 'notifications.smtp_test_failed'])
                ->latest('id')->paginate(20),
        ]);
    }

    public function test(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless((bool) config('notifications.smtp_test_enabled')
            && config('mail.default') === 'smtp', 403,
            'SMTP testing is disabled until the operator configures the provider.');

        $actor = $request->user();
        $action = 'notifications.smtp_test_accepted';
        try {
            Mail::to($actor->email)->send(new OperationalMail(
                'SMTP connectivity test', 'This is an operator-requested SMTP test.',
                route('account.notifications.index')
            ));
        } catch (Throwable $exception) {
            $action = 'notifications.smtp_test_failed';
        }

        AuditEvent::create([
            'actor_id' => $actor->getKey(),
            'subject_type' => User::class,
            'subject_id' => $actor->getKey(), 'action' => $action,
            'new_values' => ['transport' => 'smtp', 'result' => $action === 'notifications.smtp_test_accepted'
                ? 'transport_accepted' : 'test_failed'],
        ]);

        return to_route('admin.notifications.delivery')
            ->with($action === 'notifications.smtp_test_accepted' ? 'success' : 'error',
                $action === 'notifications.smtp_test_accepted'
                    ? 'SMTP transport accepted the test message. Confirm receipt independently.'
                    : 'SMTP test failed. Check protected server mail settings and provider logs.');
    }
}
