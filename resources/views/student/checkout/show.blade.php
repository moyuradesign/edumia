@extends('layouts.panel')
@section('title', 'Order review')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">SECURE CHECKOUT</span><h1>Order review</h1>
<p>Reference: {{ $order->reference }}</p></div>
<a class="btn btn-quiet" href="{{ route('checkout.orders.index') }}">My orders</a></div>
<section class="panel-card">
<h2>Price summary</h2>
<div class="table-scroll"><table>
<thead><tr><th>Item</th><th>Quantity</th><th>Unit price</th><th>Total</th></tr></thead>
<tbody>@foreach($order->items as $item)
<tr><td>{{ $item->title }}</td><td>{{ $item->quantity }}</td><td>{{ \App\Helpers\Localization::money((string) $item->unit_price, $order->currency_code, \App\Helpers\Localization::resolve()) }}</td>
<td>{{ \App\Helpers\Localization::money((string) $item->line_total, $order->currency_code, \App\Helpers\Localization::resolve()) }}</td></tr>
@endforeach</tbody></table></div>
<p><strong>Total: {{ \App\Helpers\Localization::money((string) $order->total, $order->currency_code, \App\Helpers\Localization::resolve()) }}</strong></p>
<p>Status: <strong>{{ $order->status === 'pending' && $order->expires_at?->isPast() ? 'Quote expired' : ucfirst($order->status) }}</strong></p>
@if($order->status === 'pending' && $order->expires_at?->isFuture())
<p>This quote expires at {{ \App\Helpers\Localization::date($order->expires_at, \App\Helpers\Localization::resolve(), true) }}. Access is granted only after the payment provider confirms a successful transaction.</p>
@if(app(\App\Services\Commerce\StripeCheckoutGateway::class)->configured())
<form method="post" action="{{ route('checkout.orders.pay', $order) }}">@csrf
<button class="btn btn-primary" type="submit">Pay securely with Stripe</button></form>
@else<p>No payment gateway is connected. Creating an order neither charges you nor enrolls you.</p>@endif
<form method="post" action="{{ route('checkout.orders.cancel', $order) }}">@csrf @method('PATCH')
<button class="btn btn-quiet" type="submit">Cancel this order</button></form>
@elseif($order->status === 'awaiting_payment')
<p>Payment is awaiting verified confirmation. A successful browser return alone does not grant access.</p>
@if(app(\App\Services\Commerce\StripeCheckoutGateway::class)->configured() && $order->expires_at?->isFuture())
<form method="post" action="{{ route('checkout.orders.pay', $order) }}">@csrf
<button class="btn btn-primary" type="submit">Return to Stripe Checkout</button></form>
@elseif($order->expires_at?->isPast())
<p>The hosted payment session may have expired. Do not retry payment until its status has been reconciled; contact support if you were charged.</p>
@endif
@elseif(in_array($order->status, ['paid', 'partially_refunded', 'refunded', 'disputed'], true))
<p>Payment status: {{ ucfirst(str_replace('_', ' ', $order->status)) }}. Refunds or disputes may suspend access.</p>
<a class="btn btn-quiet" href="{{ route('checkout.orders.invoice', $order) }}">View payment invoice</a>
@else
<p>This order cannot be paid here. Browse courses to request a new quote, if applicable.</p>
@endif
@if(in_array($order->status, ['paid', 'partially_refunded', 'refunded', 'disputed'], true))
<div class="builder-section"><h2>Refund support</h2>
@if($refundReview)<p>Request status: <strong>{{ ucfirst($refundReview->status) }}</strong></p>
@if($refundReview->review_note)<p>Staff response: {{ $refundReview->review_note }}</p>@endif
@elseif(in_array($order->status, ['paid', 'partially_refunded'], true))
<p>Request a refund review. Submitting a request does not automatically issue a refund.</p>
<form class="stack-form" method="post" action="{{ route('checkout.refunds.store', $order) }}">@csrf
<label>Reason<textarea name="reason" required minlength="12" maxlength="2000" rows="4">{{ old('reason') }}</textarea></label>
<button class="btn btn-secondary" type="submit">Request refund review</button>
</form>@endif
</div>@endif
</section>
@endsection
