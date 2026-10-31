@extends('layouts.panel')
@section('title', 'My earnings')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">INSTRUCTOR FINANCE</span><h1>My earnings</h1>
<p>All values are historical accounting entries. Withdrawals and transfers are disabled until financial verification and payout approvals are implemented.</p></div></div>
<section class="panel-card"><h2>Held balance</h2>
@forelse($balances as $balance)
<p><strong>{{ $balance->currency_code }} {{ number_format($balance->net_held_minor / 100, 2) }}</strong> — held, not withdrawable</p>
@empty<p>No verified course or product earnings yet.</p>@endforelse</section>
<section class="panel-card"><h2>Earnings ledger</h2><div class="table-scroll"><table>
<thead><tr><th>Date</th><th>Order ID</th><th>Type</th><th>Gross</th><th>Platform share</th><th>Your share</th><th>Status</th></tr></thead>
<tbody>@forelse($entries as $entry)
<tr><td>{{ $entry->created_at }}</td><td>{{ $entry->order_id }}</td>
<td>{{ ucfirst($entry->entry_type) }}</td>
<td>{{ $entry->currency_code }} {{ number_format($entry->gross_minor / 100, 2) }}</td>
<td>{{ number_format($entry->platform_minor / 100, 2) }}</td>
<td>{{ number_format($entry->instructor_minor / 100, 2) }}</td>
<td>{{ $entry->status }}</td></tr>
@empty<tr><td colspan="7">No financial entries yet.</td></tr>@endforelse</tbody></table></div>
{{ $entries->links() }}</section>
<section class="panel-card"><h2>Payout record history</h2><div class="table-scroll"><table>
<thead><tr><th>Date</th><th>Amount</th><th>Status</th></tr></thead><tbody>
@forelse($payouts as $payout)
<tr><td>{{ $payout->created_at }}</td><td>{{ $payout->currency_code }} {{ number_format($payout->amount_minor / 100, 2) }}</td>
<td>{{ $payout->status }}</td></tr>
@empty<tr><td colspan="3">Payout processing is not enabled.</td></tr>@endforelse</tbody></table></div></section>
@endsection
