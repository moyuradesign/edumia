<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\AuditEvent;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CourseCheckoutService
{
    /**
     * Create an unpayable price-locked draft order. This does NOT initiate,
     * verify or record payment and MUST NOT create an enrollment.
     */
    public function prepare(User $actor, Course $selected): Order
    {
        abort_unless($actor->isActive(), 403);

        return DB::transaction(function () use ($actor, $selected): Order {
            // Serialize concurrent requests by the same purchaser, including
            // requests for the first order (before any order row exists).
            $customer = User::query()->lockForUpdate()->findOrFail($actor->getKey());
            abort_unless($customer->isActive(), 403);

            $course = Course::query()->lockForUpdate()->findOrFail($selected->getKey());
            abort_unless($course->status === 'published'
                && $course->visibility === 'public'
                && $course->published_at !== null, 404);
            abort_if($course->isFree(), 422, 'Free courses use the regular enrollment flow.');

            abort_if(Enrollment::query()->where('user_id', $customer->getKey())
                ->where('course_id', $course->getKey())
                ->whereIn('status', ['active', 'completed'])->exists(), 409, 'Already enrolled.');

            // Eloquent's decimal cast returns a two-decimal string. Avoid
            // floating-point arithmetic or client-submitted price data.
            $price = (string) $course->price;
            $currency = strtoupper((string) $course->currency_code);
            abort_unless(preg_match('/^\d{1,10}\.\d{2}$/D', $price) === 1
                && $price !== '0.00'
                && preg_match('/^[A-Z]{3}$/D', $currency) === 1, 422, 'Invalid course price or currency.');

            $existing = Order::query()
                ->where('user_id', $customer->getKey())
                ->where('status', 'pending')
                ->whereHas('items', fn ($query) => $query->where('course_id', $course->getKey()))
                ->lockForUpdate()->get();

            foreach ($existing as $order) {
                if ($order->expires_at?->isFuture()
                    && $order->currency_code === $currency
                    && $order->total === $price) {
                    return $order;
                }

                $order->update(['status' => 'expired']);
            }

            $order = Order::query()->create([
                'user_id' => $customer->getKey(),
                'reference' => (string) Str::uuid(),
                'status' => 'pending',
                'currency_code' => $currency,
                'subtotal' => $price,
                'total' => $price,
                'expires_at' => now()->addMinutes(30),
            ]);

            $order->items()->create([
                'course_id' => $course->getKey(),
                'title' => $course->title,
                'quantity' => 1,
                'unit_price' => $price,
                'line_total' => $price,
            ]);

            AuditEvent::query()->create([
                'actor_id' => $customer->getKey(),
                'subject_type' => Order::class,
                'subject_id' => $order->getKey(),
                'action' => 'commerce.order_prepared',
                'new_values' => [
                    'status' => 'pending',
                    'currency_code' => $currency,
                    'total' => $price,
                ],
            ]);

            return $order;
        }, 3);
    }

    public function cancel(User $actor, Order $selected): void
    {
        abort_unless($actor->isActive(), 403);

        DB::transaction(function () use ($actor, $selected): void {
            $order = Order::query()->lockForUpdate()->findOrFail($selected->getKey());
            abort_unless((int) $order->user_id === (int) $actor->getKey(), 404);
            abort_unless($order->status === 'pending', 409, 'Only pending orders can be cancelled.');

            $order->update(['status' => 'cancelled']);

            AuditEvent::query()->create([
                'actor_id' => $actor->getKey(),
                'subject_type' => Order::class,
                'subject_id' => $order->getKey(),
                'action' => 'commerce.order_cancelled',
                'previous_values' => ['status' => 'pending'],
                'new_values' => ['status' => 'cancelled'],
            ]);
        }, 3);
    }
}
