@extends('layouts.panel')
@section('title', 'Email delivery')
@section('content')
<section class="admin-settings-workspace" aria-labelledby="mail-readiness-title">
    <div class="admin-welcome"><div><span class="admin-eyebrow">PLATFORM OPERATIONS</span>
        <h1 id="mail-readiness-title">Email delivery</h1>
        <p>Review secure provider readiness and delivery outcomes. Credentials are never displayed here.</p>
    </div></div>
    <div class="admin-settings-grid">
        <section class="admin-widget">
            <div class="admin-widget-heading"><div><span class="admin-widget-kicker">PROVIDER GATE</span>
                <h2>Delivery readiness</h2>
            </div></div>
            <p>Mail transport: <strong>{{ $provider === 'smtp' ? 'SMTP' : 'Disabled / log-only' }}</strong></p>
            <p>Queue processing: <strong>{{ $queueDriver === 'database' ? 'Database queue configured' : 'Worker setup required' }}</strong></p>
            <p>Provider verification: <strong>{{ $providerVerified ? 'Operator-approved' : 'Not yet approved' }}</strong></p>
            <p>Transactional email: <strong>{{ $transportReady ? 'Enabled by verified server configuration' : 'Disabled' }}</strong></p>
            <p class="admin-field-hint">SMTP transport acceptance is not proof of inbox delivery. A running queue worker and independent recipient verification are required.</p>
        </section>
        <section class="admin-widget">
            <div class="admin-widget-heading"><div><span class="admin-widget-kicker">OPERATOR CHECK</span>
                <h2>Send a private test message</h2>
            </div></div>
            <p>Test messages are sent only to the signed-in administrator account and only after the separate protected SMTP test gate is enabled.</p>
            <form method="post" action="{{ route('admin.notifications.delivery.test') }}">
                @csrf
                <button class="admin-action-primary" type="submit" @disabled(!$smtpTestEnabled || $provider !== 'smtp')>Send SMTP test to my account</button>
            </form>
            @if(session('error'))<p class="flash error" role="alert">{{ session('error') }}</p>@endif
        </section>
    </div>
    <section class="admin-widget">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">ACCOUNTABILITY</span>
            <h2>Delivery activity</h2><p>Administrative result categories only; no passwords, message bodies, or recipient email addresses are recorded.</p>
        </div></div>
        <div class="table-scroll"><table class="admin-table">
            <thead><tr><th>Recorded</th><th>Result</th><th>Reference</th><th>Actor</th></tr></thead>
            <tbody>
                @forelse($recentActivity as $event)
                    <tr><td>{{ $event->created_at?->format('M j, Y · H:i') }}</td>
                        <td>{{ str_replace(['notifications.', '_'], ['', ' '], $event->action) }}</td>
                        <td>Account #{{ $event->subject_id }}</td>
                        <td>{{ $event->actor?->name ?? 'Queue worker' }}</td></tr>
                @empty
                    <tr><td colspan="4"><div class="admin-empty"><strong>No delivery attempts recorded</strong></div></td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="admin-course-pagination">{{ $recentActivity->onEachSide(1)->links() }}</div>
    </section>
</section>
@endsection
