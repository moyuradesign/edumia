<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Helpers\Localization;
use App\Models\AuditEvent;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $member = $request->user()->loadCount(['enrollments', 'courses']);
        return view('auth.preferences', [
            'member' => $member,
            'languages' => Localization::LANGUAGES,
            'supportTicketsCount' => Ticket::query()->where('requester_id', $member->getKey())->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150', 'regex:/\\S/u'],
            'locale' => ['required', Rule::in(array_keys(Localization::LANGUAGES))],
        ]);

        $member = $request->user();
        DB::transaction(function () use ($member, $data): void {
            $before = ['name' => $member->name, 'locale' => $member->locale];
            $member->fill(['name' => trim($data['name']), 'locale' => $data['locale']]);
            if (! $member->isDirty(['name', 'locale'])) {
                return;
            }
            $member->save();
            foreach (['name', 'locale'] as $field) {
                if ($before[$field] === $member->{$field}) {
                    continue;
                }
                AuditEvent::query()->create([
                    'actor_id' => $member->getKey(),
                    'subject_type' => $member::class,
                    'subject_id' => $member->getKey(),
                    'action' => 'account.'.$field.'_updated',
                    'previous_values' => [$field => $before[$field]],
                    'new_values' => [$field => $member->{$field}],
                ]);
            }
        }, 3);

        return to_route('account.preferences.edit')->with('success', 'Account preferences saved.');
    }
}
