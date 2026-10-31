<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bootcamp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BootcampWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role, string $email): User
    {
        $user = User::create(['name' => $role, 'email' => $email, 'password' => 'Password#12345']);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();

        return $user;
    }

    private function cohort(int $capacity = 1, int $price = 0): array
    {
        return [
            'title' => 'Leadership Bootcamp',
            'slug' => 'leadership-bootcamp',
            'description' => 'A structured course for emerging leaders.',
            'price' => $price,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'capacity' => $capacity,
        ];
    }

    public function test_free_cohort_can_be_authored_approved_and_joined_up_to_capacity(): void
    {
        $instructor = $this->actor('instructor', 'teacher@edumia.test');
        $admin = $this->actor('admin', 'admin@edumia.test');
        $learner = $this->actor('student', 'learner@edumia.test');
        $other = $this->actor('student', 'other@edumia.test');

        $this->actingAs($instructor)->get('/instructor/bootcamps/create')->assertOk();
        $this->post('/instructor/bootcamps', $this->cohort())
            ->assertRedirect();

        $bootcamp = Bootcamp::firstOrFail();
        $this->assertSame('draft', $bootcamp->status);
        $this->post('/instructor/bootcamps/'.$bootcamp->id.'/modules', [
            'title' => 'Communication module', 'description' => 'Learn feedback.', 'sort_order' => 0,
        ])->assertRedirect();
        $module = $bootcamp->modules()->firstOrFail();

        $this->post('/instructor/bootcamps/'.$bootcamp->id.'/modules/'.$module->id.'/resources', [
            'title' => 'Private learning workbook',
            'external_url' => 'https://example.test/private-workbook',
            'sort_order' => 0,
        ])->assertRedirect();

        $this->post('/instructor/bootcamps/'.$bootcamp->id.'/sessions', [
            'title' => 'Leadership live session',
            'meeting_url' => 'https://example.test/private-meeting',
            'starts_at' => now()->addDays(4)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(4)->addHour()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        auth()->logout();
        $this->get('/bootcamps')->assertOk()->assertDontSee('Leadership Bootcamp');

        $this->actingAs($admin)->patch('/admin/bootcamps/'.$bootcamp->id.'/publication', [
            'published' => 1,
        ])->assertRedirect(route('admin.bootcamps.index'));
        $this->assertSame('published', $bootcamp->fresh()->status);

        auth()->logout();
        $this->get('/bootcamps')->assertOk()->assertSee('Leadership Bootcamp');
        $this->get('/bootcamps/'.$bootcamp->slug)->assertOk()
            ->assertDontSee('private-meeting')->assertDontSee('private-workbook');

        $this->actingAs($learner)->get('/my-bootcamps/'.$bootcamp->id.'/classroom')->assertForbidden();
        $this->post('/my-bootcamps/'.$bootcamp->id)->assertRedirect(route('bootcamps.classroom', $bootcamp));
        $this->post('/my-bootcamps/'.$bootcamp->id)->assertRedirect();
        $this->assertDatabaseCount('bootcamp_purchases', 1);
        $this->get('/my-bootcamps/'.$bootcamp->id.'/classroom')
            ->assertOk()->assertSee('private-meeting')->assertSee('private-workbook');

        $this->actingAs($other)->post('/my-bootcamps/'.$bootcamp->id)
            ->assertSessionHasErrors('bootcamp');
        $this->assertDatabaseCount('bootcamp_purchases', 1);
        $this->assertDatabaseHas('audit_events', ['action' => 'bootcamps.published']);
    }

    public function test_paid_bootcamps_are_never_granted_free_access(): void
    {
        $instructor = $this->actor('instructor', 'teacher@edumia.test');
        $admin = $this->actor('admin', 'admin@edumia.test');
        $learner = $this->actor('student', 'learner@edumia.test');

        $this->actingAs($instructor)->post('/instructor/bootcamps', $this->cohort(10, 99))
            ->assertRedirect();
        $bootcamp = Bootcamp::firstOrFail();
        $this->post('/instructor/bootcamps/'.$bootcamp->id.'/modules', [
            'title' => 'A module', 'sort_order' => 0,
        ])->assertRedirect();

        $this->actingAs($admin)->patch('/admin/bootcamps/'.$bootcamp->id.'/publication', [
            'published' => 1,
        ])->assertSessionHasErrors('bootcamp');
        $this->actingAs($learner)->post('/my-bootcamps/'.$bootcamp->id)
            ->assertNotFound();
        $this->assertDatabaseCount('bootcamp_purchases', 0);
    }

    public function test_other_instructor_cannot_edit_or_add_content_to_bootcamp(): void
    {
        $owner = $this->actor('instructor', 'owner@edumia.test');
        $other = $this->actor('instructor', 'other@edumia.test');
        $student = $this->actor('student', 'student@edumia.test');
        $this->actingAs($owner)->post('/instructor/bootcamps', $this->cohort())->assertRedirect();
        $bootcamp = Bootcamp::firstOrFail();

        $this->actingAs($other)->get('/instructor/bootcamps/'.$bootcamp->id.'/edit')->assertForbidden();
        $this->post('/instructor/bootcamps/'.$bootcamp->id.'/modules', [
            'title' => 'Unauthorized module', 'sort_order' => 0,
        ])->assertForbidden();

        $this->actingAs($student)->post('/instructor/bootcamps', $this->cohort())->assertForbidden();
        $this->assertDatabaseCount('bootcamp_modules', 0);
    }
}
