<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\System\SettingsRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class RegisteredUserController extends Controller
{
    public function create(SettingsRepository $settings): View
    {
        abort_unless($settings->registrationEnabled(), 403, 'Registration is currently closed.');

        return view('auth.register');
    }

    public function store(RegisterRequest $request, SettingsRepository $settings): RedirectResponse
    {
        abort_unless($settings->registrationEnabled(), 403, 'Registration is currently closed.');

        $data = $request->validated();

        // Student is the default database role. Never accept an incoming role.
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'locale' => $data['locale'] ?? $settings->general()['default_locale'],
        ]);

        // Load database defaults (active/student) into the model before
        // Auth::login caches the newly registered user for this session.
        $user->refresh();

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
