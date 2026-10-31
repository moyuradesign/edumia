<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Internal ledger reconciliation. This does not confirm bank settlement,
 * instruct a refund, modify a purchase, or initiate any payout.
 */
final class CommerceReconciliationService
{
    public function scan(bool $persist = false): array
    {
        $counts = [
            'paid_missing_invoice' => 0,
            'paid_missing_payment_record' => 0,
            'paid_missing_earning_entry' => 0,
            'invoice_amount_mismatch' => 0,
            'refunded_total_mismatch' => 0,
        ];

        Order::query()->whereNotNull('paid_at')->orderBy('id')->chunkById(200,
            function ($orders) use (&$counts, $persist): void {
                foreach ($orders as $order) {
                    $invoice = DB::table('commerce_invoices')->where('order_id', $order->id)->first();
                    $paymentExists = DB::table('payment_histories')
                        ->where('reference', $order->gateway_payment_reference)
                        ->where('user_id', $order->user_id)->exists();
                    $saleExists = DB::table('instructor_earning_entries')
                        ->where('source_key', 'sale:'.$order->id)->exists();
                    $refund = DB::table('commerce_refunds')->where('order_id', $order->id)->first();
                    $total = app(CommerceAccountingService::class)->minor($order->total);

                    $failed = [
                        'paid_missing_invoice' => $invoice === null,
                        'paid_missing_payment_record' => ! $paymentExists,
                        'paid_missing_earning_entry' => ! $saleExists,
                        'invoice_amount_mismatch' => $invoice !== null &&
                            ((int) $invoice->total_minor !== $total || $invoice->currency_code !== $order->currency_code),
                        'refunded_total_mismatch' => $invoice !== null &&
                            (int) $invoice->refunded_minor !== (int) ($refund->refunded_minor ?? 0),
                    ];

                    foreach ($failed as $kind => $isFailed) {
                        if ($isFailed) {
                            $counts[$kind]++;
                        }
                        if ($persist) {
                            DB::table('commerce_reconciliation_issues')->updateOrInsert(
                                ['issue_key' => 'order:'.$order->id.':'.$kind],
                                ['order_id' => $order->id, 'kind' => $kind,
                                    'status' => $isFailed ? 'open' : 'resolved',
                                    'updated_at' => now(), 'created_at' => now()]
                            );
                        }
                    }
                }
            });

        return $counts;
    }
}
