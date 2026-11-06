@extends('layouts.panel')
@section('title', 'Notifications')
@section('content')
<section class="admin-settings-workspace" aria-labelledby="notifications-title">
    <div class="admin-welcome">
        <div><span class="admin-eyebrow">PERSONAL WORKSPACE</span>
            <h1 id="notifications-title">Notifications</h1>
            <p>Important updates about your applications, support tickets, and account security.</p></div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('account.notifications.preferences.edit') }}">Delivery preferences</a>
            @if($unreadCount > 0)
                <form method="post" action="{{ route('account.notifications.read-all') }}">
                    @csrf @method('PATCH')
                    <button class="admin-action-primary" type="submit">Mark all as read</button>
                </form>
            @endif
        </div>
    </div>
    <section class="admin-widget">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">ACCOUNT INBOX</span>
            <h2>Recent activity</h2>
            <p>{{ number_format($unreadCount) }} unread notifications. Messages are visible only to this account.</p>
        </div></div>
        <div class="table-scroll"><table class="admin-table">
            <thead><tr><th>Update</th><th>Received</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($notices as $notice)
                    <tr>
                        <td><strong>{{ data_get($notice->data, 'title', 'Account update') }}</strong>
                            <p>{{ data_get($notice->data, 'message', '') }}</p>
                            @php($noticePath = data_get($notice->data, 'path', '/dashboard'))
                            @if(is_string($noticePath) && str_starts_with($noticePath, '/') && !str_starts_with($noticePath, '//'))
                                <a href="{{ $noticePath }}">Open related page</a>
                            @endif
                        </td>
                        <td>{{ $notice->created_at?->format('M j, Y · H:i') }}</td>
                        <td><span class="admin-status">{{ $notice->read_at ? 'Read' : 'Unread' }}</span></td>
                        <td>
                            @unless($notice->read_at)
                                <form method="post" action="{{ route('account.notifications.read', $notice->id) }}">
                                    @csrf @method('PATCH')
                                    <button class="admin-action-secondary" type="submit">Mark read</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="admin-empty">
                        <strong>No notifications yet</strong>
                        <p>Your account updates will appear here.</p>
                    </div></td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="admin-course-pagination">{{ $notices->onEachSide(1)->links() }}</div>
    </section>
</section>
@endsection
