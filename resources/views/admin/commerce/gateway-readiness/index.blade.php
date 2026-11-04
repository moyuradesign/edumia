@extends('layouts.panel')
@section('title', 'Payment provider readiness')
@section('content')
<div class="admin-dashboard">
    <div class="admin-welcome">
        <div><span class="admin-eyebrow">COMMERCE / PROVIDERS</span>
            <h1>External payment readiness</h1>
            <p>Inspect operator configuration and test Stripe API connectivity without exposing secrets or activating real-money transactions.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('admin.commerce.orders.index') }}">Orders</a>
            <a class="admin-action-secondary" href="{{ route('admin.commerce.accounting.index') }}">Accounting</a>
        </div>
    </div>
    <div class="admin-stats">
        <div class="admin-stat"><span class="admin-stat-top"><span>Stripe mode</span></span>
            <strong>{{ strtoupper($readiness['mode']) }}</strong>
            <small>{{ $readiness['enabled'] ? 'Configured enabled' : 'Disabled in private environment' }}</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>API key</span></span>
            <strong>{{ $readiness['key_valid_for_mode'] ? 'Set' : 'Missing' }}</strong>
            <small>Never displayed on this page</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>Webhook signing</span></span>
            <strong>{{ $readiness['webhook_configured'] ? 'Configured' : 'Missing' }}</strong>
            <small>Real signed event delivery must still be tested</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>HTTPS URL</span></span>
            <strong>{{ $readiness['https'] ? 'Ready' : 'Required' }}</strong>
            <small>Public HTTPS is mandatory outside isolated tests</small></div>
    </div>
    <section class="admin-widget">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">EXTERNAL ACCEPTANCE</span>
            <h2>Real-money activation is a separate release gate</h2>
            <p>API connectivity is not proof of account eligibility, signed webhook fulfillment, refunds, settlement, currency or tax compliance.</p>
        </div></div>
        <p><strong>Live transactions:</strong>
            <span class="admin-status admin-status--neutral">{{ $readiness['live_approved'] ? 'Operator flag set (external verification still required)' : 'Not approved' }}</span></p>
        <p><strong>Bakong status verifier:</strong> {{ $readiness['bakong_enabled'] ? 'Read-only verification configured' : 'Disabled' }}.
            Dynamic KHQR issuance and paid checkout are not connected.</p>
        <p>The operator must independently verify merchant country eligibility and payout settlement with the provider. Do not use a fictitious country or borrowed credentials.</p>
        @if($readiness['test_connectivity_available'])
            <form method="post" action="{{ route('admin.commerce.gateway-readiness.check') }}">
                @csrf
                <button class="btn btn-primary">Verify Stripe TEST API connection</button>
            </form>
            <p class="subtle">Uses your server-side test key to request account metadata. Only a sanitized outcome is recorded. This action never charges a card or enables checkout.</p>
        @else
            <p class="subtle">To test connectivity, configure STRIPE_MODE=test and a real sk_test_ key in the private environment. The verification button appears only after this prerequisite.</p>
        @endif
    </section>
    <section class="admin-widget">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">PROVIDER EVIDENCE</span>
            <h2>Recent account connectivity checks</h2>
            <p>These are genuine request outcomes once a test key is configured, not sample data.</p></div></div>
        @if($checks->isEmpty())
            <div class="admin-empty"><strong>No provider checks recorded</strong>
                <p>Run a test-account connectivity check after configuring private test credentials.</p></div>
        @else
            <div class="table-scroll"><table class="admin-table">
                <thead><tr><th>Date</th><th>Provider</th><th>Mode</th><th>Result</th><th>Account country</th><th>Charges flag</th></tr></thead>
                <tbody>@foreach($checks as $check)
                    <tr>
                        <td>{{ $check->checked_at?->format('M j, Y H:i') }} UTC</td>
                        <td>{{ strtoupper($check->provider) }}</td>
                        <td>{{ strtoupper($check->mode) }}</td>
                        <td><span class="admin-status admin-status--{{ $check->result==='connected'?'success':'neutral' }}">{{ ucfirst($check->result) }}</span></td>
                        <td>{{ $check->country ?? 'Not provided' }}</td>
                        <td>{{ $check->charges_enabled === null ? 'Unknown' : ($check->charges_enabled ? 'Yes' : 'No') }}</td>
                    </tr>
                @endforeach</tbody>
            </table></div>
        @endif
    </section>
</div>
@endsection
