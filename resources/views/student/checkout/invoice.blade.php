@extends('layouts.panel')
@section('title', 'Payment invoice')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">COMMERCE</span><h1>Payment invoice</h1>
<p>{{ $invoice->number }} · Issued {{ $invoice->issued_at }}</p></div>
<a class="btn btn-quiet" href="{{ route('checkout.orders.show', $order) }}">Order details</a></div>
<section class="panel-card">
<p><strong>Customer:</strong> {{ $invoice->customer_name }} ({{ $invoice->customer_email }})</p>
<p><strong>Order:</strong> {{ $order->reference }}</p>
<div class="table-scroll"><table>
<thead><tr><th>Item</th><th>Quantity</th><th>Amount</th></tr></thead>
<tbody>@foreach($order->items as $item)
<tr><td>{{ $item->title }}</td><td>{{ $item->quantity }}</td>
<td>{{ $order->currency_code }} {{ $item->line_total }}</td></tr>
@endforeach</tbody></table></div>
<p><strong>Total:</strong> {{ $invoice->currency_code }} {{ number_format($invoice->total_minor / 100, 2) }}</p>
<p><strong>Refunded:</strong> {{ $invoice->currency_code }} {{ number_format($invoice->refunded_minor / 100, 2) }}</p>
<p><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</p>
<p><strong>Tax:</strong> Not assessed by Edumia. This document is a payment record, not a tax-compliant invoice until merchant and jurisdictional configuration are reviewed.</p>
</section>
@endsection
