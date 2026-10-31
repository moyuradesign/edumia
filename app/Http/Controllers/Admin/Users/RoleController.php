<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Requests\Admin\RoleRequest;
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
    public function index(): View
    {
        return view('admin.users.roles.index', [
            'roles' => Role::query()->with('permissions')->withCount('users')->orderBy('name')->get(),
            'permissions' => RoleManagementService::DELEGATABLE,
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
