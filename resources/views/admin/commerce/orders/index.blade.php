@extends('layouts.panel')
@section('title', 'Commerce orders')
@section('content')
<section class="admin-order-workspace" aria-labelledby="admin-orders-heading">
    <div class="admin-welcome">
        <div>
            <span class="admin-eyebrow">COMMERCE OPERATIONS</span>
            <h1 id="admin-orders-heading">Order management</h1>
            <p>Review purchases and their verification states. Payment confirmation remains server-controlled.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('admin.commerce.refunds.index') }}">Refund reviews</a>
            <a class="admin-action-primary" href="{{ route('admin.commerce.accounting.index') }}">Accounting overview</a>
        </div>
    </div>

    <div class="admin-stats" aria-label="Order statistics">
        <div class="admin-stat">
            <span class="admin-stat-top"><span>Total orders</span><span class="admin-stat-icon admin-stat-icon--blue" aria-hidden="true">▤</span></span>
            <strong>{{ number_format($totalOrders) }}</strong><small>All order records</small>
        </div>
        <div class="admin-stat">
            <span class="admin-stat-top"><span>Pending payments</span><span class="admin-stat-icon admin-stat-icon--orange" aria-hidden="true">◷</span></span>
            <strong>{{ number_format($pendingOrders) }}</strong><small>Unexpired and unverified</small>
        </div>
        <div class="admin-stat">
            <span class="admin-stat-top"><span>Verified paid orders</span><span class="admin-stat-icon admin-stat-icon--mint" aria-hidden="true">✓</span></span>
            <strong>{{ number_format($verifiedOrders) }}</strong><small>Paid status with payment verification</small>
        </div>
        <div class="admin-stat">
            <span class="admin-stat-top"><span>Refunds & disputes</span><span class="admin-stat-icon admin-stat-icon--violet" aria-hidden="true">↩</span></span>
            <strong>{{ number_format($exceptionOrders) }}</strong><small>Partial, full, or disputed orders</small>
        </div>
    </div>

    <div class="admin-widget admin-order-list">
        <div class="admin-widget-heading">
            <div>
                <span class="admin-widget-kicker">ORDER DIRECTORY</span>
                <h2>All transactions</h2>
                <p>Showing {{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} of {{ number_format($orders->total()) }} matching orders.</p>
            </div>
        </div>

        <form class="admin-course-filters admin-order-filters" action="{{ route('admin.commerce.orders.index') }}" method="get">
            <div>
                <label class="sr-only" for="admin-order-query">Search order reference or customer</label>
                <input id="admin-order-query" type="search" name="q" value="{{ $search }}" maxlength="100"
                       placeholder="Reference, customer name or email">
            </div>
            <div>
                <label class="sr-only" for="admin-order-status">Filter order status</label>
                <select id="admin-order-status" name="status">
                    @foreach([
                        'all' => 'All statuses',
                        'pending' => 'Pending payment',
                        'paid' => 'Verified paid',
                        'partially_refunded' => 'Partially refunded',
                        'refunded' => 'Refunded',
                        'disputed' => 'Disputed',
                        'expired' => 'Expired quotes',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="admin-action-primary" type="submit">Apply filters</button>
            @if($search !== '' || $status !== 'all')
                <a class="admin-action-secondary" href="{{ route('admin.commerce.orders.index') }}">Clear</a>
            @endif
        </form>

        <div class="table-scroll">
            <table class="admin-table admin-order-table">
                <thead><tr><th scope="col">Reference</th><th scope="col">Customer</th><th scope="col">Items</th><th scope="col">Total</th><th scope="col">Payment status</th><th scope="col">Placed</th></tr></thead>
                <tbody>
                @forelse($orders as $order)
                    @php
                        $expired = $order->status === 'pending' && $order->paid_at === null && $order->expires_at?->isPast();
                        $verifiedPaid = $order->status === 'paid' && $order->paid_at !== null;
                        $label = $expired ? 'Quote expired' : ($order->status === 'paid' && !$verifiedPaid ? 'Payment unverified' : ucfirst(str_replace('_', ' ', $order->status)));
                        $tone = $verifiedPaid ? 'success' : (in_array($order->status, ['disputed', 'refunded', 'partially_refunded'], true) ? 'danger' : 'neutral');
                    @endphp
                    <tr>
                        <td>
                            <strong class="admin-order-reference" title="{{ $order->reference }}">{{ $order->reference }}</strong>
                            <small>{{ $order->payment_gateway ? strtoupper($order->payment_gateway) : 'Gateway not selected' }}</small>
                        </td>
                        <td>
                            <strong>{{ $order->user?->name ?? 'Removed account' }}</strong>
                            <small>{{ $order->user?->email ?? 'Customer unavailable' }}</small>
                        </td>
                        <td>
                            <span class="admin-order-items">
                                {{ $order->items->pluck('title')->filter()->take(2)->implode(', ') ?: 'No items' }}
                                @if($order->items->count() > 2)<small>+{{ $order->items->count() - 2 }} more</small>@endif
                            </span>
                        </td>
                        <td><strong>{{ $order->currency_code }} {{ number_format((float) $order->total, 2) }}</strong></td>
                        <td>
                            <span class="admin-status admin-status--{{ $tone }}">{{ $label }}</span>
                            @if($order->paid_at)
                                <small>Verified {{ $order->paid_at->format('M j, Y') }}</small>
                            @else
                                <small>No verified payment</small>
                            @endif
                        </td>
                        <td><time datetime="{{ $order->created_at->toDateString() }}">{{ $order->created_at->format('M j, Y') }}</time></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="admin-empty"><span aria-hidden="true">⌕</span><strong>No matching orders</strong><p>Try a different reference, customer, or payment status.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-course-pagination">{{ $orders->onEachSide(1)->links() }}</div>
        <p class="admin-widget-footnote">Order totals are shown in their original currencies. No cross-currency revenue estimate or manual payment confirmation is performed here.</p>
    </div>
</section>
@endsection
