@extends('layouts.panel')
@section('title', 'Commerce accounting')
@section('content')
<section class="admin-accounting-workspace" aria-labelledby="admin-accounting-heading">
    <div class="admin-welcome">
        <div>
            <span class="admin-eyebrow">FINANCE & RECONCILIATION</span>
            <h1 id="admin-accounting-heading">Commerce accounting</h1>
            <p>Inspect verified orders, local reconciliation exceptions, and held instructor earnings.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('admin.commerce.orders.index') }}">All orders</a>
            <a class="admin-action-secondary" href="{{ route('admin.commerce.refunds.index') }}">Refund reviews</a>
            <form method="post" action="{{ route('admin.commerce.accounting.reconcile') }}">
                @csrf <button class="admin-action-primary" type="submit">Scan local records</button>
            </form>
        </div>
    </div>

    <div class="admin-accounting-notice" role="note">
        <strong>Reconciliation scope</strong>
        <p>This is a read-only view of internal financial records. The scan compares local records only; external provider settlement and bank statements require independent verification. No automatic payouts, refund initiation, or currency conversion are enabled.</p>
    </div>

    <section class="admin-widget" aria-labelledby="admin-accounting-exceptions">
        <div class="admin-widget-heading">
            <div><span class="admin-widget-kicker">INTEGRITY MONITORING</span><h2 id="admin-accounting-exceptions">Review exceptions</h2>
                <p>Record consistency checks and dispute history.</p></div>
        </div>
        @php
            $issueLabels = [
                'paid_missing_invoice' => 'Verified orders missing invoices',
                'paid_missing_payment_record' => 'Missing payment history',
                'paid_missing_earning_entry' => 'Missing earning entries',
                'open_disputes' => 'Dispute records',
            ];
        @endphp
        <div class="admin-accounting-issues">
            @foreach($issues as $key => $count)
                <div class="admin-accounting-issue">
                    <span>{{ $issueLabels[$key] ?? ucwords(str_replace('_', ' ', $key)) }}</span>
                    <strong>{{ number_format($count) }}</strong>
                </div>
            @endforeach
        </div>
    </section>

    <section class="admin-widget" aria-labelledby="admin-accounting-invoices">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">VERIFIED SALES</span>
            <h2 id="admin-accounting-invoices">Invoice records</h2><p>Original invoice currencies and documented refunds.</p></div></div>
        <div class="table-scroll"><table class="admin-table admin-accounting-table">
            <thead><tr><th scope="col">Invoice</th><th scope="col">Customer</th><th scope="col">Gross</th><th scope="col">Refunded</th><th scope="col">Status</th></tr></thead>
            <tbody>@forelse($invoices as $invoice)
                <tr>
                    <td><strong>{{ $invoice->number }}</strong></td><td>{{ $invoice->customer_email }}</td>
                    <td>{{ \App\Helpers\Localization::minorMoney((int) $invoice->total_minor, $invoice->currency_code, \App\Helpers\Localization::resolve()) }}</td>
                    <td>{{ \App\Helpers\Localization::minorMoney((int) $invoice->refunded_minor, $invoice->currency_code, \App\Helpers\Localization::resolve()) }}</td>
                    <td><span class="admin-status admin-status--{{ $invoice->status === 'paid' ? 'success' : 'neutral' }}">{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</span></td>
                </tr>
            @empty<tr><td colspan="5"><div class="admin-empty"><strong>No verified invoices</strong><p>Invoices appear once orders are verified.</p></div></td></tr>@endforelse</tbody>
        </table></div>
        <div class="admin-course-pagination">{{ $invoices->onEachSide(1)->links() }}</div>
    </section>

    <div class="admin-data-grid admin-accounting-ledgers">
        <section class="admin-widget" aria-labelledby="admin-accounting-earnings">
            <div class="admin-widget-heading"><div><span class="admin-widget-kicker">INSTRUCTOR LEDGER</span><h2 id="admin-accounting-earnings">Held instructor earnings</h2></div></div>
            <div class="table-scroll"><table class="admin-table">
                <thead><tr><th scope="col">Instructor ID</th><th scope="col">Currency</th><th scope="col">Net held</th></tr></thead>
                <tbody>@forelse($earnings as $earning)
                    <tr><td>{{ $earning->instructor_id }}</td><td>{{ $earning->currency_code }}</td>
                        <td><strong>{{ \App\Helpers\Localization::minorMoney((int) $earning->net_held_minor, $earning->currency_code, \App\Helpers\Localization::resolve()) }}</strong></td></tr>
                @empty<tr><td colspan="3"><div class="admin-empty"><strong>No earnings recorded</strong></div></td></tr>@endforelse</tbody>
            </table></div>
            <p class="admin-widget-footnote">These balances are held accounting entries, not available withdrawal funds.</p>
        </section>
        <section class="admin-widget" aria-labelledby="admin-accounting-payouts">
            <div class="admin-widget-heading"><div><span class="admin-widget-kicker">PAYOUT AUDIT</span><h2 id="admin-accounting-payouts">Payout record ledger</h2></div></div>
            <div class="table-scroll"><table class="admin-table">
                <thead><tr><th scope="col">Instructor</th><th scope="col">Amount</th><th scope="col">Status</th></tr></thead>
                <tbody>@forelse($payouts as $payout)
                    <tr><td>{{ $payout->instructor_id }}</td>
                        <td>{{ \App\Helpers\Localization::minorMoney((int) $payout->amount_minor, $payout->currency_code, \App\Helpers\Localization::resolve()) }}</td>
                        <td><span class="admin-status">{{ ucfirst(str_replace('_', ' ', $payout->status)) }}</span></td></tr>
                @empty<tr><td colspan="3"><div class="admin-empty"><strong>No payout records</strong><p>Disbursements are disabled.</p></div></td></tr>@endforelse</tbody>
            </table></div>
            <p class="admin-widget-footnote">Payout records are for reconciliation only. No transfers are initiated here.</p>
        </section>
    </div>
</section>
@endsection
