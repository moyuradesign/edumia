<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class MemberDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $requestedSearch = $request->query('q');
        $search = is_string($requestedSearch) ? mb_substr(trim($requestedSearch), 0, 100) : '';

        $requestedRole = $request->query('role');
        $role = is_string($requestedRole) && in_array($requestedRole, UserRole::values(), true)
            ? $requestedRole : 'all';

        $requestedStatus = $request->query('status');
        $status = is_string($requestedStatus) && in_array($requestedStatus, AccountStatus::values(), true)
            ? $requestedStatus : 'all';

        $members = User::query()
            ->withCount(['courses', 'enrollments'])
            ->when($search !== '', fn ($query) => $query->where(fn ($matching) =>
                $matching->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')))
            ->when($role !== 'all', fn ($query) => $query->where('role', $role))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.members.index', [
            'members' => $members,
            'search' => $search,
            'role' => $role,
            'status' => $status,
            'totalMembers' => User::query()->count(),
            'activeMembers' => User::query()->where('status', AccountStatus::Active->value)->count(),
            'suspendedMembers' => User::query()->where('status', AccountStatus::Suspended->value)->count(),
            'totalInstructors' => User::query()->where('role', UserRole::Instructor->value)->count(),
        ]);
    }
}
