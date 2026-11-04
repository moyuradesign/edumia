<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Models\TeamPackageMember;
use App\Models\TeamPackagePurchase;
use App\Models\TeamTrainingPackage;
use App\Services\Learning\TeamTrainingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TeamTrainingController extends Controller
{
    public function index(Request $request): View
    {
        $id = $request->user()->getKey();

        return view('student.teams.index', [
            'ownedTeams' => TeamPackagePurchase::query()
                ->where('purchaser_id', $id)->where('status', 'active')
                ->with(['package.course', 'members.user:id,name'])
                ->latest()->paginate(12),
            'invitations' => TeamPackageMember::query()
                ->where('user_id', $id)->where('status', 'pending')
                ->whereHas('purchase', fn ($q) => $q->where('status', 'active'))
                ->with(['purchase.package.course', 'purchase.purchaser:id,name'])
                ->latest()->get(),
            'joinedTeams' => TeamPackageMember::query()
                ->where('user_id', $id)->where('status', 'active')
                ->with('purchase.package.course')
                ->latest()->get(),
        ]);
    }

    public function activate(
        Request $request,
        TeamTrainingPackage $package,
        TeamTrainingService $teams
    ): RedirectResponse {
        $data = $request->validate([
            'organization_name' => ['required', 'string', 'min:3', 'max:190'],
        ]);
        $teams->activate($request->user(), $package, trim($data['organization_name']));

        return to_route('team.workspaces.index')->with('success', 'Your free team training workspace is active.');
    }

    public function invite(Request $request, TeamPackagePurchase $team, TeamTrainingService $teams): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:190']]);
        $teams->invite($request->user(), $team, mb_strtolower(trim($data['email'])));

        return to_route('team.workspaces.index')->with('success', 'Invitation created. The member can accept it from their account.');
    }

    public function respond(
        Request $request,
        TeamPackageMember $invitation,
        TeamTrainingService $teams
    ): RedirectResponse {
        $data = $request->validate(['decision' => ['required', 'in:accept,decline']]);
        $teams->respond($request->user(), $invitation, $data['decision'] === 'accept');

        return to_route('team.workspaces.index')->with('success', 'Invitation response saved.');
    }
}
