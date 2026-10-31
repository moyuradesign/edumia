@extends('layouts.panel')
@section('title', 'My earnings')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">INSTRUCTOR FINANCE</span><h1>My earnings</h1>
<p>All values are historical accounting entries. Withdrawals and transfers are disabled until financial verification and payout approvals are implemented.</p></div></div>
<section class="panel-card"><h2>Held balance</h2>
@forelse($balances as $balance)
<p><strong>{{ \App\Helpers\Localization::minorMoney((int) ($balance->net_held_minor), $balance->currency_code, \App\Helpers\Localization::resolve()) }}</strong> — held, not withdrawable</p>
@empty<p>No verified course or product earnings yet.</p>@endforelse</section>
<section class="panel-card"><h2>Earnings ledger</h2><div class="table-scroll"><table>
<thead><tr><th>Date</th><th>Order ID</th><th>Type</th><th>Gross</th><th>Platform share</th><th>Your share</th><th>Status</th></tr></thead>
<tbody>@forelse($entries as $entry)
<tr><td>{{ \App\Helpers\Localization::date(\Carbon\CarbonImmutable::parse($entry->created_at, 'UTC'), \App\Helpers\Localization::resolve(), true) }}</td><td>{{ $entry->order_id }}</td>
<td>{{ ucfirst($entry->entry_type) }}</td>
<td>{{ \App\Helpers\Localization::minorMoney((int) ($entry->gross_minor), $entry->currency_code, \App\Helpers\Localization::resolve()) }}</td>
<td>{{ \App\Helpers\Localization::minorMoney((int) ($entry->platform_minor), $entry->currency_code, \App\Helpers\Localization::resolve()) }}</td>
<td>{{ \App\Helpers\Localization::minorMoney((int) ($entry->instructor_minor), $entry->currency_code, \App\Helpers\Localization::resolve()) }}</td>
<td>{{ $entry->status }}</td></tr>
@empty<tr><td colspan="7">No financial entries yet.</td></tr>@endforelse</tbody></table></div>
{{ $entries->links() }}</section>
<section class="panel-card"><h2>Payout record history</h2><div class="table-scroll"><table>
<thead><tr><th>Date</th><th>Amount</th><th>Status</th></tr></thead><tbody>
@forelse($payouts as $payout)
<tr><td>{{ \App\Helpers\Localization::date(\Carbon\CarbonImmutable::parse($payout->created_at, 'UTC'), \App\Helpers\Localization::resolve(), true) }}</td><td>{{ \App\Helpers\Localization::minorMoney((int) ($payout->amount_minor), $payout->currency_code, \App\Helpers\Localization::resolve()) }}</td>
<td>{{ $payout->status }}</td></tr>
@empty<tr><td colspan="3">Payout processing is not enabled.</td></tr>@endforelse</tbody></table></div></section>
@endsection
