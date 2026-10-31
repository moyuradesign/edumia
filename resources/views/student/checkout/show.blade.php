@extends('layouts.panel')
@section('title', 'Order review')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">COURSE CHECKOUT</span><h1>Order review</h1>
<p>Reference: {{ $order->reference }}</p></div>
<a class="btn btn-quiet" href="{{ route('checkout.orders.index') }}">My orders</a></div>
<section class="panel-card">
<h2>Price summary</h2>
<div class="table-scroll"><table>
<thead><tr><th>Course</th><th>Quantity</th><th>Unit price</th><th>Total</th></tr></thead>
<tbody>@foreach($order->items as $item)
<tr><td>{{ $item->title }}</td><td>{{ $item->quantity }}</td><td>{{ $order->currency_code }} {{ $item->unit_price }}</td>
<td>{{ $order->currency_code }} {{ $item->line_total }}</td></tr>
@endforeach</tbody></table></div>
<p><strong>Total: {{ $order->currency_code }} {{ $order->total }}</strong></p>
<p>Status: <strong>{{ $order->status === 'pending' && $order->expires_at?->isPast() ? 'Quote expired' : ucfirst($order->status) }}</strong></p>
@if($order->status === 'pending' && $order->expires_at?->isFuture())
<p>This quote expires at {{ $order->expires_at->format('Y-m-d H:i T') }}. No payment gateway is connected. Creating an order neither charges you nor enrolls you.</p>
<form method="post" action="{{ route('checkout.orders.cancel', $order) }}">@csrf @method('PATCH')
<button class="btn btn-quiet" type="submit">Cancel this order</button></form>
@else
<p>This order cannot be paid here. Browse courses to request a new quote, if applicable.</p>
@endif
</section>
@endsection
