<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Courses;

use App\Models\Course;
use App\Models\TeamTrainingPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TeamTrainingController extends Controller
{
    public function index(): View
    {
        return view('admin.courses.teams.index', [
            'courses' => Course::query()->published()->where('price', 0)
                ->orderBy('title')->get(['id','title']),
            'packages' => TeamTrainingPackage::query()->with('course')
                ->withCount('purchases')->latest()->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'name' => ['required', 'string', 'min:3', 'max:190'],
            'description' => ['nullable', 'string', 'max:5000'],
            'seats' => ['required', 'integer', 'between:1,200'],
        ]);
        $course = Course::query()->published()->where('price', 0)->findOrFail($data['course_id']);
        TeamTrainingPackage::query()->create([
            'course_id' => $course->getKey(),
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'seats' => $data['seats'],
            'price' => 0,
            'status' => 'active',
        ]);

        return to_route('admin.team-training.index')->with('success', 'Free team package published.');
    }

    public function status(Request $request, TeamTrainingPackage $package): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:active,inactive']]);
        $package->update(['status' => $data['status']]);

        return to_route('admin.team-training.index')->with('success', 'Team package availability updated.');
    }
}
