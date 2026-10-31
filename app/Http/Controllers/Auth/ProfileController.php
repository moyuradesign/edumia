<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Helpers\Localization;
use App\Models\AuditEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.preferences', [
            'member' => $request->user(),
            'languages' => Localization::LANGUAGES,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'locale' => ['required', Rule::in(array_keys(Localization::LANGUAGES))],
        ]);

        $member = $request->user();
        $previousLocale = $member->locale;
        $member->fill(['name' => trim($data['name']), 'locale' => $data['locale']]);
        $member->save();

        if ($previousLocale !== $member->locale) {
            AuditEvent::query()->create([
                'actor_id' => $member->getKey(),
                'subject_type' => $member::class,
                'subject_id' => $member->getKey(),
                'action' => 'account.locale_updated',
                'previous_values' => ['locale' => $previousLocale],
                'new_values' => ['locale' => $member->locale],
            ]);
        }

        return to_route('account.preferences.edit')->with('success', 'Account preferences saved.');
    }
}
