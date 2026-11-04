<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TutorBooking;
use App\Models\TutorOffering;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TutorBookingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $email, string $role = 'student'): User
    {
        $user = User::query()->create(['name' => ucfirst(explode('@', $email)[0]),
            'email' => $email, 'password' => 'SecurePass#123']);
        $user->forceFill(['role' => $role, 'status' => 'active'])->save();
        return $user;
    }

    public function test_free_availability_booking_confirmation_and_conflict_protection(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-05 01:00:00', 'UTC'));
        $tutor = $this->actor('tutor@example.test', 'instructor');
        $learner = $this->actor('learner@example.test');
        $other = $this->actor('other@example.test');

        $this->actingAs($tutor)->post(route('instructor.tutors.offer'), [
            'subject_name' => 'Business English',
        ])->assertRedirect();
        $offering = TutorOffering::query()->firstOrFail();
        $start = CarbonImmutable::parse('2026-11-06 11:00:00', 'UTC');

        $this->post(route('instructor.tutors.schedule'), [
            'weekday' => $start->dayOfWeekIso,
            'start_time' => '09:00', 'end_time' => '17:00',
            'timezone' => 'UTC',
        ])->assertRedirect();

        $this->get(route('instructor.tutors.index'))->assertOk()->assertSee('Business English');
        $this->actingAs($learner)->get(route('tutors.index'))->assertOk()->assertSee('Business English');
        $this->get(route('tutors.show', $offering))->assertOk()->assertSee('Request free booking');

        $this->post(route('tutors.book', $offering), [
            'scheduled_at' => '2026-11-06T11:00',
        ])->assertRedirect(route('tutor.bookings.index'));
        $booking = TutorBooking::query()->firstOrFail();
        $this->assertSame('pending', $booking->status);
        $this->assertSame('0.00', $booking->fee);

        $this->actingAs($other)->post(route('tutors.book', $offering), [
            'scheduled_at' => '2026-11-06T11:00',
        ])->assertSessionHasErrors('scheduled_at');
        $this->assertDatabaseCount('tutor_bookings', 1);
        $this->get(route('tutor.bookings.index'))->assertOk()->assertDontSee('Business English');

        $this->actingAs($tutor)->patch(route('instructor.tutors.booking.update', $booking), [
            'status' => 'confirmed', 'meeting_url' => 'https://meet.example.com/office-hours',
        ])->assertRedirect();
        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->actingAs($learner)->get(route('tutor.bookings.index'))
            ->assertOk()->assertSee('https://meet.example.com/office-hours');

        $this->actingAs($other)->patch(route('tutor.bookings.cancel', $booking))->assertForbidden();
        $this->actingAs($learner)->patch(route('tutor.bookings.cancel', $booking))->assertRedirect();
        $this->assertSame('cancelled', $booking->fresh()->status);

        $this->actingAs($other)->post(route('tutors.book', $offering), [
            'scheduled_at' => '2026-11-06T11:00',
        ])->assertRedirect();
        $this->assertDatabaseCount('tutor_bookings', 2);
    }

    public function test_paid_offerings_self_booking_and_foreign_schedule_changes_are_denied(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-05 01:00:00', 'UTC'));
        $teacher = $this->actor('teacher@example.test', 'instructor');
        $student = $this->actor('student@example.test');
        $different = $this->actor('different@example.test', 'instructor');

        $this->actingAs($teacher)->post(route('instructor.tutors.offer'), [
            'subject_name' => 'Foundations of Mathematics',
        ])->assertRedirect();
        $offering = TutorOffering::query()->firstOrFail();

        $start = CarbonImmutable::parse('2026-11-06 14:00:00', 'UTC');
        $this->post(route('instructor.tutors.schedule'), [
            'weekday' => $start->dayOfWeekIso,
            'start_time' => '12:00', 'end_time' => '17:00', 'timezone' => 'UTC',
        ])->assertRedirect();
        $schedule = \App\Models\TutorSchedule::query()->firstOrFail();

        $this->actingAs($student)->post(route('tutors.book', $offering), [
            'scheduled_at' => '2026-11-06T09:00',
        ])->assertSessionHasErrors('scheduled_at');
        $this->actingAs($teacher)->post(route('tutors.book', $offering), [
            'scheduled_at' => '2026-11-06T14:00',
        ])->assertStatus(422);
        $this->actingAs($different)->patch(route('instructor.tutors.schedule.remove', $schedule))
            ->assertForbidden();
        $offering->update(['rate_per_hour' => '20.00']);
        $this->actingAs($student)->post(route('tutors.book', $offering), [
            'scheduled_at' => '2026-11-06T14:00',
        ])->assertStatus(422);
        $this->assertDatabaseCount('tutor_bookings', 0);
    }
}
