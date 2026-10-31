@extends('layouts.panel')
@section('title', 'Commerce orders')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">COMMERCE OPERATIONS</span><h1>Orders</h1>
<p>Read-only pending order ledger. Payments, settlement, invoices and refunds are not enabled; these orders are not revenue.</p></div></div>
<section class="panel-card">
<div class="table-scroll"><table>
<thead><tr><th>Reference</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Created</th></tr></thead>
<tbody>@forelse($orders as $order)
<tr><td>{{ substr($order->reference, 0, 8) }}</td><td>{{ $order->user?->email ?? 'Removed account' }}</td>
<td>{{ $order->items->pluck('title')->implode(', ') }}</td><td>{{ $order->currency_code }} {{ $order->total }}</td>
<td>{{ $order->status === 'pending' && $order->expires_at?->isPast() ? 'Quote expired' : ucfirst($order->status) }}</td>
<td>{{ $order->created_at->format('Y-m-d') }}</td></tr>
@empty<tr><td colspan="6">No orders have been prepared.</td></tr>@endforelse
</tbody></table></div>
{{ $orders->links() }}
</section>
@endsection
