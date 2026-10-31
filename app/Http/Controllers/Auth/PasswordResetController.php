<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function requestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email', 'max:190']]);
        Password::sendResetLink($request->only('email'));

        // Avoid disclosing whether a user exists.
        return back()->with('success', 'If this address is registered and email delivery is configured, a reset link will arrive.');
    }

    public function resetForm(string $token, Request $request)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate(['token' => ['required', 'string'], 'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()->symbols()]]);
        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET ? redirect()->route('login')->with('success', 'Password reset. Sign in now.')
         : back()->withErrors(['email' => 'This reset link is invalid or expired.']);
    }
}
