<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use App\Services\Commerce\BakongStatusGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class CommerceAccountingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function setupPurchase(): array
    {
        $teacher = User::create(['name' => 'Teacher', 'email' => 'teacher@edumia.test', 'password' => 'Password#12345']);
        $teacher->forceFill(['role' => 'instructor', 'status' => 'active'])->save();
        $student = User::create(['name' => 'Student', 'email' => 'student@edumia.test', 'password' => 'Password#12345']);
        $student->forceFill(['role' => 'student', 'status' => 'active'])->save();
        $course = Course::create([
            'instructor_id' => $teacher->id, 'title' => 'Accounting Basics',
            'slug' => 'accounting-basics', 'status' => 'published',
            'visibility' => 'public', 'published_at' => now(),
            'price' => '25.00', 'currency_code' => 'USD',
        ]);
        config()->set('payments.stripe', [
            'enabled' => true, 'mode' => 'test',
            'secret_key' => 'sk_test_123456789abcdef',
            'webhook_secret' => 'whsec_test_123456789abcdef',
        ]);
        $this->actingAs($student)->post('/checkout/courses/'.$course->id)->assertRedirect();
        return [$student, $teacher, $course, Order::firstOrFail()];
    }

    private function checkout(Order $order): array
    {
        return [
            'id' => 'cs_test_accounting123456', 'url' => 'https://checkout.stripe.com/c/pay/verified',
            'client_reference_id' => $order->reference,
            'metadata' => ['order_reference' => $order->reference],
            'currency' => 'usd', 'amount_total' => 2500,
            'mode' => 'payment', 'payment_status' => 'paid', 'status' => 'complete',
            'livemode' => false, 'payment_intent' => 'pi_accounting123456',
        ];
    }

    private function signed(array $event): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($event, JSON_THROW_ON_ERROR);
        $time = now()->timestamp;
        return $this->call('POST', '/webhooks/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't='.$time.',v1='.hash_hmac('sha256', $time.'.'.$body,
                (string) config('payments.stripe.webhook_secret')),
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }

    private function sale(Order $order): void
    {
        $session = $this->checkout($order);
        Http::fake(fn () => Http::response($session, 200));
        $this->post('/checkout/orders/'.$order->id.'/pay')->assertRedirect();
        $this->signed([
            'id' => 'evt_accounting_sale123456',
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => $session['id']]],
        ])->assertOk();
        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_payment_creates_invoice_and_held_earning_with_customer_isolation(): void
    {
        [$student, $teacher, $course, $order] = $this->setupPurchase();
        $this->sale($order);
        $this->assertDatabaseHas('commerce_invoices', [
            'order_id' => $order->id, 'total_minor' => 2500,
            'tax_treatment' => 'not_assessed', 'status' => 'paid',
        ]);
        $this->assertDatabaseHas('instructor_earning_entries', [
            'source_key' => 'sale:'.$order->id,
            'instructor_id' => $teacher->id,
            'gross_minor' => 2500, 'platform_minor' => 500,
            'instructor_minor' => 2000, 'status' => 'held',
        ]);
        $this->assertDatabaseCount('instructor_payout_records', 0);
        $this->get('/checkout/orders/'.$order->id.'/invoice')->assertOk()
            ->assertSee('Not assessed by Edumia');
        $this->get('/admin/commerce/accounting')->assertForbidden();

        $stranger = User::create([
            'name' => 'Other', 'email' => 'other@edumia.test', 'password' => 'Password#12345',
        ]);
        $stranger->forceFill(['status' => 'active'])->save();
        $this->actingAs($stranger)->get('/checkout/orders/'.$order->id.'/invoice')->assertNotFound();
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@edumia.test', 'password' => 'Password#12345']);
        $admin->forceFill(['role' => 'admin', 'status' => 'active'])->save();
        $this->actingAs($admin)->get('/admin/commerce/accounting')
            ->assertOk()->assertSee('Held instructor earnings')->assertSee('EDM-');
    }

    public function test_verified_partial_then_full_refund_adjusts_earnings_and_revokes_course_access(): void
    {
        [$student, $teacher, $course, $order] = $this->setupPurchase();
        $this->sale($order);

        $refundedMinor = 1000;
        Http::fake(function (HttpRequest $request) use (&$refundedMinor) {
            $this->assertStringContainsString('/v1/charges/ch_accounting12345', $request->url());
            return Http::response([
                'id' => 'ch_accounting12345', 'payment_intent' => 'pi_accounting123456',
                'currency' => 'usd', 'amount' => 2500, 'amount_refunded' => $refundedMinor,
                'paid' => true, 'livemode' => false,
            ]);
        });
        $event = [
            'id' => 'evt_accounting_refund_one', 'type' => 'charge.refunded',
            'data' => ['object' => ['id' => 'ch_accounting12345']],
        ];
        $this->signed($event)->assertOk();
        $this->signed($event)->assertOk();
        $this->assertSame('partially_refunded', $order->fresh()->status);
        $this->assertDatabaseHas('commerce_invoices', [
            'order_id' => $order->id, 'refunded_minor' => 1000,
            'status' => 'partially_refunded',
        ]);
        $this->assertSame('active', Enrollment::firstOrFail()->status);
        $this->assertSame(2, DB::table('instructor_earning_entries')->count());

        $refundedMinor = 2500;
        $event['id'] = 'evt_accounting_refund_two';
        $this->signed($event)->assertOk();
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame('revoked', Enrollment::firstOrFail()->status);
        $this->get('/learn/'.$course->id)->assertNotFound();
        $this->assertDatabaseHas('commerce_invoices', [
            'order_id' => $order->id, 'refunded_minor' => 2500, 'status' => 'refunded',
        ]);
        $this->assertSame(0, DB::table('instructor_earning_entries')
            ->where('order_id', $order->id)->sum('instructor_minor'));
        $this->assertDatabaseCount('payment_histories', 1);
        $this->assertDatabaseCount('instructor_payout_records', 0);
    }

    public function test_dispute_suspends_only_payment_linked_enrollment(): void
    {
        [$student, $teacher, $course, $order] = $this->setupPurchase();
        $this->sale($order);
        Http::fake(function (HttpRequest $request) {
            if (str_contains($request->url(), '/v1/disputes/')) {
                return Http::response([
                    'id' => 'dp_accounting12345', 'charge' => 'ch_accounting12345',
                    'currency' => 'usd', 'amount' => 2500, 'status' => 'needs_response',
                ]);
            }
            return Http::response([
                'id' => 'ch_accounting12345', 'payment_intent' => 'pi_accounting123456',
                'currency' => 'usd', 'amount' => 2500, 'paid' => true, 'livemode' => false,
            ]);
        });
        $event = [
            'id' => 'evt_accounting_dispute', 'type' => 'charge.dispute.created',
            'data' => ['object' => ['id' => 'dp_accounting12345']],
        ];
        $this->signed($event)->assertOk();
        $this->signed($event)->assertOk();
        $this->assertSame('disputed', $order->fresh()->status);
        $this->assertDatabaseCount('commerce_disputes', 1);
        $this->assertSame('revoked', Enrollment::firstOrFail()->status);
        $this->get('/learn/'.$course->id)->assertNotFound();
    }

    public function test_bakong_verifier_checks_real_recipient_and_amount_without_fulfilling_order(): void
    {
        $gateway = app(BakongStatusGateway::class);
        $this->assertFalse($gateway->configured());
        config()->set('payments.bakong', [
            'enabled' => true, 'base_url' => 'https://sit-api-bakong.nbc.gov.kh',
            'token' => str_repeat('z', 24), 'receiver_account' => 'merchant@bakong',
        ]);
        Http::fake([
            'sit-api-bakong.nbc.gov.kh/*' => Http::response([
                'responseCode' => 0,
                'data' => [
                    'hash' => str_repeat('b', 64),
                    'toAccountId' => 'merchant@bakong',
                    'currency' => 'USD', 'amount' => '25.00',
                    'trackingStatus' => 'RECEIVE_AT_RECEIVER_BANK',
                ],
            ]),
        ]);
        $md5 = str_repeat('a', 32);
        $this->assertTrue($gateway->verifyTrustedMd5($md5, 'USD', '25.00')['matched']);
        $this->assertFalse($gateway->verifyTrustedMd5($md5, 'USD', '26.00')['matched']);
        $this->assertFalse($gateway->verifyTrustedMd5($md5, 'KHR', '25.00')['matched']);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('enrollments', 0);
    }
}
