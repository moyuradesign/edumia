<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Services\Notifications\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.notifications.index', [
            'notices' => $request->user()->notifications()->latest()->paginate(20),
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $notice = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $notice->markAsRead();

        return to_route('account.notifications.index')->with('success', 'Notification marked as read.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return to_route('account.notifications.index')->with('success', 'All notifications marked as read.');
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
            'preferences.*' => ['array'],
            'preferences.*.database' => ['sometimes', 'boolean'],
            'preferences.*.mail' => ['sometimes', 'boolean'],
        ]);
        $notifications->savePreferences($request->user(), $data['preferences']);

        return to_route('account.notifications.preferences.edit')
            ->with('success', 'Notification preferences saved.');
    }
}
