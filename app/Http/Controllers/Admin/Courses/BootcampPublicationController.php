<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Courses;

use App\Models\Bootcamp;
use App\Services\Learning\BootcampService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class BootcampPublicationController extends Controller
{
    public function index(): View
    {
        return view('admin.courses.bootcamps.index', [
            'bootcamps' => Bootcamp::query()->with('instructor')->withCount('modules')
                ->latest()->paginate(25),
        ]);
    }

    public function update(Request $request, Bootcamp $bootcamp, BootcampService $service): RedirectResponse
    {
        $data = $request->validate(['published' => ['required', 'boolean']]);
        $service->publish($request->user(), $bootcamp, (bool) $data['published']);

        return to_route('admin.bootcamps.index')->with('success', 'Publication status updated.');
    }
}
