<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\AuditEvent;
use App\Models\Certificate;
use App\Models\BootcampPurchase;
use App\Models\EbookPurchase;
use App\Models\Enrollment;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Stripe events are signature-checked upstream. Changes depend on a fresh
 * authenticated API response, never on webhook payload fields alone.
 */
final class StripeAdjustmentService
{
    public function handle(array $event, StripeCheckoutGateway $gateway, CommerceAccountingService $accounting): void
    {
        $type = $event['type'];
        $id = $event['id'];
        $objectId = $event['data']['object']['id'] ?? null;
        abort_unless(is_string($objectId) && preg_match('/^(ch_|dp_)[A-Za-z0-9_]{6,180}$/D', $objectId), 400);

        $dispute = null;
        if (in_array($type, ['charge.dispute.created', 'charge.dispute.closed'], true)) {
            abort_unless(str_starts_with($objectId, 'dp_'), 400);
            $dispute = $this->stripeGet('/v1/disputes/'.$objectId);
            abort_unless(($dispute['id'] ?? null) === $objectId
                && is_string($dispute['charge'] ?? null), 422);
            $chargeId = $dispute['charge'];
        } else {
            abort_unless($type === 'charge.refunded' && str_starts_with($objectId, 'ch_'), 400);
            $chargeId = $objectId;
        }
        abort_unless(preg_match('/^ch_[A-Za-z0-9_]{6,180}$/D', $chargeId), 422);
        $charge = $this->stripeGet('/v1/charges/'.$chargeId);
        $intent = $charge['payment_intent'] ?? null;
        $order = is_string($intent) ? Order::query()
            ->where('payment_gateway', 'stripe')
            ->where('gateway_payment_reference', $intent)->first() : null;

        if ($order === null) {
            // A valid Stripe event for another merchant resource isn't Edumia's.
            return;
        }

        DB::transaction(function () use ($order, $id, $type, $chargeId, $charge, $dispute, $accounting): void {
            $order = Order::query()->lockForUpdate()->findOrFail($order->getKey());
            if (DB::table('payment_webhook_events')->where('gateway', 'stripe')
                ->where('event_id', $id)->exists()) {
                return;
            }
            $total = $accounting->minor($order->total);
            abort_unless($order->paid_at !== null
                && in_array($order->status, ['paid', 'partially_refunded', 'refunded', 'disputed'], true)
                && ($charge['id'] ?? null) === $chargeId
                && ($charge['payment_intent'] ?? null) === $order->gateway_payment_reference
                && ($charge['currency'] ?? null) === strtolower($order->currency_code)
                && ($charge['amount'] ?? null) === $total
                && ($charge['paid'] ?? null) === true
                && ($charge['livemode'] ?? null) === (config('payments.stripe.mode') === 'live'),
                422, 'Payment adjustment did not match the paid order.');

            if ($type === 'charge.refunded') {
                $refunded = $charge['amount_refunded'] ?? null;
                abort_unless(is_int($refunded) && $refunded > 0 && $refunded <= $total, 422);
                $accounting->recordRefund($order, $chargeId, $refunded);
                $full = $refunded === $total;
                if ($full) {
                    $this->revoke($order, 'refunded');
                }
                $order->forceFill(['status' => $full ? 'refunded'
                    : ($order->status === 'disputed' ? 'disputed' : 'partially_refunded')])->save();
            } else {
                abort_unless(($dispute['charge'] ?? null) === $chargeId
                    && ($dispute['currency'] ?? null) === strtolower($order->currency_code)
                    && is_int($dispute['amount'] ?? null)
                    && $dispute['amount'] > 0 && $dispute['amount'] <= $total
                    && ($type !== 'charge.dispute.closed'
                        || in_array($dispute['status'] ?? null, ['won', 'lost', 'warning_closed'], true)), 422);
                DB::table('commerce_disputes')->updateOrInsert(
                    ['gateway_dispute_id' => $dispute['id']],
                    ['order_id' => $order->getKey(), 'status' => (string) ($dispute['status'] ?? 'needs_response'),
                        'amount_minor' => $dispute['amount'], 'updated_at' => now(), 'created_at' => now()]
                );
                // Delivery can be out of order: a closed dispute may arrive
                // before the created event. Freeze access until case review.
                // Never automatically re-enable access even when Stripe says "won".
                $this->revoke($order, 'disputed');
                if ($order->status !== 'refunded') {
                    $order->forceFill(['status' => 'disputed'])->save();
                }
            }

            DB::table('payment_webhook_events')->insert([
                'gateway' => 'stripe', 'event_id' => $id, 'order_id' => $order->getKey(),
                'event_type' => $type, 'processed_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            AuditEvent::query()->create([
                'actor_id' => null, 'subject_type' => Order::class, 'subject_id' => $order->getKey(),
                'action' => match ($type) {
                    'charge.refunded' => 'commerce.refund_verified',
                    'charge.dispute.closed' => 'commerce.dispute_closed_verified',
                    default => 'commerce.dispute_verified',
                },
                'new_values' => ['status' => $order->status, 'gateway' => 'stripe'],
            ]);
        }, 3);
    }

    private function stripeGet(string $path): array
    {
        $response = Http::withToken((string) config('payments.stripe.secret_key'))
            ->timeout(15)->get('https://api.stripe.com'.$path);
        abort_unless($response->successful() && is_array($response->json()), 502,
            'Provider verification is unavailable.');
        return $response->json();
    }

    private function revoke(Order $order, string $reason): void
    {
        $item = $order->items()->firstOrFail();
        if ($item->course_id !== null) {
            $linked = Enrollment::query()->where('course_id', $item->course_id)
                ->where('user_id', $order->user_id)->where('payment_order_id', $order->getKey())
                ->lockForUpdate()->first();
            if ($linked && in_array($linked->status, ['active', 'completed'], true)) {
                $linked->forceFill(['status' => 'revoked'])->save();
                Certificate::query()->where('course_id', $item->course_id)
                    ->where('user_id', $order->user_id)->whereNull('revoked_at')
                    ->update(['revoked_at' => now()]);
            }
        } elseif ($item->ebook_id !== null) {
            EbookPurchase::query()->where('ebook_id', $item->ebook_id)
                ->where('user_id', $order->user_id)
                ->where('payment_reference', $order->gateway_payment_reference)
                ->where('status', 'completed')->update(['status' => $reason]);
        } elseif ($item->bootcamp_id !== null) {
            BootcampPurchase::query()->where('bootcamp_id', $item->bootcamp_id)
                ->where('user_id', $order->user_id)
                ->where('payment_reference', $order->gateway_payment_reference)
                ->where('status', 'completed')->update(['status' => $reason]);
        }
    }
}
