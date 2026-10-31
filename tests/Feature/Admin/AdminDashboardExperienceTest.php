<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminDashboardExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role, string $name, string $email): User
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'Strong#Password123',
        ]);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();
        return $user;
    }

    public function test_admin_has_grouped_sidebar_real_modules_and_operational_dashboard(): void
    {
        $admin = $this->member('admin', 'Site Owner', 'owner@example.test');
        $instructor = $this->member('instructor', 'Educator', 'educator@example.test');
        $student = $this->member('student', 'A Learner', 'learner@example.test');

        $course = Course::query()->create([
            'instructor_id' => $instructor->id,
            'title' => 'Review our course', 'slug' => 'review-our-course',
            'summary' => 'Instructor course',
            'status' => 'pending', 'visibility' => 'private',
            'price' => '0.00',
        ]);
        Enrollment::query()->create([
            'course_id' => $course->id, 'user_id' => $student->id,
            'status' => 'active', 'progress_percent' => 0,
        ]);
        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertOk()
            ->assertSee('data-admin-shell', false)
            ->assertSee('data-admin-sidebar', false)
            ->assertSee('id="panel-navigation"', false)
            ->assertSee('data-admin-search', false)
            ->assertSee('id="admin-main"', false)
            ->assertSee('data-admin-profile', false)
            ->assertSee('Welcome back, Site Owner')
            ->assertSee('Review our course')
            ->assertSee('Enrollment activity')
            ->assertSee('Needs attention')
            ->assertSee('Member management')
            ->assertSee('name="member_q"', false)
            ->assertSee('action="'.route('admin.courses.publish', $course).'"', false)
            ->assertSee('href="'.route('admin.tickets.index').'"', false)
            ->assertSee('href="'.route('admin.roles.index').'"', false)
            ->assertSee('href="'.route('admin.settings.edit').'"', false)
            ->assertSee('href="'.route('admin.media.index').'"', false)
            ->assertSee('href="'.route('admin.commerce.orders.index').'"', false);
        $this->assertSame(7, substr_count((string) $response->getContent(), 'class="admin-chart-column"'));
    }

    public function test_member_filter_is_functional_and_safely_validated(): void
    {
        $admin = $this->member('admin', 'Owner', 'owner@example.test');
        $this->member('student', 'Findable Member', 'found@example.test');
        $this->member('student', 'Unrelated Member', 'other@example.test');

        $this->actingAs($admin)->get('/dashboard?member_q=Findable')->assertOk()
            ->assertSee('Findable Member')
            ->assertDontSee('Unrelated Member')
            ->assertSee('value="Findable"', false);
        $this->get('/dashboard?member_q[]=invalid')->assertOk();
    }

    public function test_paid_metric_does_not_count_unverified_orders(): void
    {
        $admin = $this->member('admin', 'Owner', 'owner@example.test');
        $member = $this->member('student', 'Buyer', 'buyer@example.test');
        Order::query()->create([
            'user_id' => $member->id, 'reference' => 'pending-qa-unique',
            'status' => 'pending', 'currency_code' => 'USD',
            'subtotal' => '25.00', 'total' => '25.00',
        ]);
        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertSee('Verified paid orders')
            ->assertSee('Paid records only · no estimated revenue')
            ->assertSee('Pending');
    }

    public function test_non_admin_workspaces_are_not_replaced_or_exposed(): void
    {
        $student = $this->member('student', 'Student', 'student@example.test');
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->actingAs($student)->get('/dashboard')->assertOk()
            ->assertDontSee('data-admin-shell', false)
            ->assertDontSee('ADMIN CONSOLE')
            ->assertSee('id="panel-navigation"', false);
        $this->get('/admin/categories')->assertForbidden();
        $this->get('/admin/commerce/orders')->assertForbidden();
        $this->get('/admin/settings/general')->assertForbidden();
    }
}
