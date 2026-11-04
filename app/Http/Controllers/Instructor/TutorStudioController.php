<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Models\TutorBooking;
use App\Models\TutorOffering;
use App\Models\TutorSchedule;
use App\Models\TutorSubject;
use App\Services\Learning\TutorBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TutorStudioController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->canTeach(), 403);
        $id = $request->user()->getKey();

        return view('instructor.tutors.index', [
            'offerings' => TutorOffering::query()->where('tutor_id', $id)
                ->with('subject')->orderByDesc('id')->get(),
            'schedules' => TutorSchedule::query()->where('tutor_id', $id)
                ->orderBy('weekday')->orderBy('start_time')->get(),
            'bookings' => TutorBooking::query()->where('tutor_id', $id)
                ->with(['learner:id,name', 'subject:id,name'])
                ->latest('scheduled_at')->paginate(20),
        ]);
    }

    public function offer(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canTeach(), 403);
        $data = $request->validate(['subject_name' => ['required', 'string', 'min:3', 'max:100']]);
        $name = trim($data['subject_name']);
        $slug = Str::slug($name);
        if ($slug === '') {
            $slug = 'subject-'.substr(hash('sha256', mb_strtolower($name)), 0, 20);
        }

        DB::transaction(static function () use ($request, $name, $slug): void {
            $subject = TutorSubject::query()->firstOrCreate(['slug' => $slug], ['name' => $name]);
            TutorOffering::query()->firstOrCreate(
                ['tutor_id' => $request->user()->getKey(), 'subject_id' => $subject->getKey()],
                ['rate_per_hour' => 0, 'currency_code' => 'USD']
            );
        }, attempts: 3);

        return to_route('instructor.tutors.index')->with('success', 'Free tutoring subject added.');
    }

    public function schedule(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canTeach(), 403);
        $data = $request->validate([
            'weekday' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'timezone' => ['required', 'timezone', 'max:64'],
        ]);

        // A single time zone per tutor prevents ambiguous booking forms.
        $existing = TutorSchedule::query()->where('tutor_id', $request->user()->getKey())->first();
        if ($existing && $existing->timezone !== $data['timezone']) {
            throw ValidationException::withMessages(['timezone' => 'Use the same time zone as your existing availability.']);
        }
        $start = $data['start_time'].':00';
        $end = $data['end_time'].':00';
        if (strtotime($end) - strtotime($start) < 3600) {
            throw ValidationException::withMessages(['end_time' => 'Availability must include at least one hour.']);
        }

        $overlap = TutorSchedule::query()->where('tutor_id', $request->user()->getKey())
            ->where('weekday', $data['weekday'])->where('active', true)
            ->where('start_time', '<', $end)->where('end_time', '>', $start)->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['start_time' => 'Availability overlaps an existing window.']);
        }
        TutorSchedule::query()->create([
            'tutor_id' => $request->user()->getKey(),
            'weekday' => $data['weekday'], 'start_time' => $start,
            'end_time' => $end, 'timezone' => $data['timezone'],
            'active' => true,
        ]);

        return to_route('instructor.tutors.index')->with('success', 'Availability published.');
    }

    public function removeSchedule(Request $request, TutorSchedule $schedule): RedirectResponse
    {
        abort_unless($request->user()?->canTeach() && $schedule->tutor_id === $request->user()->getKey(), 403);
        $schedule->update(['active' => false]);

        return to_route('instructor.tutors.index')->with('success', 'Availability stopped for future bookings.');
    }

    public function respond(Request $request, TutorBooking $booking, TutorBookingService $service): RedirectResponse
    {
        abort_unless($request->user()?->canTeach() && $booking->tutor_id === $request->user()->getKey(), 403);
        $data = $request->validate([
            'status' => ['required', 'in:confirmed,cancelled,completed'],
            'meeting_url' => ['nullable', 'url:http,https', 'starts_with:https://', 'max:255'],
        ]);
        $service->changeStatus($request->user(), $booking, $data['status'], $data['meeting_url'] ?? null);

        return to_route('instructor.tutors.index')->with('success', 'Booking updated.');
    }
}
