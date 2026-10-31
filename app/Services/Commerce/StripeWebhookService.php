<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

final class StripeWebhookService
{
    public function handle(string $payload, ?string $signature, StripeCheckoutGateway $gateway,
        StripeSignatureVerifier $verifier, PaymentFulfillmentService $fulfillment): void
    {
        $gateway->assertConfigured();
        abort_unless($verifier->valid($payload, $signature,
            (string) config('payments.stripe.webhook_secret'), now()->timestamp), 400, 'Invalid webhook signature.');

        $event = json_decode($payload, true);
        abort_unless(is_array($event) && is_string($event['id'] ?? null)
            && preg_match('/^evt_[A-Za-z0-9_]{6,180}$/D', $event['id'])
            && is_string($event['type'] ?? null), 400);

        $type = $event['type'];
        if (! in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded', 'checkout.session.expired'], true)) {
            return;
        }

        $id = $event['data']['object']['id'] ?? null;
        abort_unless(is_string($id) && str_starts_with($id, 'cs_'), 400);

        if (DB::table('payment_webhook_events')->where('gateway', 'stripe')
            ->where('event_id', $event['id'])->exists()) {
            return;
        }

        $order = Order::query()->where('payment_gateway', 'stripe')
            ->where('gateway_session_id', $id)->first();

        // A signed event for another product/account is not our order.
        if (! $order) {
            return;
        }

        // Retrieve authenticated state from Stripe rather than trusting even
        // a signed event payload for totals, status or entitlement identity.
        $session = $gateway->retrieveSession($id);
        if ($type === 'checkout.session.expired') {
            $fulfillment->expire($order, $session, $event['id'], $gateway);
            return;
        }

        $fulfillment->fulfill($order, $session, $event['id'], $type, $gateway);
    }
}
