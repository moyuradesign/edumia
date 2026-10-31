<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\AuditEvent;
use App\Models\Bootcamp;
use App\Models\BootcampPurchase;
use App\Models\EbookPurchase;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class PaymentFulfillmentService
{
    public function fulfill(Order $selected, array $session, string $eventId, string $eventType,
        StripeCheckoutGateway $gateway): void
    {
        DB::transaction(function () use ($selected, $session, $eventId, $eventType, $gateway): void {
            $order = Order::query()->lockForUpdate()->findOrFail($selected->getKey());

            if (DB::table('payment_webhook_events')->where('gateway', 'stripe')
                ->where('event_id', $eventId)->exists()) {
                return;
            }

            $paymentId = $session['payment_intent'] ?? null;
            abort_unless($order->payment_gateway === 'stripe'
                && $order->gateway_session_id === ($session['id'] ?? null)
                && ($session['client_reference_id'] ?? null) === $order->reference
                && ($session['metadata']['order_reference'] ?? null) === $order->reference
                && ($session['payment_status'] ?? null) === 'paid'
                && ($session['status'] ?? null) === 'complete'
                && ($session['currency'] ?? null) === strtolower($order->currency_code)
                && ($session['amount_total'] ?? null) === $gateway->cents($order->total)
                && ($session['livemode'] ?? null) === (config('payments.stripe.mode') === 'live')
                && is_string($paymentId) && str_starts_with($paymentId, 'pi_'),
                422, 'Verified payment does not match the order.');

            if ($order->status === 'paid') {
                abort_unless($order->gateway_payment_reference === $paymentId, 409);
                $this->recordEvent($order, $eventId, $eventType);
                return;
            }

            abort_unless($order->status === 'awaiting_payment', 409);
            abort_unless(! Order::query()->where('gateway_payment_reference', $paymentId)->exists(), 409);
            $user = User::query()->lockForUpdate()->findOrFail($order->user_id);
            $items = $order->items()->get();
            abort_unless($items->count() === 1, 422, 'Only single-product purchases are supported.');
            $item = $items[0];
            $types = (int) ($item->course_id !== null) + (int) ($item->ebook_id !== null)
                + (int) ($item->bootcamp_id !== null);
            abort_unless($types === 1, 422, 'The order product is unavailable.');

            if ($item->course_id !== null) {
                $existing = Enrollment::query()->where('user_id', $user->getKey())
                    ->where('course_id', $item->course_id)->lockForUpdate()->first();

                if ($existing === null) {
                    Enrollment::query()->create([
                        'course_id' => $item->course_id, 'user_id' => $user->getKey(),
                        'enrolled_at' => now(), 'progress_percent' => 0, 'status' => 'active',
                    ]);
                } else {
                    abort_unless(in_array($existing->status, ['active', 'completed'], true), 409,
                        'Enrollment requires reconciliation.');
                }
            } elseif ($item->ebook_id !== null) {
                $existing = EbookPurchase::query()->where('user_id', $user->getKey())
                    ->where('ebook_id', $item->ebook_id)->lockForUpdate()->first();
                abort_unless($existing === null || $existing->status === 'completed', 409);
                if ($existing === null) {
                    EbookPurchase::query()->create([
                        'user_id' => $user->getKey(), 'ebook_id' => $item->ebook_id,
                        'amount' => $order->total, 'currency_code' => $order->currency_code,
                        'payment_reference' => $paymentId, 'status' => 'completed',
                        'purchased_at' => now(),
                    ]);
                }
            } else {
                $cohort = Bootcamp::query()->lockForUpdate()->findOrFail($item->bootcamp_id);
                $existing = BootcampPurchase::query()->where('user_id', $user->getKey())
                    ->where('bootcamp_id', $cohort->getKey())->lockForUpdate()->first();
                abort_unless($existing === null || $existing->status === 'completed', 409);
                if ($existing === null) {
                    $confirmed = BootcampPurchase::query()
                        ->where('bootcamp_id', $cohort->getKey())->where('status', 'completed')->count();
                    // A delayed webhook must never oversubscribe the cohort.
                    abort_unless($cohort->capacity === null || $confirmed < $cohort->capacity, 409,
                        'Cohort is full. An administrator must reconcile this paid order.');
                    BootcampPurchase::query()->create([
                        'user_id' => $user->getKey(), 'bootcamp_id' => $cohort->getKey(),
                        'amount' => $order->total, 'currency_code' => $order->currency_code,
                        'payment_reference' => $paymentId, 'status' => 'completed',
                    ]);
                }
            }

            $order->forceFill([
                'status' => 'paid', 'paid_at' => now(),
                'gateway_payment_reference' => $paymentId,
                'gateway_checkout_url' => null,
            ])->save();

            DB::table('payment_histories')->insert([
                'user_id' => $user->getKey(), 'gateway_id' => null, 'reference' => $paymentId,
                'amount' => $order->total, 'currency_code' => $order->currency_code,
                'status' => 'paid', 'paid_at' => now(),
                'metadata' => json_encode(['order_id' => $order->getKey(), 'gateway' => 'stripe'], JSON_THROW_ON_ERROR),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->recordEvent($order, $eventId, $eventType);
            AuditEvent::query()->create([
                'actor_id' => null, 'subject_type' => Order::class,
                'subject_id' => $order->getKey(), 'action' => 'commerce.payment_verified',
                'previous_values' => ['status' => 'awaiting_payment'],
                'new_values' => ['status' => 'paid', 'gateway' => 'stripe'],
            ]);
        }, 3);
    }

    private function recordEvent(Order $order, string $eventId, string $eventType): void
    {
        DB::table('payment_webhook_events')->insert([
            'gateway' => 'stripe', 'event_id' => $eventId,
            'order_id' => $order->getKey(), 'event_type' => $eventType,
            'processed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
