<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
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

        return redirect()->route('dashboard')->with('success', 'Password updated.');
    }
}
