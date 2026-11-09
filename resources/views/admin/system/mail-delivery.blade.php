@extends('layouts.panel')
@section('title', 'Email delivery')
@section('content')
<section class="admin-settings-workspace edumia-notifications" aria-labelledby="mail-readiness-title">
    <div class="admin-welcome">
        <div>
            <span class="admin-eyebrow">PLATFORM OPERATIONS</span>
            <h1 id="mail-readiness-title">Email delivery</h1>
            <p>Monitor notification queues and delivery activity without exposing provider credentials.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('account.notifications.index') }}">My notifications</a>
        </div>
    </div>
    <div class="edumia-notification-operations" aria-label="Delivery statistics">
        <div class="edumia-notification-metric">
            <span>Pending notification jobs</span>
            <strong>{{ number_format($pendingNotifications) }}</strong>
            <small>{{ $queueDriver === 'database' ? 'Database queue configured' : 'Worker setup required' }}</small>
        </div>
        <div class="edumia-notification-metric">
            <span>Failed notification jobs</span>
            <strong>{{ number_format($failedNotifications) }}</strong>
            <small>Review failures before retrying jobs</small>
        </div>
        <div class="edumia-notification-metric">
            <span>Transactional email</span>
            <strong class="edumia-notification-metric-state">{{ $transportReady ? 'Ready' : 'Disabled' }}</strong>
            <small>{{ $workerVerified ? 'Worker operator verified' : 'Not independently verified' }}</small>
        </div>
    </div>
    <div class="admin-settings-grid">
        <section class="admin-widget">
            <div class="admin-widget-heading"><div>
                <span class="admin-widget-kicker">PROVIDER GATE</span><h2>Delivery readiness</h2>
            </div></div>
            <dl class="edumia-notification-facts">
                <div><dt>Mail transport</dt><dd>{{ $provider === 'smtp' ? 'SMTP' : 'Disabled / log-only' }}</dd></div>
                <div><dt>Queue processing</dt><dd>{{ $queueDriver === 'database' ? 'Database configured' : 'Worker setup required' }}</dd></div>
                <div><dt>Provider verification</dt><dd>{{ $providerVerified ? 'Operator-approved' : 'Not yet approved' }}</dd></div>
                <div><dt>Queue worker</dt><dd>{{ $workerVerified ? 'Operator verified' : 'Not independently verified' }}</dd></div>
                <div><dt>Last SMTP transport acceptance</dt><dd>{{ $lastTransportAcceptance ? $lastTransportAcceptance->format('M j, Y · H:i') : 'None recorded' }}</dd></div>
            </dl>
            <p class="edumia-notification-advisory">SMTP transport acceptance is not proof of inbox delivery. Queue counts do not prove worker health; confirm the Supervisor/cron process and recipient delivery independently.</p>
        </section>
        <section class="admin-widget">
            <div class="admin-widget-heading"><div>
                <span class="admin-widget-kicker">OPERATOR CHECK</span><h2>Send a private test message</h2>
            </div></div>
            <p>Test messages go only to the signed-in administrator when their email exactly matches the approved sandbox recipient configured on the server.</p>
            <p class="edumia-notification-test-status">Test recipient approval:
                <strong>{{ $approvedTestRecipient ? 'Configured for this administrator' : 'Not configured for this administrator' }}</strong>
            </p>
            <form method="post" action="{{ route('admin.notifications.delivery.test') }}">
                @csrf
                <button class="admin-action-primary" type="submit"
                    @disabled(!$smtpTestEnabled || $provider !== 'smtp' || !$approvedTestRecipient)>Send SMTP test to my account</button>
            </form>
            @if(!$smtpTestEnabled || $provider !== 'smtp' || !$approvedTestRecipient)
                <p class="admin-field-hint">SMTP testing remains unavailable until the operator approves the sandbox recipient and transport.</p>
            @endif
            @if(session('error'))<p class="flash error" role="alert">{{ session('error') }}</p>@endif
        </section>
    </div>
    <section class="admin-widget" aria-labelledby="edumia-delivery-activity-title">
        <div class="admin-widget-heading"><div>
            <span class="admin-widget-kicker">ACCOUNTABILITY</span>
            <h2 id="edumia-delivery-activity-title">Delivery activity</h2>
            <p>Administrative result categories only. Message bodies, passwords and recipient addresses are not displayed.</p>
        </div></div>
        <nav class="edumia-notification-activity-tabs" aria-label="Delivery result filters">
            @foreach(['all' => 'All events', 'accepted' => 'Accepted', 'failed' => 'Failed'] as $key => $label)
                <a href="{{ route('admin.notifications.delivery', ['outcome' => $key]) }}"
                   class="edumia-notification-filter-link {{ $outcome === $key ? 'is-active' : '' }}"
                   @if($outcome === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <p class="edumia-notification-results">Showing {{ $recentActivity->firstItem() ?? 0 }}–{{ $recentActivity->lastItem() ?? 0 }}
            of {{ number_format($recentActivity->total()) }} delivery records</p>
        <div class="table-scroll"><table class="admin-table">
            <thead><tr><th scope="col">Recorded</th><th scope="col">Result</th><th scope="col">Reference</th><th scope="col">Actor</th></tr></thead>
            <tbody>
                @forelse($recentActivity as $item)
                    <tr>
                        <td><time datetime="{{ $item->created_at?->toAtomString() }}">{{ $item->created_at?->format('M j, Y · H:i') }}</time></td>
                        <td>{{ str_replace(['notifications.', '_'], ['', ' '], $item->action) }}</td>
                        <td>Account #{{ $item->subject_id }}</td>
                        <td>{{ $item->actor?->name ?? 'Queue worker' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="admin-empty"><strong>No delivery records match this filter.</strong></div></td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="admin-course-pagination">{{ $recentActivity->onEachSide(1)->links() }}</div>
    </section>
</section>
@endsection
