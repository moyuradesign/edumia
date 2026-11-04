<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Models\TutorOffering;
use App\Services\Learning\TutorBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TutorDirectoryController extends Controller
{
    private function availableOfferings()
    {
        return TutorOffering::query()
            ->where('rate_per_hour', 0)
            ->whereHas('tutor', fn ($query) =>
                $query->whereIn('role', ['instructor', 'admin'])->where('status', 'active'))
            ->whereHas('tutor', fn ($query) =>
                $query->whereHas('tutorSchedules', fn ($schedules) =>
                    $schedules->where('active', true)));
    }

    public function index(Request $request): View
    {
        $search = $request->query('q');
        $term = is_string($search) ? trim(mb_substr($search, 0, 80)) : '';
        $query = $this->availableOfferings()->with(['subject', 'tutor:id,name']);

        if ($term !== '') {
            $escaped = addcslashes($term, '%_\\');
            $query->where(static fn ($builder) =>
                $builder->whereHas('subject', fn ($sub) => $sub->where('name', 'like', '%'.$escaped.'%'))
                    ->orWhereHas('tutor', fn ($tutor) => $tutor->where('name', 'like', '%'.$escaped.'%')));
        }

        return view('frontend.default.tutors.index', [
            'offerings' => $query->orderByDesc('id')->paginate(15)->withQueryString(),
            'search' => $term,
            'locale' => \App\Helpers\Localization::resolve(),
        ]);
    }

    public function show(TutorOffering $offering): View
    {
        $offering = $this->availableOfferings()->with(['subject', 'tutor:id,name'])
            ->findOrFail($offering->getKey());

        return view('frontend.default.tutors.show', [
            'offering' => $offering,
            'schedules' => \App\Models\TutorSchedule::query()
                ->where('tutor_id', $offering->tutor_id)->where('active', true)
                ->orderBy('weekday')->orderBy('start_time')->get(),
            'locale' => \App\Helpers\Localization::resolve(),
        ]);
    }

    public function book(Request $request, TutorOffering $offering, TutorBookingService $bookings): RedirectResponse
    {
        $data = $request->validate([
            'scheduled_at' => ['required', 'date_format:Y-m-d\TH:i'],
        ]);
        $bookings->reserve($request->user(), $offering, $data['scheduled_at']);

        return to_route('tutor.bookings.index')->with('success', 'Booking requested. Your tutor will confirm the appointment.');
    }
}
