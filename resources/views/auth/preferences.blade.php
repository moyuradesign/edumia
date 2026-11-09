@extends('layouts.panel')
@section('title', 'Account preferences')
@section('content')
<section class="account-preferences-workspace" aria-labelledby="account-preferences-heading">
    <header class="admin-welcome">
        <div>
            <span class="admin-eyebrow">MY ACCOUNT</span>
            <h1 id="account-preferences-heading">Account preferences</h1>
            <p>Update your display name and preferred language. Review the activity associated with your account.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('password.edit') }}">Password & security</a>
            <button class="admin-action-primary" type="submit" form="account-preferences-form">Save preferences</button>
        </div>
    </header>

    <div class="admin-stats" aria-label="Your account activity">
        <a class="admin-stat" href="{{ route('account.courses') }}">
            <span class="admin-stat-top"><span>My enrollments</span></span>
            <strong>{{ number_format($member->enrollments_count) }}</strong><small>Learning records</small>
        </a>
        @if($member->canTeach())
            <a class="admin-stat" href="{{ route('instructor.courses.index') }}">
                <span class="admin-stat-top"><span>My teaching courses</span></span>
                <strong>{{ number_format($member->courses_count) }}</strong><small>Instructor course records</small>
            </a>
        @endif
        <a class="admin-stat" href="{{ route('account.notifications.index', ['status' => 'unread']) }}">
            <span class="admin-stat-top"><span>Unread notifications</span></span>
            <strong>{{ number_format($unreadNotificationCount) }}</strong><small>Private account updates</small>
        </a>
        <a class="admin-stat" href="{{ route('support.tickets.index') }}">
            <span class="admin-stat-top"><span>Support tickets</span></span>
            <strong>{{ number_format($supportTicketsCount) }}</strong><small>Requests created by you</small>
        </a>
    </div>

    <div class="account-preferences-grid">
        <section class="admin-widget" aria-labelledby="account-details-title">
            <div class="admin-widget-heading">
                <div><span class="admin-widget-kicker">PERSONAL SETTINGS</span>
                    <h2 id="account-details-title">Profile details</h2>
                    <p>Changes to these preferences apply to your signed-in account.</p>
                </div>
            </div>
            <form id="account-preferences-form" class="stack-form" method="post" action="{{ route('account.preferences.update') }}">
                @csrf @method('PUT')
                <label for="account-display-name">Display name
                    <input id="account-display-name" name="name" value="{{ old('name', $member->name) }}"
                        required minlength="2" maxlength="150" autocomplete="name">
                </label>
                <label for="account-language">Preferred language
                    <select id="account-language" name="locale" required>
                        @foreach ($languages as $code => $name)
                            <option value="{{ $code }}" @selected(old('locale', $member->locale) === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
                <p class="admin-media-hint">Some learning resources and management screens remain in their original language.</p>
            </form>
        </section>
        <aside class="admin-widget" aria-labelledby="account-summary-heading">
            <div class="admin-widget-heading">
                <div><span class="admin-widget-kicker">ACCOUNT STATUS</span>
                    <h2 id="account-summary-heading">Your account</h2>
                    <p>Verified details from your profile.</p>
                </div>
            </div>
            <dl class="account-profile-facts">
                <div><dt>Email address</dt><dd>{{ $member->email }}</dd></div>
                <div><dt>Account type</dt><dd>{{ ucfirst($member->role) }}</dd></div>
                <div><dt>Account status</dt><dd>{{ ucfirst($member->status) }}</dd></div>
                <div><dt>Joined</dt><dd>{{ $member->created_at?->format('M j, Y') ?? '—' }}</dd></div>
            </dl>
            <p class="admin-media-hint">Email and access permissions are managed separately for account security.</p>
        </aside>
    </div>
    <section class="admin-widget edumia-account-activity" aria-labelledby="account-activity-title">
        <div class="admin-widget-heading">
            <div>
                <span class="admin-widget-kicker">ACCOUNT HISTORY</span>
                <h2 id="account-activity-title">Recent preference changes</h2>
                <p>Only your own account settings changes are displayed here, without private audit values.</p>
            </div>
            <a href="{{ route('account.notifications.preferences.edit') }}">Notification settings</a>
        </div>
        <div class="edumia-account-activity-list">
            @forelse($recentAccountChanges as $change)
                <div class="edumia-account-activity-row">
                    <span>{{ match ($change->action) {
                        'account.name_updated' => 'Display name changed',
                        'account.locale_updated' => 'Language preference changed',
                        'notifications.preference_changed' => 'Notification preferences changed',
                        default => 'Account updated',
                    } }}</span>
                    <time datetime="{{ $change->created_at?->toAtomString() }}">{{ $change->created_at?->format('M j, Y · H:i') }}</time>
                </div>
            @empty
                <p class="edumia-account-activity-empty">No recent preference changes. Your account history will appear here.</p>
            @endforelse
        </div>
    </section>
</section>
@endsection
