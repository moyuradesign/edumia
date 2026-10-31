<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\System;

use App\Http\Requests\Admin\GeneralSettingsRequest;
use App\Services\System\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class GeneralSettingsController extends Controller
{
    public function edit(SettingsRepository $settings): View
    {
        return view('admin.system.settings.general', ['settings' => $settings->general()]);
    }

    public function update(GeneralSettingsRequest $request, SettingsRepository $settings): RedirectResponse
    {
        $settings->updateGeneral($request->user(), $request->validated());

        return to_route('admin.settings.edit')->with('success', 'General settings saved.');
    }
}
