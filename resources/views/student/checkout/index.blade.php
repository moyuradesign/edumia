@extends('layouts.panel')
@section('title', 'My orders')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">COMMERCE</span><h1>My orders</h1>
<p>Review orders for courses, ebooks and bootcamps. Payment options depend on the site operator’s gateway configuration. Pending orders never grant paid access.</p></div>
<a class="btn btn-quiet" href="{{ route('courses.index') }}">Browse courses</a></div>
<section class="panel-card">
<div class="table-scroll"><table>
<thead><tr><th>Order</th><th>Item</th><th>Amount</th><th>Status</th><th>Created</th><th></th></tr></thead>
<tbody>
@forelse($orders as $order)
<tr>
<td>{{ substr($order->reference, 0, 8) }}</td>
<td>{{ $order->items->pluck('title')->implode(', ') }}</td>
<td>{{ $order->currency_code }} {{ $order->total }}</td>
<td>{{ $order->status === 'pending' && $order->expires_at?->isPast() ? 'Quote expired' : ucwords(str_replace('_', ' ', $order->status)) }}</td>
<td>{{ $order->created_at->format('Y-m-d') }}</td>
<td><a href="{{ route('checkout.orders.show', $order) }}">View</a></td>
</tr>
@empty<tr><td colspan="6">You have not created an order yet.</td></tr>@endforelse
</tbody></table></div>
{{ $orders->links() }}
</section>
@endsection
