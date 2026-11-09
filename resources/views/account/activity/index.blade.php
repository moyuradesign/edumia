@extends('layouts.panel')
@section('title', 'Account activity')
@section('content')
<section class="account-preferences-workspace edumia-account-history" aria-labelledby="account-history-heading">
    <header class="admin-welcome">
        <div>
            <span class="admin-eyebrow">PRIVATE ACCOUNT HISTORY</span>
            <h1 id="account-history-heading">Account activity</h1>
            <p>Review your profile and notification setting changes. Private before-and-after values are never displayed.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('account.preferences.edit') }}">Profile settings</a>
            <a class="admin-action-secondary" href="{{ route('password.edit') }}">Password &amp; security</a>
            <a class="admin-action-secondary" href="{{ route('account.notifications.index') }}">Notification inbox</a>
        </div>
    </header>

    <nav class="edumia-account-history-filters" aria-label="Account activity filters">
        @foreach($types as $key => $label)
            <a class="edumia-account-history-filter {{ $type === $key ? 'is-active' : '' }}"
               href="{{ route('account.activity.index', $key === 'all' ? [] : ['type' => $key]) }}"
               @if($type === $key) aria-current="page" @endif>
                <span>{{ $label }}</span>
                <strong>{{ number_format($counts[$key]) }}</strong>
            </a>
        @endforeach
    </nav>

    <section class="admin-widget" aria-labelledby="account-history-list-title">
        <div class="admin-widget-heading">
            <div>
                <span class="admin-widget-kicker">YOUR CHANGES</span>
                <h2 id="account-history-list-title">Activity history</h2>
                <p>These events belong to your signed-in account only.</p>
            </div>
        </div>
        <p class="edumia-notification-results" role="status">
            Showing {{ $activity->firstItem() ?? 0 }}–{{ $activity->lastItem() ?? 0 }}
            of {{ number_format($activity->total()) }} matching changes
        </p>
        <div class="edumia-account-activity-list">
            @forelse($activity as $item)
                <article class="edumia-account-activity-row">
                    <span>{{ $types[$item->action] ?? 'Account updated' }}</span>
                    <time datetime="{{ $item->created_at?->toAtomString() }}">{{ $item->created_at?->format('M j, Y · H:i') }}</time>
                </article>
            @empty
                <div class="edumia-notification-empty">
                    <strong>No matching account activity</strong>
                    <p>Your own profile and notification preference updates will appear here.</p>
                    @if($type !== 'all')
                        <a class="admin-action-secondary" href="{{ route('account.activity.index') }}">View all activity</a>
                    @endif
                </div>
            @endforelse
        </div>
        @if($activity->hasPages())
            <div class="edumia-account-history-pagination">
                <div class="admin-course-pagination">{{ $activity->onEachSide(1)->links() }}</div>
                <form class="edumia-account-page-jump" method="get" action="{{ route('account.activity.index') }}">
                    @if($type !== 'all')<input type="hidden" name="type" value="{{ $type }}">@endif
                    <label for="account-activity-page">Go to page</label>
                    <input id="account-activity-page" type="number" name="page" min="1" max="{{ $activity->lastPage() }}"
                           value="{{ $activity->currentPage() }}" inputmode="numeric" required>
                    <button class="admin-action-secondary" type="submit">Go</button>
                </form>
            </div>
        @endif
    </section>
</section>
@endsection
