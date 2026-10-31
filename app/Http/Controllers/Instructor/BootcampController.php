<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Http\Requests\Instructor\BootcampRequest;
use App\Models\Bootcamp;
use App\Models\BootcampModule;
use App\Services\Learning\BootcampService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class BootcampController extends Controller
{
    public function index(Request $request): View
    {
        return view('instructor.bootcamps.index', [
            'bootcamps' => Bootcamp::query()
                ->when(! $request->user()->isAdmin(),
                    fn ($q) => $q->where('instructor_id', $request->user()->getKey()))
                ->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('instructor.bootcamps.form', ['bootcamp' => new Bootcamp]);
    }

    public function store(BootcampRequest $request, BootcampService $service): RedirectResponse
    {
        $bootcamp = $service->save($request->user(), $request->validated());

        return to_route('instructor.bootcamps.edit', $bootcamp)->with('success', 'Cohort created in draft.');
    }

    public function edit(Request $request, Bootcamp $bootcamp, BootcampService $service): View
    {
        $service->assertOwner($request->user(), $bootcamp);

        return view('instructor.bootcamps.form', [
            'bootcamp' => $bootcamp->load(['modules.resources', 'sessions']),
        ]);
    }

    public function update(
        BootcampRequest $request, Bootcamp $bootcamp, BootcampService $service
    ): RedirectResponse {
        $service->save($request->user(), $request->validated(), $bootcamp);

        return to_route('instructor.bootcamps.edit', $bootcamp)->with('success', 'Cohort saved as draft.');
    }

    public function module(Request $request, Bootcamp $bootcamp, BootcampService $service): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);
        $service->module($request->user(), $bootcamp, $data);

        return to_route('instructor.bootcamps.edit', $bootcamp)->with('success', 'Cohort module added.');
    }

    public function resource(
        Request $request, Bootcamp $bootcamp, BootcampModule $module, BootcampService $service
    ): RedirectResponse {
        $service->assertOwner($request->user(), $bootcamp);
        abort_unless((int) $module->bootcamp_id === (int) $bootcamp->getKey(), 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'external_url' => ['required', 'url', 'starts_with:https://', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);
        $module->resources()->create($data);

        return to_route('instructor.bootcamps.edit', $bootcamp)->with('success', 'Module resource added.');
    }

    public function session(Request $request, Bootcamp $bootcamp, BootcampService $service): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'meeting_url' => ['required', 'url', 'starts_with:https://', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);
        $service->session($request->user(), $bootcamp, $data);

        return to_route('instructor.bootcamps.edit', $bootcamp)->with('success', 'Session scheduled.');
    }
}
