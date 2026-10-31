<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Bootcamp;
use App\Models\Course;
use App\Models\Ebook;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Must be invoked within the same DB transaction as verified fulfillment.
 * Amounts are integer minor units, and earned balances remain held.
 */
final class CommerceAccountingService
{
    public function recordPaid(Order $order): void
    {
        $item = $order->items()->firstOrFail();
        $instructor = $item->course_id !== null
            ? Course::query()->findOrFail($item->course_id)->instructor_id
            : ($item->ebook_id !== null
                ? Ebook::query()->findOrFail($item->ebook_id)->author_id
                : Bootcamp::query()->findOrFail($item->bootcamp_id)->instructor_id);

        $gross = $this->minor($order->total);
        $basisPoints = (int) config('payments.platform_commission_bps', 2000);
        abort_unless($basisPoints >= 0 && $basisPoints <= 10000, 500);

        $platform = intdiv($gross * $basisPoints, 10000);
        DB::table('commerce_invoices')->insertOrIgnore([
            'order_id' => $order->getKey(),
            'number' => 'EDM-'.str_pad((string) $order->getKey(), 9, '0', STR_PAD_LEFT),
            'customer_name' => $order->user->name,
            'customer_email' => $order->user->email,
            'currency_code' => $order->currency_code,
            'total_minor' => $gross,
            'tax_treatment' => 'not_assessed',
            'status' => 'paid',
            'issued_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('instructor_earning_entries')->insertOrIgnore([
            'order_id' => $order->getKey(), 'instructor_id' => $instructor,
            'source_key' => 'sale:'.$order->getKey(), 'entry_type' => 'sale',
            'currency_code' => $order->currency_code,
            'gross_minor' => $gross, 'platform_minor' => $platform,
            'instructor_minor' => $gross - $platform,
            'status' => 'held',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function recordRefund(Order $order, string $chargeId, int $cumulativeMinor): void
    {
        $invoice = DB::table('commerce_invoices')->where('order_id', $order->getKey())
            ->lockForUpdate()->first();
        abort_unless($invoice !== null, 409, 'Invoice is missing; reconcile before refund settlement.');
        abort_unless($cumulativeMinor >= 0 && $cumulativeMinor <= (int) $invoice->total_minor, 422);

        $refund = DB::table('commerce_refunds')->where('order_id', $order->getKey())
            ->lockForUpdate()->first();
        abort_unless($refund === null || $refund->gateway_charge_id === $chargeId, 409);
        $previous = $refund ? (int) $refund->refunded_minor : 0;
        abort_unless($cumulativeMinor >= $previous, 409, 'Cumulative refund decreased.');
        if ($cumulativeMinor === $previous) {
            return;
        }

        if ($refund) {
            DB::table('commerce_refunds')->where('id', $refund->id)->update([
                'refunded_minor' => $cumulativeMinor, 'updated_at' => now(),
            ]);
        } else {
            DB::table('commerce_refunds')->insert([
                'order_id' => $order->getKey(), 'gateway_charge_id' => $chargeId,
                'refunded_minor' => $cumulativeMinor, 'currency_code' => $order->currency_code,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $sale = DB::table('instructor_earning_entries')->where('source_key', 'sale:'.$order->getKey())
            ->lockForUpdate()->first();
        abort_unless($sale !== null, 409);
        $previousInstructor = (int) DB::table('instructor_earning_entries')
            ->where('order_id', $order->getKey())->where('entry_type', 'refund')
            ->sum('instructor_minor');
        $previousPlatform = (int) DB::table('instructor_earning_entries')
            ->where('order_id', $order->getKey())->where('entry_type', 'refund')
            ->sum('platform_minor');
        $targetInstructor = -intdiv((int) $sale->instructor_minor * $cumulativeMinor, (int) $invoice->total_minor);
        $targetPlatform = -intdiv((int) $sale->platform_minor * $cumulativeMinor, (int) $invoice->total_minor);
        DB::table('instructor_earning_entries')->insert([
            'order_id' => $order->getKey(), 'instructor_id' => $sale->instructor_id,
            'source_key' => 'refund:'.$order->getKey().':'.$cumulativeMinor,
            'entry_type' => 'refund', 'currency_code' => $order->currency_code,
            'gross_minor' => -($cumulativeMinor - $previous),
            'platform_minor' => $targetPlatform - $previousPlatform,
            'instructor_minor' => $targetInstructor - $previousInstructor,
            'status' => 'held', 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('commerce_invoices')->where('id', $invoice->id)->update([
            'refunded_minor' => $cumulativeMinor,
            'status' => $cumulativeMinor === (int) $invoice->total_minor ? 'refunded' : 'partially_refunded',
            'updated_at' => now(),
        ]);
    }

    public function minor(string $amount): int
    {
        abort_unless(preg_match('/^(0|[1-9]\d{0,8})\.(\d{2})$/D', $amount, $matches) === 1, 422);
        return (int) $matches[1] * 100 + (int) $matches[2];
    }
}
