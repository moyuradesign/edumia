<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\AuditEvent;
use App\Models\CommerceRefundRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RefundRequestService
{
    public function create(User $customer, Order $selected, string $reason): CommerceRefundRequest
    {
        abort_unless($customer->isActive(), 403);

        return DB::transaction(function () use ($customer, $selected, $reason): CommerceRefundRequest {
            $order = Order::query()->lockForUpdate()->findOrFail($selected->getKey());
            abort_unless((int) $order->user_id === (int) $customer->getKey(), 404);
            abort_unless(in_array($order->status, ['paid', 'partially_refunded'], true), 409,
                'Only paid orders can request a refund review.');
            if (CommerceRefundRequest::query()->where('order_id', $order->getKey())->exists()) {
                throw ValidationException::withMessages(['reason' => 'A refund review is already recorded for this order.']);
            }
            $request = CommerceRefundRequest::query()->create([
                'order_id' => $order->getKey(), 'user_id' => $customer->getKey(),
                'reason' => $reason, 'status' => 'open',
            ]);
            AuditEvent::query()->create([
                'actor_id' => $customer->getKey(),
                'subject_type' => CommerceRefundRequest::class,
                'subject_id' => $request->getKey(),
                'action' => 'commerce.refund_review_requested',
                'new_values' => ['order_id' => $order->getKey(), 'status' => 'open'],
            ]);
            return $request;
        }, 3);
    }

    public function review(User $admin, CommerceRefundRequest $selected, string $status, string $note): void
    {
        abort_unless($admin->isAdmin(), 403);
        abort_unless(in_array($status, ['reviewed', 'closed'], true), 422);

        DB::transaction(function () use ($admin, $selected, $status, $note): void {
            $request = CommerceRefundRequest::query()->lockForUpdate()->findOrFail($selected->getKey());
            abort_unless($request->status === 'open' || $request->status === 'reviewed', 409);
            $old = $request->status;
            $request->forceFill([
                'status' => $status, 'review_note' => $note,
                'reviewed_by' => $admin->getKey(), 'reviewed_at' => now(),
            ])->save();
            AuditEvent::query()->create([
                'actor_id' => $admin->getKey(),
                'subject_type' => CommerceRefundRequest::class,
                'subject_id' => $request->getKey(),
                'action' => 'commerce.refund_review_updated',
                'previous_values' => ['status' => $old],
                'new_values' => ['status' => $status],
            ]);
        }, 3);
    }
}
