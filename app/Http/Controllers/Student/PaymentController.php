<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Models\Order;
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
