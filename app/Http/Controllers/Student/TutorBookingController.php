<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Models\TutorBooking;
use App\Services\Learning\TutorBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TutorBookingController extends Controller
{
    public function index(Request $request): View
    {
        return view('student.tutors.bookings', [
            'bookings' => TutorBooking::query()->where('learner_id', $request->user()->getKey())
                ->with(['tutor:id,name', 'subject:id,name'])
                ->orderByDesc('scheduled_at')->paginate(20),
        ]);
    }

    public function cancel(Request $request, TutorBooking $booking, TutorBookingService $service): RedirectResponse
    {
        abort_unless($booking->learner_id === $request->user()->getKey(), 403);
        $service->changeStatus($request->user(), $booking, 'cancelled');

        return to_route('tutor.bookings.index')->with('success', 'Your booking was cancelled.');
    }
}
