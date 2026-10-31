<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CourseCheckoutWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role, string $email): User
    {
        $user = User::create([
            'name' => ucfirst($role),
            'email' => $email,
            'password' => 'Password#12345',
        ]);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    private function course(User $owner, string $price = '25.00'): Course
    {
        return Course::create([
            'instructor_id' => $owner->getKey(),
            'title' => 'Secure Commerce Basics',
            'slug' => 'secure-commerce-basics',
            'status' => 'published',
            'published_at' => now(),
            'visibility' => 'public',
            'price' => $price,
            'currency_code' => 'USD',
        ]);
    }

    public function test_pending_checkout_is_idempotent_and_never_grants_access(): void
    {
        $teacher = $this->actor('instructor', 'teacher@example.test');
        $student = $this->actor('student', 'student@example.test');
        $course = $this->course($teacher);

        $this->actingAs($student)->post('/checkout/courses/'.$course->id)
            ->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertSame('25.00', $order->total);
        $this->assertSame('USD', $order->currency_code);
        $this->assertSame('Secure Commerce Basics', $order->items()->firstOrFail()->title);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseCount('payment_histories', 0);
        $this->get('/checkout/orders/'.$order->id)->assertOk()->assertSee('No payment gateway is connected');
        $this->post('/checkout/courses/'.$course->id)->assertRedirect();
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('audit_events', ['action' => 'commerce.order_prepared']);
        $this->post('/courses/'.$course->id.'/enroll')->assertForbidden();
        $this->get('/learn/'.$course->id)->assertForbidden();
    }

    public function test_order_privacy_and_administrator_read_only_view(): void
    {
        $teacher = $this->actor('instructor', 'teacher@example.test');
        $one = $this->actor('student', 'one@example.test');
        $two = $this->actor('student', 'two@example.test');
        $admin = $this->actor('admin', 'admin@example.test');
        $course = $this->course($teacher);

        $this->actingAs($one)->post('/checkout/courses/'.$course->id)->assertRedirect();
        $order = Order::firstOrFail();

        $this->actingAs($two)->get('/checkout/orders/'.$order->id)->assertNotFound();
        $this->patch('/checkout/orders/'.$order->id.'/cancel')->assertNotFound();
        $this->get('/checkout/orders')->assertOk()->assertDontSee($order->reference);
        $this->get('/admin/commerce/orders')->assertForbidden();

        $this->actingAs($admin)->get('/admin/commerce/orders')->assertOk()
            ->assertSee('Commerce')->assertSee('one@example.test');
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_cancel_and_price_change_never_mutate_historical_amount(): void
    {
        $teacher = $this->actor('instructor', 'teacher@example.test');
        $student = $this->actor('student', 'student@example.test');
        $course = $this->course($teacher);

        $this->actingAs($student)->post('/checkout/courses/'.$course->id)->assertRedirect();
        $first = Order::firstOrFail();

        $this->patch('/checkout/orders/'.$first->id.'/cancel')->assertRedirect();
        $this->assertSame('cancelled', $first->fresh()->status);
        $this->patch('/checkout/orders/'.$first->id.'/cancel')->assertStatus(409);

        $course->update(['price' => '40.00']);
        $this->post('/checkout/courses/'.$course->id)->assertRedirect();

        $this->assertDatabaseCount('orders', 2);
        $this->assertSame('25.00', $first->fresh()->total);
        $this->assertSame('40.00', Order::latest('id')->firstOrFail()->total);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseCount('payment_histories', 0);
        $this->assertDatabaseHas('audit_events', ['action' => 'commerce.order_cancelled']);
    }

    public function test_unpublished_and_free_courses_do_not_create_paid_orders(): void
    {
        $teacher = $this->actor('instructor', 'teacher@example.test');
        $student = $this->actor('student', 'student@example.test');
        $course = $this->course($teacher, '0.00');

        $this->actingAs($student)->post('/checkout/courses/'.$course->id)
            ->assertStatus(422);
        $course->update(['price' => '15.00', 'status' => 'draft']);
        $this->post('/checkout/courses/'.$course->id)->assertNotFound();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_guest_and_inactive_member_cannot_create_orders(): void
    {
        $teacher = $this->actor('instructor', 'teacher@example.test');
        $student = $this->actor('student', 'student@example.test');
        $course = $this->course($teacher);

        $this->post('/checkout/courses/'.$course->id)->assertRedirect(route('login'));

        $student->forceFill(['status' => 'suspended'])->save();
        $this->actingAs($student)->post('/checkout/courses/'.$course->id)->assertForbidden();
        $this->assertDatabaseCount('orders', 0);
    }
}
