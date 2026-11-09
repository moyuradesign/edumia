<details class="edumia-account-bell" data-account-bell>
    <summary class="admin-topbar-icon edumia-account-bell-trigger"
        aria-label="Notifications{{ $unreadNotificationCount > 0 ? ', '.$unreadNotificationCount.' unread' : ', none unread' }}"
        title="Account notifications">
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.75"
             stroke-linejoin="round" stroke-linecap="round">
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/>
        </svg>
        @if($unreadNotificationCount > 0)
            <span class="edumia-account-bell-count" aria-hidden="true">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
        @endif
    </summary>
    <div class="edumia-account-bell-panel">
        <div class="edumia-account-bell-heading">
            <div>
                <strong>Notifications</strong>
                <span>{{ number_format($unreadNotificationCount) }} unread</span>
            </div>
            <a href="{{ route('account.notifications.preferences.edit') }}" aria-label="Notification delivery preferences">Preferences</a>
        </div>
        @forelse($notificationPreview as $item)
            <div class="edumia-account-bell-item">
                <span class="edumia-account-bell-dot" aria-hidden="true"></span>
                <div>
                    <strong>{{ data_get($item->data, 'title', 'Account update') }}</strong>
                    <time datetime="{{ $item->created_at?->toAtomString() }}">{{ $item->created_at?->format('M j, H:i') }}</time>
                </div>
                <form method="post" action="{{ route('account.notifications.read', ['notification' => $item->id]) }}">
                    @csrf @method('PATCH')
                    <button type="submit" title="Mark this notification read" aria-label="Mark notification as read">✓</button>
                </form>
            </div>
        @empty
            <p class="edumia-account-bell-empty">You're all caught up. No unread notifications.</p>
        @endforelse
        <div class="edumia-account-bell-footer">
            <a href="{{ route('account.notifications.index') }}">Open notification inbox</a>
            @if($unreadNotificationCount > 0)
                <form method="post" action="{{ route('account.notifications.read-all') }}">
                    @csrf @method('PATCH')
                    <button type="submit">Mark all read</button>
                </form>
            @endif
        </div>
    </div>
</details>
