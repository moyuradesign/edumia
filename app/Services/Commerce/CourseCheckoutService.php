<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\AuditEvent;
use App\Models\Bootcamp;
use App\Models\BootcampPurchase;
use App\Models\Course;
use App\Models\Ebook;
use App\Models\EbookPurchase;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CourseCheckoutService
{
    public function prepare(User $actor, Course $course): Order
    {
        return $this->prepareProduct($actor, $course);
    }

    public function prepareEbook(User $actor, Ebook $ebook): Order
    {
        return $this->prepareProduct($actor, $ebook);
    }

    public function prepareBootcamp(User $actor, Bootcamp $bootcamp): Order
    {
        return $this->prepareProduct($actor, $bootcamp);
    }

    /**
     * Each immutable order holds exactly one product snapshot.
     * Only the verified gateway fulfillment service can issue an entitlement.
     */
    private function prepareProduct(User $actor, Course|Ebook|Bootcamp $product): Order
    {
        abort_unless($actor->isActive(), 403);

        return DB::transaction(function () use ($actor, $product): Order {
            $customer = User::query()->lockForUpdate()->findOrFail($actor->getKey());
            abort_unless($customer->isActive(), 403);
            $product = $product->newQuery()->lockForUpdate()->findOrFail($product->getKey());

            if ($product instanceof Course) {
                abort_unless($product->status === 'published' && $product->visibility === 'public'
                    && $product->published_at !== null, 404);
                abort_if(Enrollment::query()->where('user_id', $customer->getKey())
                    ->where('course_id', $product->getKey())->whereIn('status', ['active', 'completed'])
                    ->exists(), 409, 'Already enrolled.');
                $key = 'course_id';
            } elseif ($product instanceof Ebook) {
                abort_unless($product->status === 'published'
                    && $product->published_at?->isPast(), 404);
                abort_if(EbookPurchase::query()->where('user_id', $customer->getKey())
                    ->where('ebook_id', $product->getKey())->where('status', 'completed')
                    ->exists(), 409, 'Already in your library.');
                $key = 'ebook_id';
            } else {
                abort_unless($product->status === 'published', 404);
                abort_if(BootcampPurchase::query()->where('user_id', $customer->getKey())
                    ->where('bootcamp_id', $product->getKey())->where('status', 'completed')
                    ->exists(), 409, 'Already enrolled in this cohort.');
                $key = 'bootcamp_id';
            }

            abort_if($product->isFree(), 422, 'Free items use the regular enrollment or library flow.');
            $price = (string) $product->price;
            $currency = $product instanceof Course
                ? strtoupper((string) $product->currency_code) : 'USD';

            abort_unless(preg_match('/^\d{1,10}\.\d{2}$/D', $price) === 1
                && $price !== '0.00' && preg_match('/^[A-Z]{3}$/D', $currency) === 1,
                422, 'Invalid product price or currency.');

            $existing = Order::query()
                ->where('user_id', $customer->getKey())
                ->whereIn('status', ['pending', 'awaiting_payment'])
                ->whereHas('items', fn ($query) => $query->where($key, $product->getKey()))
                ->lockForUpdate()->get();

            foreach ($existing as $order) {
                if ($order->status === 'awaiting_payment') {
                    return $order;
                }
                if ($order->expires_at?->isFuture()
                    && $order->currency_code === $currency && $order->total === $price) {
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
                'expires_at' => now()->addHour(),
            ]);
            $order->items()->create([
                $key => $product->getKey(),
                'title' => $product->title,
                'quantity' => 1,
                'unit_price' => $price,
                'line_total' => $price,
            ]);
            AuditEvent::query()->create([
                'actor_id' => $customer->getKey(),
                'subject_type' => Order::class,
                'subject_id' => $order->getKey(),
                'action' => 'commerce.order_prepared',
                'new_values' => ['status' => 'pending', 'currency_code' => $currency, 'total' => $price],
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
