@extends('layouts.panel')
@section('title', 'Payment invoice')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">COMMERCE</span><h1>Payment invoice</h1>
<p>{{ $invoice->number }} · Issued {{ \App\Helpers\Localization::date(\Carbon\CarbonImmutable::parse($invoice->issued_at, 'UTC'), \App\Helpers\Localization::resolve(), true) }}</p></div>
<a class="btn btn-quiet" href="{{ route('checkout.orders.show', $order) }}">Order details</a></div>
<section class="panel-card">
<p><strong>Customer:</strong> {{ $invoice->customer_name }} ({{ $invoice->customer_email }})</p>
<p><strong>Order:</strong> {{ $order->reference }}</p>
<div class="table-scroll"><table>
<thead><tr><th>Item</th><th>Quantity</th><th>Amount</th></tr></thead>
<tbody>@foreach($order->items as $item)
<tr><td>{{ $item->title }}</td><td>{{ $item->quantity }}</td>
<td>{{ \App\Helpers\Localization::money((string) $item->line_total, $order->currency_code, \App\Helpers\Localization::resolve()) }}</td></tr>
@endforeach</tbody></table></div>
<p><strong>Total:</strong> {{ \App\Helpers\Localization::minorMoney((int) ($invoice->total_minor), $invoice->currency_code, \App\Helpers\Localization::resolve()) }}</p>
<p><strong>Refunded:</strong> {{ \App\Helpers\Localization::minorMoney((int) ($invoice->refunded_minor), $invoice->currency_code, \App\Helpers\Localization::resolve()) }}</p>
<p><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</p>
<p><strong>Tax:</strong> Not assessed by Edumia. This document is a payment record, not a tax-compliant invoice until merchant and jurisdictional configuration are reviewed.</p>
</section>
@endsection
