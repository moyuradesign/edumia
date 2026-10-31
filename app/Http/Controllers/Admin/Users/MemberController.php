<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Users;

use App\Http\Requests\Admin\UpdateMemberRequest;
use App\Models\User;
use App\Services\Identity\MemberManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class MemberController extends Controller
{
    public function __invoke(
        UpdateMemberRequest $request,
        User $user,
        MemberManagementService $members
    ): RedirectResponse {
        $members->update($request->user(), $user, $request->validated());

        return back()->with('success', 'Member updated.');
    }
}
