<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Application;
use App\Models\CommerceRefundRequest;
use App\Models\Contact;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Review;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $start = now()->startOfDay()->subDays(6);
        $byDay = Enrollment::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')->pluck('total', 'day');

        $enrollmentTrend = [];
        for ($offset = 6; $offset >= 0; $offset--) {
            $date = now()->startOfDay()->subDays($offset);
            $enrollmentTrend[] = [
                'day' => $date->format('D'),
                'date' => $date->toDateString(),
                'total' => (int) ($byDay[$date->toDateString()] ?? 0),
            ];
        }
        $maxEnrollments = max(1, ...array_column($enrollmentTrend, 'total'));

        $search = $request->query('member_q');
        $memberSearch = is_string($search) ? mb_substr(trim($search), 0, 80) : '';
        $memberQuery = User::query()->latest();
        if ($memberSearch !== '') {
            $memberQuery->where(fn ($query) =>
                $query->where('name', 'like', '%'.$memberSearch.'%')
                    ->orWhere('email', 'like', '%'.$memberSearch.'%'));
        }

        return view('dashboard.admin', [
            'users' => User::query()->count(),
            'instructors' => User::query()->where('role', 'instructor')->count(),
            'courses' => Course::query()->count(),
            'publishedCourses' => Course::published()->count(),
            'students' => Enrollment::query()->distinct('user_id')->count('user_id'),
            'totalEnrollments' => Enrollment::query()->count(),
            'paidOrders' => Order::query()->whereNotNull('paid_at')->count(),
            'pendingCoursesCount' => Course::query()->where('status', 'pending')->count(),
            'pendingReviewsCount' => Review::query()->where('status', 'pending')->count(),
            'pendingApplicationsCount' => Application::query()->where('type', 'instructor')->where('status', 'pending')->count(),
            'pendingRefundsCount' => CommerceRefundRequest::query()->where('status', 'pending')->count(),
            'newContactsCount' => Contact::query()->where('status', 'new')->count(),
            'openTicketsCount' => Ticket::query()->whereHas('status', fn ($query) => $query->where('is_closed', false))->count(),
            'pending' => Course::query()->where('status', 'pending')->with('instructor')->latest()->limit(6)->get(),
            'recentCourses' => Course::query()->with('instructor')->latest()->limit(7)->get(),
            'recentOrders' => Order::query()->with('user')->latest()->limit(5)->get(),
            'members' => $memberQuery->limit(10)->get(),
            'memberSearch' => $memberSearch,
            'enrollmentTrend' => $enrollmentTrend,
            'maxEnrollments' => $maxEnrollments,
        ]);
    }
}
