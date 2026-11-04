<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\TutorBooking;
use App\Models\TutorOffering;
use App\Models\TutorSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TutorBookingService
{
    public function reserve(User $learner, TutorOffering $offering, string $localStart): TutorBooking
    {
        abort_unless($learner->isActive(), 403);
        abort_if($learner->getKey() === $offering->tutor_id, 422, 'You cannot book yourself.');

        return DB::transaction(function () use ($learner, $offering, $localStart): TutorBooking {
            // Serializing reservations on the tutor row prevents double booking even
            // if concurrent requests target different subjects.
            $tutor = User::query()->lockForUpdate()->findOrFail($offering->tutor_id);
            abort_unless($tutor->canTeach(), 404);
            $offering = TutorOffering::query()->whereKey($offering->getKey())
                ->where('tutor_id', $tutor->getKey())->firstOrFail();

            // Tutor booking is free-only until a verified payment integration
            // supports this product. Never fabricate paid confirmation.
            abort_unless((float) $offering->rate_per_hour === 0.0, 422, 'Paid tutoring checkout is unavailable.');

            $schedules = TutorSchedule::query()->where('tutor_id', $tutor->getKey())
                ->where('active', true)->get();
            if ($schedules->isEmpty()) {
                throw ValidationException::withMessages(['scheduled_at' => 'No availability has been published.']);
            }
            $timezone = $schedules->first()->timezone;
            $start = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $localStart, $timezone);
            if ($start === false || $start->format('Y-m-d\TH:i') !== $localStart) {
                throw ValidationException::withMessages(['scheduled_at' => 'Choose a valid local appointment time.']);
            }
            if ($start->utc()->lessThan(now()->addHours(2))
                || $start->utc()->greaterThan(now()->addDays(60))) {
                throw ValidationException::withMessages(['scheduled_at' => 'Choose a time between two hours and 60 days from now.']);
            }
            if ($start->minute !== 0) {
                throw ValidationException::withMessages(['scheduled_at' => 'Bookings begin on the hour.']);
            }

            $end = $start->addHour();
            $withinAvailability = $schedules->contains(static function (TutorSchedule $schedule) use ($start, $end): bool {
                return (int) $schedule->weekday === $start->dayOfWeekIso
                    && $schedule->timezone === $start->timezoneName
                    && $start->format('H:i:s') >= $schedule->start_time
                    && $end->format('H:i:s') <= $schedule->end_time
                    && $end->isSameDay($start);
            });
            if (! $withinAvailability) {
                throw ValidationException::withMessages(['scheduled_at' => 'This time is outside the tutor’s available hours.']);
            }

            $utcStart = $start->utc();
            $utcEnd = $end->utc();
            $conflicts = TutorBooking::query()
                ->where('tutor_id', $tutor->getKey())
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereBetween('scheduled_at', [
                    $utcStart->subDay()->toDateTimeString(),
                    $utcEnd->toDateTimeString(),
                ])->get();

            foreach ($conflicts as $booking) {
                $busyStart = $booking->scheduled_at->utc();
                $busyEnd = $busyStart->addMinutes($booking->duration_minutes);
                if ($busyStart->lessThan($utcEnd) && $busyEnd->greaterThan($utcStart)) {
                    throw ValidationException::withMessages(['scheduled_at' => 'This slot has already been booked.']);
                }
            }

            return TutorBooking::query()->create([
                'tutor_id' => $tutor->getKey(),
                'learner_id' => $learner->getKey(),
                'subject_id' => $offering->subject_id,
                'scheduled_at' => $utcStart,
                'duration_minutes' => 60,
                'fee' => 0,
                'status' => 'pending',
            ]);
        }, attempts: 3);
    }

    public function changeStatus(User $actor, TutorBooking $booking, string $status, ?string $meetingUrl = null): TutorBooking
    {
        return DB::transaction(function () use ($actor, $booking, $status, $meetingUrl): TutorBooking {
            $tutor = User::query()->lockForUpdate()->findOrFail($booking->tutor_id);
            $booking = TutorBooking::query()->lockForUpdate()->findOrFail($booking->getKey());

            if ($actor->getKey() === $tutor->getKey() && $actor->canTeach()) {
                abort_unless(in_array($status, ['confirmed', 'cancelled', 'completed'], true), 422);
                abort_unless(
                    ($booking->status === 'pending' && in_array($status, ['confirmed', 'cancelled'], true))
                    || ($booking->status === 'confirmed' && in_array($status, ['completed', 'cancelled'], true)),
                    422
                );
                if ($status === 'completed') {
                    abort_unless($booking->scheduled_at->addMinutes(60)->isPast(), 422, 'The meeting has not ended.');
                }
            } elseif ($actor->getKey() === $booking->learner_id && $actor->isActive()) {
                abort_unless($status === 'cancelled'
                    && in_array($booking->status, ['pending', 'confirmed'], true), 403);
                $meetingUrl = null;
            } else {
                abort(403);
            }

            $booking->status = $status;
            if ($status === 'confirmed' && $meetingUrl !== null) {
                $booking->meeting_url = $meetingUrl;
            }
            if ($status === 'cancelled') {
                $booking->meeting_url = null;
            }
            $booking->save();

            return $booking;
        }, attempts: 3);
    }
}
