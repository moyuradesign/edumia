<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Models\Order;
use App\Models\Bootcamp;
use App\Models\BootcampPurchase;
use App\Services\Commerce\StripeCheckoutGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

final class PaymentController extends Controller
{
    public function start(Request $request, Order $order, StripeCheckoutGateway $gateway): RedirectResponse
    {
        abort_unless((int) $order->user_id === (int) $request->user()->getKey(), 404);
        $gateway->assertConfigured();

        $url = DB::transaction(function () use ($order, $gateway): string {
            $order = Order::query()->lockForUpdate()->findOrFail($order->getKey());
            abort_unless(in_array($order->status, ['pending', 'awaiting_payment'], true), 409);
            abort_unless($order->expires_at?->isFuture(), 409, 'Order has expired.');

            if ($order->status === 'awaiting_payment') {
                abort_unless($order->payment_gateway === 'stripe'
                    && is_string($order->gateway_checkout_url)
                    && $gateway->checkoutUrlSafe($order->gateway_checkout_url), 409);
                return $order->gateway_checkout_url;
            }

            // Paid bootcamp seats are reserved by active, server-created
            // Checkout sessions, not by a visitor clicking a listing.
            $item = $order->items()->firstOrFail();
            if ($item->bootcamp_id !== null) {
                $bootcamp = Bootcamp::query()->lockForUpdate()->findOrFail($item->bootcamp_id);
                $confirmed = BootcampPurchase::query()
                    ->where('bootcamp_id', $bootcamp->getKey())
                    ->where('status', 'completed')->count();
                $reserved = Order::query()
                    ->where('status', 'awaiting_payment')
                    ->where('expires_at', '>', now())
                    ->whereHas('items', fn ($q) => $q->where('bootcamp_id', $bootcamp->getKey()))
                    ->count();
                abort_unless($bootcamp->capacity === null
                    || $confirmed + $reserved < $bootcamp->capacity, 409,
                    'All seats are currently reserved. Try another cohort.');
            }

            $session = $gateway->createSession($order);
            $order->forceFill([
                'status' => 'awaiting_payment',
                'payment_gateway' => 'stripe',
                'gateway_session_id' => $session['id'],
                'gateway_checkout_url' => $session['url'],
            ])->save();

            return $session['url'];
        }, 3);

        return redirect()->away($url);
    }
}
