<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Instructor\DashboardController as InstructorDashboard;
use App\Http\Controllers\Student\DashboardController as StudentDashboard;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user?->isActive(), 403);

        if ($user->isAdmin()) {
            return app(AdminDashboard::class)($request);
        }

        if ($user->canTeach()) {
            return app(InstructorDashboard::class)($request);
        }

        return app(StudentDashboard::class)($request);
    }
}
