<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Requests\Admin\RoleRequest;
use App\Models\AuditEvent;
use App\Models\Role;
use App\Models\User;
use App\Services\Identity\RoleManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $rawSearch = $request->query('member_q');
        $memberSearch = is_string($rawSearch) ? mb_substr(trim($rawSearch), 0, 100) : '';

        return view('admin.users.roles.index', [
            'roles' => Role::query()->with('permissions')->withCount('users')->orderBy('name')->get(),
            'permissions' => RoleManagementService::DELEGATABLE,
            'memberSearch' => $memberSearch,
            'memberOptions' => User::query()->where('status', 'active')->where('role', '!=', 'admin')
                ->when($memberSearch !== '', fn ($q) => $q->where(fn ($inner) =>
                    $inner->where('name', 'like', '%'.$memberSearch.'%')
                        ->orWhere('email', 'like', '%'.$memberSearch.'%')))
                ->orderBy('name')->limit(25)->get(['id', 'name', 'email']),
            'roleHistory' => AuditEvent::query()->with('actor')
                ->whereIn('action', ['roles.created', 'roles.updated', 'roles.assigned', 'roles.revoked'])
                ->latest('id')->paginate(15, ['*'], 'audit_page')->withQueryString(),
        ]);
    }

    public function store(RoleRequest $request, RoleManagementService $service): RedirectResponse
    {
        $service->save($request->user(), $request->validated());

        return to_route('admin.roles.index')->with('success', 'Custom role created.');
    }

    public function update(RoleRequest $request, Role $role, RoleManagementService $service): RedirectResponse
    {
        $service->save($request->user(), $request->validated(), $role);

        return to_route('admin.roles.index')->with('success', 'Custom role updated.');
    }

    public function assign(Request $request, Role $role, RoleManagementService $service): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'grant' => ['required', 'boolean'],
        ]);

        $service->assign($request->user(), $role, User::findOrFail($data['user_id']), (bool) $data['grant']);

        return to_route('admin.roles.index')->with('success', 'Staff role assignment updated.');
    }
}
