<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Services\Notifications\NotificationService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function edit()
    {
        return view('auth.change-password');
    }

    public function update(Request $request)
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()]]);
        $request->user()->forceFill(['password' => Hash::make($data['password'])])->save();
        $request->session()->regenerate();
        app(NotificationService::class)->deliver($request->user(), 'account_security',
            'Account password changed',
            'Your account password was changed. If you did not do this, contact Edumia support.',
            '/account/password');

        return redirect()->route('dashboard')->with('success', 'Password updated.');
    }
}
