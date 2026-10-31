@extends('layouts.panel')
@section('title', 'Commerce accounting')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">FINANCE & RECONCILIATION</span><h1>Commerce accounting</h1>
<p>Read-only internal reconciliation. No automatic payout, refund initiation, or currency conversion is enabled.</p></div></div>
<form method="post" action="{{ route('admin.commerce.accounting.reconcile') }}">@csrf
<button class="btn btn-primary" type="submit">Scan local financial records</button></form>
<section class="panel-card"><h2>Review exceptions</h2><div class="report-grid">
@foreach($issues as $key => $count)<div class="builder-section"><p>{{ ucwords(str_replace('_',' ',$key)) }}</p><h2>{{ $count }}</h2></div>@endforeach
</div><p>These checks compare local records only; provider settlement and bank statements require a separate reconciliation process.</p></section>
<section class="panel-card"><h2>Invoice records</h2><div class="table-scroll"><table>
<thead><tr><th>Number</th><th>Customer</th><th>Gross</th><th>Refunded</th><th>Status</th></tr></thead>
<tbody>@forelse($invoices as $invoice)<tr><td>{{ $invoice->number }}</td><td>{{ $invoice->customer_email }}</td>
<td>{{ \App\Helpers\Localization::minorMoney((int) ($invoice->total_minor), $invoice->currency_code, \App\Helpers\Localization::resolve()) }}</td>
<td>{{ \App\Helpers\Localization::minorMoney((int) ($invoice->refunded_minor), $invoice->currency_code, \App\Helpers\Localization::resolve()) }}</td><td>{{ $invoice->status }}</td></tr>
@empty<tr><td colspan="5">No verified paid orders yet.</td></tr>@endforelse</tbody></table></div>{{ $invoices->links() }}</section>
<section class="panel-card"><h2>Held instructor earnings</h2><div class="table-scroll"><table>
<thead><tr><th>Instructor ID</th><th>Currency</th><th>Net held</th></tr></thead>
<tbody>@forelse($earnings as $earning)<tr><td>{{ $earning->instructor_id }}</td>
<td>{{ $earning->currency_code }}</td><td>{{ \App\Helpers\Localization::minorMoney((int) ($earning->net_held_minor), $earning->currency_code, \App\Helpers\Localization::resolve()) }}</td></tr>
@empty<tr><td colspan="3">No earnings recorded.</td></tr>@endforelse</tbody></table></div>
<p>Balances are held accounting entries only. They are not available for withdrawal or transfer.</p></section>
<section class="panel-card"><h2>Payout record ledger</h2><div class="table-scroll"><table>
<thead><tr><th>Instructor</th><th>Amount</th><th>Status</th></tr></thead>
<tbody>@forelse($payouts as $payout)<tr><td>{{ $payout->instructor_id }}</td>
<td>{{ \App\Helpers\Localization::minorMoney((int) ($payout->amount_minor), $payout->currency_code, \App\Helpers\Localization::resolve()) }}</td><td>{{ $payout->status }}</td></tr>
@empty<tr><td colspan="3">No payout records. Disbursements are disabled.</td></tr>@endforelse</tbody></table></div></section>
@endsection
