<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Models\Bootcamp;
use App\Models\BootcampPurchase;
use App\Services\Learning\BootcampService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class BootcampEnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        return view('student.bootcamps.index', [
            'registrations' => BootcampPurchase::query()->where('user_id', $request->user()->getKey())
                ->where('status', 'completed')->with('bootcamp')->latest()->paginate(20),
        ]);
    }

    public function enroll(Request $request, Bootcamp $bootcamp, BootcampService $service): RedirectResponse
    {
        $service->enroll($request->user(), $bootcamp);

        return to_route('bootcamps.classroom', $bootcamp)->with('success', 'Successfully enrolled.');
    }

    public function classroom(Request $request, Bootcamp $bootcamp, BootcampService $service): View
    {
        $service->assertCanAttend($request->user(), $bootcamp);

        return view('student.bootcamps.classroom', [
            'bootcamp' => $bootcamp->load(['modules.resources', 'sessions']),
        ]);
    }
}
