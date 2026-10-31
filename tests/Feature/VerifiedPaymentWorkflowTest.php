<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Ebook;
use App\Models\Bootcamp;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class VerifiedPaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role, string $email): User
    {
        $actor = User::create(['name' => ucfirst($role), 'email' => $email, 'password' => 'Password#12345']);
        $actor->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $actor;
    }

    private function pendingOrder(): array
    {
        $teacher = $this->actor('instructor', 'teacher@edumia.test');
        $student = $this->actor('student', 'student@edumia.test');
        $course = Course::create([
            'instructor_id' => $teacher->id,
            'title' => 'Secure Payment',
            'slug' => 'secure-payment',
            'status' => 'published',
            'published_at' => now(),
            'visibility' => 'public',
            'price' => '25.00',
            'currency_code' => 'USD',
        ]);

        $this->actingAs($student)->post('/checkout/courses/'.$course->id)->assertRedirect();

        return [$student, $course, Order::firstOrFail()];
    }

    private function enableStripe(): void
    {
        config()->set('payments.stripe', [
            'enabled' => true,
            'mode' => 'test',
            'secret_key' => 'sk_test_123456789abcdef',
            'webhook_secret' => 'whsec_test_123456789abcdef',
        ]);
    }

    private function stripeSession(Order $order, array $overrides = []): array
    {
        return array_replace([
            'id' => 'cs_test_123456789abcdef',
            'url' => 'https://checkout.stripe.com/c/pay/verified',
            'client_reference_id' => $order->reference,
            'metadata' => ['order_reference' => $order->reference],
            'currency' => 'usd',
            'amount_total' => 2500,
            'expires_at' => now()->addMinutes(55)->timestamp,
            'mode' => 'payment',
            'payment_status' => 'paid',
            'status' => 'complete',
            'livemode' => false,
            'payment_intent' => 'pi_test_123456789abcdef',
        ], $overrides);
    }

    private function fakeGateway(array $session, ?array $verifiedSession = null): void
    {
        Http::fake(function (HttpRequest $request) use ($session, $verifiedSession) {
            $this->assertStringStartsWith('https://api.stripe.com/v1/checkout/sessions', $request->url());

            return Http::response($request->method() === 'GET' ? ($verifiedSession ?? $session) : $session, 200);
        });
    }

    private function start(Order $order): void
    {
        $this->post('/checkout/orders/'.$order->id.'/pay')
            ->assertRedirect('https://checkout.stripe.com/c/pay/verified');
        $this->assertSame('awaiting_payment', $order->fresh()->status);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseCount('payment_histories', 0);
    }

    private function event(string $id = 'evt_test_123456789abcdef'): array
    {
        return ['id' => $id, 'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_123456789abcdef']]];
    }

    private function webhook(array $event, bool $signed = true): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($event, JSON_THROW_ON_ERROR);
        $timestamp = now()->timestamp;
        $signature = $signed
            ? hash_hmac('sha256', $timestamp.'.'.$body, (string) config('payments.stripe.webhook_secret'))
            : str_repeat('0', 64);

        return $this->call('POST', '/webhooks/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }

    public function test_gateway_is_disabled_and_browser_cannot_grant_access(): void
    {
        [$student, $course, $order] = $this->pendingOrder();

        $this->post('/checkout/orders/'.$order->id.'/pay')->assertStatus(503);
        $this->get('/checkout/orders/'.$order->id.'?payment=return')->assertOk();
        $this->post('/courses/'.$course->id.'/enroll')->assertForbidden();
        $this->get('/learn/'.$course->id)->assertNotFound();
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_verified_webhook_grants_course_once_and_is_idempotent(): void
    {
        [$student, $course, $order] = $this->pendingOrder();
        $this->enableStripe();
        $this->fakeGateway($this->stripeSession($order));
        $this->start($order);

        $this->get('/checkout/orders/'.$order->id.'?payment=return')->assertOk();
        $this->assertDatabaseCount('enrollments', 0);

        $this->webhook($this->event())->assertOk();
        $this->webhook($this->event())->assertOk();
        $this->webhook($this->event('evt_test_another123456'))->assertOk();

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->paid_at);
        $this->assertDatabaseCount('payment_webhook_events', 2);
        $this->assertDatabaseCount('payment_histories', 1);
        $this->assertDatabaseCount('enrollments', 1);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $student->id, 'course_id' => $course->id, 'status' => 'active',
        ]);
        $this->post('/checkout/courses/'.$course->id)->assertStatus(409);
    }

    public function test_invalid_signatures_and_incorrect_totals_never_grant_access(): void
    {
        [$student, $course, $order] = $this->pendingOrder();
        $this->enableStripe();
        $this->fakeGateway($this->stripeSession($order), $this->stripeSession($order, ['amount_total' => 100]));
        $this->start($order);

        $this->webhook($this->event(), false)->assertStatus(400);
        $this->assertDatabaseCount('enrollments', 0);

        $this->webhook($this->event())->assertStatus(422);
        $this->assertSame('awaiting_payment', $order->fresh()->status);
        $this->assertDatabaseCount('payment_histories', 0);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_unpaid_session_and_replay_after_cancellation_fail_closed(): void
    {
        [$student, $course, $order] = $this->pendingOrder();
        $this->enableStripe();
        $this->fakeGateway($this->stripeSession($order), $this->stripeSession($order, ['payment_status' => 'unpaid']));
        $this->start($order);
        $this->patch('/checkout/orders/'.$order->id.'/cancel')->assertStatus(409);

        $this->webhook($this->event())->assertStatus(422);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseCount('payment_histories', 0);
    }

    public function test_other_student_cannot_initiate_another_users_payment(): void
    {
        [$student, $course, $order] = $this->pendingOrder();
        $other = $this->actor('student', 'different@edumia.test');
        $this->enableStripe();
        $this->actingAs($other)->post('/checkout/orders/'.$order->id.'/pay')->assertNotFound();
        $this->assertSame('pending', $order->fresh()->status);
    }
    public function test_verified_ebook_purchase_grants_private_download_only_after_payment(): void
    {
        $teacher = $this->actor('instructor', 'teacher@edumia.test');
        $student = $this->actor('student', 'reader@edumia.test');
        Storage::fake('local');
        Storage::disk('local')->put('edumia/ebooks/security.pdf', '%PDF-1.4 test-content');
        $ebook = Ebook::query()->create([
            'author_id' => $teacher->getKey(),
            'title' => 'Security Handbook', 'slug' => 'security-handbook',
            'price' => '25.00', 'status' => 'published',
            'published_at' => now()->subMinute(),
            'file_path' => 'edumia/ebooks/security.pdf',
        ]);

        $this->actingAs($student)->post('/checkout/ebooks/'.$ebook->id)->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertDatabaseCount('ebook_purchases', 0);
        $this->get('/my-ebooks/'.$ebook->id.'/download')->assertForbidden();
        $this->enableStripe();
        $this->fakeGateway($this->stripeSession($order));
        $this->start($order);
        $this->webhook($this->event())->assertOk();

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertDatabaseHas('ebook_purchases', [
            'ebook_id' => $ebook->id, 'user_id' => $student->id,
            'status' => 'completed', 'amount' => '25',
        ]);
        $this->get('/my-ebooks/'.$ebook->id.'/download')->assertOk();
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseCount('payment_histories', 1);
    }

    public function test_verified_bootcamp_purchase_grants_cohort_and_limits_reservations(): void
    {
        $teacher = $this->actor('instructor', 'teacher@edumia.test');
        $first = $this->actor('student', 'first@edumia.test');
        $second = $this->actor('student', 'second@edumia.test');
        $bootcamp = Bootcamp::query()->create([
            'instructor_id' => $teacher->getKey(),
            'title' => 'Leadership Cohort', 'slug' => 'leadership-cohort',
            'price' => '25.00', 'capacity' => 1, 'status' => 'published',
        ]);

        $this->actingAs($first)->post('/checkout/bootcamps/'.$bootcamp->id)->assertRedirect();
        $firstOrder = Order::firstOrFail();
        $this->enableStripe();
        $this->fakeGateway($this->stripeSession($firstOrder));
        $this->start($firstOrder);
        $this->get('/my-bootcamps/'.$bootcamp->id.'/classroom')->assertForbidden();

        $this->actingAs($second)->post('/checkout/bootcamps/'.$bootcamp->id)->assertRedirect();
        $secondOrder = Order::query()->latest('id')->firstOrFail();
        $this->post('/checkout/orders/'.$secondOrder->id.'/pay')->assertStatus(409);
        $this->assertDatabaseCount('bootcamp_purchases', 0);

        $this->actingAs($first);
        $this->webhook($this->event())->assertOk();
        $this->assertDatabaseHas('bootcamp_purchases', [
            'bootcamp_id' => $bootcamp->id, 'user_id' => $first->id, 'status' => 'completed',
        ]);
        $this->get('/my-bootcamps/'.$bootcamp->id.'/classroom')->assertOk();
        $this->actingAs($second)->get('/my-bootcamps/'.$bootcamp->id.'/classroom')->assertForbidden();
        $this->assertDatabaseCount('bootcamp_purchases', 1);
        $this->assertDatabaseCount('payment_histories', 1);
    }

    public function test_verified_expiration_releases_checkout_without_issuing_entitlement(): void
    {
        [$student, $course, $order] = $this->pendingOrder();
        $this->enableStripe();
        $this->fakeGateway($this->stripeSession($order), $this->stripeSession($order, [
            'status' => 'expired', 'payment_status' => 'unpaid',
        ]));
        $this->start($order);
        $event = $this->event('evt_test_expired12345');
        $event['type'] = 'checkout.session.expired';
        $this->webhook($event)->assertOk();
        $this->webhook($event)->assertOk();

        $this->assertSame('expired', $order->fresh()->status);
        $this->assertNull($order->fresh()->gateway_checkout_url);
        $this->assertDatabaseCount('payment_webhook_events', 1);
        $this->assertDatabaseCount('payment_histories', 0);
        $this->assertDatabaseCount('enrollments', 0);
        $this->post('/checkout/courses/'.$course->id)->assertRedirect();
        $this->assertDatabaseCount('orders', 2);
    }

    public function test_expiry_of_checkout_session_extends_bootcamp_seat_reservation(): void
    {
        $teacher = $this->actor('instructor', 'trainer@edumia.test');
        $first = $this->actor('student', 'buyer-one@edumia.test');
        $second = $this->actor('student', 'buyer-two@edumia.test');
        $bootcamp = Bootcamp::query()->create([
            'instructor_id' => $teacher->id, 'title' => 'Single Seat Workshop',
            'slug' => 'single-seat-workshop', 'price' => '25.00',
            'capacity' => 1, 'status' => 'published',
        ]);
        $this->actingAs($first)->post('/checkout/bootcamps/'.$bootcamp->id)->assertRedirect();
        $firstOrder = Order::firstOrFail();
        $originalExpiry = $firstOrder->expires_at;

        // The buyer starts Stripe Checkout just before the draft order expires.
        $this->travel(50)->minutes();
        $this->enableStripe();
        $this->fakeGateway($this->stripeSession($firstOrder));
        $this->post('/checkout/orders/'.$firstOrder->id.'/pay')->assertRedirect();
        $this->assertTrue($firstOrder->fresh()->expires_at->greaterThan($originalExpiry));
        $this->assertTrue($firstOrder->fresh()->expires_at->greaterThan(now()->addMinutes(50)));

        // After the original quote's expiry, Checkout remains payable and
        // the second buyer MUST NOT be allowed to reserve the same seat.
        $this->travel(15)->minutes();
        $this->actingAs($second)->post('/checkout/bootcamps/'.$bootcamp->id)->assertRedirect();
        $otherOrder = Order::query()->latest('id')->firstOrFail();
        $this->post('/checkout/orders/'.$otherOrder->id.'/pay')->assertStatus(409);
        $this->assertSame('pending', $otherOrder->fresh()->status);
        $this->assertDatabaseCount('bootcamp_purchases', 0);
        $this->assertDatabaseCount('payment_histories', 0);
    }

    public function test_invalid_gateway_expiry_fails_without_reserving_or_enrolling(): void
    {
        [$student, $course, $order] = $this->pendingOrder();
        $this->enableStripe();
        $this->fakeGateway($this->stripeSession($order, [
            'expires_at' => now()->subMinute()->timestamp,
        ]));
        $this->post('/checkout/orders/'.$order->id.'/pay')->assertStatus(502);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseCount('payment_histories', 0);
    }

}
