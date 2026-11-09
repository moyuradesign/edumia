<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Services\Notifications\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->inboxFilters($request);
        $user = $request->user();
        $query = $user->notifications();

        if ($filters['status'] === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filters['status'] === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($filters['event'] !== 'all') {
            $query->where('data->event', $filters['event']);
        }

        return view('account.notifications.index', [
            'notices' => $query->latest()->paginate(15)->withQueryString(),
            'unreadCount' => $user->unreadNotifications()->count(),
            'counts' => [
                'all' => $user->notifications()->count(),
                'unread' => $user->unreadNotifications()->count(),
                'read' => $user->notifications()->whereNotNull('read_at')->count(),
            ],
            'status' => $filters['status'],
            'event' => $filters['event'],
            'events' => NotificationService::EVENTS,
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $notice = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $notice->markAsRead();

        return to_route('account.notifications.index', $this->inboxFilters($request))
            ->with('success', 'Notification marked as read.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return to_route('account.notifications.index', $this->inboxFilters($request))
            ->with('success', 'All notifications marked as read.');
    }

    public function preferences(Request $request, NotificationService $notifications): View
    {
        $user = $request->user();
        $enabled = [];
        foreach (NotificationService::EVENTS as $event => $label) {
            foreach (['database', 'mail'] as $channel) {
                $enabled[$event][$channel] = $notifications->enabled($user, $event, $channel);
            }
        }

        return view('account.notifications.preferences', [
            'events' => NotificationService::EVENTS,
            'enabled' => $enabled,
            'mailReady' => $notifications->mailReady(),
        ]);
    }

    public function updatePreferences(Request $request, NotificationService $notifications): RedirectResponse
    {
        $data = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*' => ['array:database,mail'],
            'preferences.*.database' => ['sometimes', 'boolean'],
            'preferences.*.mail' => ['sometimes', 'boolean'],
        ]);
        $notifications->savePreferences($request->user(), $data['preferences']);

        return to_route('account.notifications.preferences.edit')
            ->with('success', 'Notification preferences saved.');
    }

    /** Preserve only recognized, account-scoped inbox filters after a POST. */
    private function inboxFilters(Request $request): array
    {
        $rawStatus = $request->query('status', 'all');
        $rawEvent = $request->query('event', 'all');

        return [
            'status' => is_string($rawStatus) && in_array($rawStatus, ['all', 'unread', 'read'], true)
                ? $rawStatus : 'all',
            'event' => is_string($rawEvent) && ($rawEvent === 'all'
                || array_key_exists($rawEvent, NotificationService::EVENTS))
                ? $rawEvent : 'all',
        ];
    }
}
