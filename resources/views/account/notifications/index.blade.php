@extends('layouts.panel')
@section('title', 'Notifications')
@section('content')
<section class="admin-settings-workspace edumia-notifications" aria-labelledby="notifications-title">
    <div class="admin-welcome">
        <div>
            <span class="admin-eyebrow">PERSONAL WORKSPACE</span>
            <h1 id="notifications-title">Notifications</h1>
            <p>Stay up to date with your courses, support requests, applications and account security.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('account.notifications.preferences.edit') }}">Delivery preferences</a>
            @if($unreadCount > 0)
                <form method="post" action="{{ route('account.notifications.read-all', array_merge(['status' => $status, 'event' => $event], $search !== '' ? ['q' => $search] : [])) }}">
                    @csrf @method('PATCH')
                    <button class="admin-action-primary" type="submit">Mark all as read</button>
                </form>
            @endif
        </div>
    </div>
    <nav class="edumia-notification-tabs" aria-label="Notification status filters">
        @foreach(['all' => 'All updates', 'unread' => 'Unread', 'read' => 'Read'] as $key => $label)
            <a class="edumia-notification-tab {{ $status === $key ? 'is-active' : '' }}"
                href="{{ route('account.notifications.index', array_merge(['status' => $key, 'event' => $event], $search !== '' ? ['q' => $search] : [])) }}"
                @if($status === $key) aria-current="page" @endif>
                <span>{{ $label }}</span><strong>{{ number_format($counts[$key]) }}</strong>
            </a>
        @endforeach
    </nav>
    <section class="admin-widget" aria-labelledby="inbox-feed-title">
        <div class="admin-widget-heading">
            <div>
                <span class="admin-widget-kicker">PRIVATE INBOX</span>
                <h2 id="inbox-feed-title">Recent activity</h2>
                <p>{{ number_format($unreadCount) }} unread notifications. Only you can access these updates.</p>
            </div>
        </div>
        <form class="edumia-notification-filters" method="get" action="{{ route('account.notifications.index') }}">
            <input type="hidden" name="status" value="{{ $status }}">
            <label for="notification-search">Search</label>
            <input id="notification-search" type="search" name="q" value="{{ $search }}"
                   maxlength="80" placeholder="Search your updates…" autocomplete="off">
            <label for="notification-event">Activity type</label>
            <select id="notification-event" name="event">
                <option value="all" @selected($event === 'all')>All activity</option>
                @foreach($events as $key => $label)
                    <option value="{{ $key }}" @selected($event === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="admin-action-primary" type="submit">Apply filter</button>
            @if($status !== 'all' || $event !== 'all' || $search !== '')
                <a class="admin-action-secondary" href="{{ route('account.notifications.index') }}">Clear filters</a>
            @endif
        </form>
        <p class="edumia-notification-results" role="status">
            Showing {{ $notices->firstItem() ?? 0 }}–{{ $notices->lastItem() ?? 0 }}
            of {{ number_format($notices->total()) }} matching updates
        </p>
        <div class="edumia-notification-feed">
            @forelse($notices as $notice)
                @php
                    $eventKey = data_get($notice->data, 'event', '');
                    $noticePath = data_get($notice->data, 'path', '');
                    $safePath = is_string($noticePath) && str_starts_with($noticePath, '/')
                        && !str_starts_with($noticePath, '//')
                        && !str_contains($noticePath, "\r") && !str_contains($noticePath, "\n")
                        && !str_contains($noticePath, "\\");
                @endphp
                <article class="edumia-notification-item {{ $notice->read_at ? 'is-read' : 'is-unread' }}">
                    <span class="edumia-notification-indicator" aria-hidden="true"></span>
                    <div class="edumia-notification-content">
                        <div class="edumia-notification-title-row">
                            <h3>{{ data_get($notice->data, 'title', 'Account update') }}</h3>
                            <span class="edumia-notification-state">{{ $notice->read_at ? 'Read' : 'Unread' }}</span>
                        </div>
                        <p>{{ data_get($notice->data, 'message', '') }}</p>
                        <div class="edumia-notification-meta">
                            <span>{{ $events[$eventKey] ?? 'Account update' }}</span>
                            <time datetime="{{ $notice->created_at?->toAtomString() }}">{{ $notice->created_at?->format('M j, Y · H:i') }}</time>
                        </div>
                    </div>
                    <div class="edumia-notification-actions">
                        @if($safePath)<a class="admin-action-secondary" href="{{ $noticePath }}">Open related page</a>@endif
                        @if($notice->read_at)
                            <form method="post" action="{{ route('account.notifications.unread', array_merge(['notification' => $notice->id, 'status' => $status, 'event' => $event], $search !== '' ? ['q' => $search] : [])) }}">
                                @csrf @method('PATCH')
                                <button class="admin-action-secondary" type="submit">Mark unread</button>
                            </form>
                        @else
                            <form method="post" action="{{ route('account.notifications.read', array_merge(['notification' => $notice->id, 'status' => $status, 'event' => $event], $search !== '' ? ['q' => $search] : [])) }}">
                                @csrf @method('PATCH')
                                <button class="admin-action-secondary" type="submit">Mark read</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="edumia-notification-empty">
                    <strong>{{ $status === 'unread' ? 'You are all caught up' : 'No matching notifications' }}</strong>
                    <p>{{ $status === 'unread' ? 'There are no unread notifications in this view.' : 'New account updates will appear here. Try another filter.' }}</p>
                    @if($status !== 'all' || $event !== 'all' || $search !== '')
                        <a class="admin-action-secondary" href="{{ route('account.notifications.index') }}">View all notifications</a>
                    @endif
                </div>
            @endforelse
        </div>
        <div class="admin-course-pagination">{{ $notices->onEachSide(1)->links() }}</div>
    </section>
</section>
@endsection
