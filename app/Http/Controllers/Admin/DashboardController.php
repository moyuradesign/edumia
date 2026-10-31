<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('dashboard.admin', [
            'users' => User::count(),
            'instructors' => User::where('role', 'instructor')->count(),
            'courses' => Course::count(),
            'students' => Enrollment::distinct('user_id')->count('user_id'),
            'pending' => Course::where('status', 'pending')->latest()->get(),
            'recentCourses' => Course::with('instructor')->latest()->limit(10)->get(),
            'members' => User::latest()->limit(15)->get(),
        ]);
    }
}
