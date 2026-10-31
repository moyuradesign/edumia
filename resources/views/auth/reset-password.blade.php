@extends('layouts.auth')
@section('title', 'New password')
@section('content')
<div class="auth-heading">
    <span class="auth-eyebrow">SECURE RECOVERY</span>
    <h1 id="auth-heading">Set a new password</h1>
    <p>Choose a strong password to protect your account.</p>
</div>
<form class="auth-form" action="{{ route('password.store') }}" method="post">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <x-auth-field name="email" label="Email address" type="email" icon="mail"
                  :value="old('email', $email)" autocomplete="email" required maxlength="190"/>
    <x-auth-field name="password" label="New password" type="password" icon="lock"
                  autocomplete="new-password" minlength="12" required
                  help="12+ characters with uppercase, lowercase, number and symbol."/>
    <x-auth-field name="password_confirmation" label="Confirm password" type="password" icon="lock"
                  autocomplete="new-password" minlength="12" required/>
    <button class="auth-submit" type="submit">
        <span>Reset password</span>
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
    </button>
</form>
<p class="auth-switch"><a href="{{ route('login') }}">Back to sign in</a></p>
@endsection
