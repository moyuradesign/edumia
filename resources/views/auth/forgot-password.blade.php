@extends('layouts.auth')
@section('title', 'Forgot password')
@section('content')
<div class="auth-heading">
    <span class="auth-eyebrow">ACCOUNT RECOVERY</span>
    <h1 id="auth-heading">Forgot your password?</h1>
    <p>Enter your account email and we'll send a reset link if it's registered.</p>
</div>
<form class="auth-form" action="{{ route('password.email') }}" method="post">
    @csrf
    <x-auth-field name="email" label="Email address" type="email" icon="mail"
                  :value="old('email')" autocomplete="email" required maxlength="190"/>
    <button class="auth-submit" type="submit">
        <span>Send reset link</span>
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
    </button>
</form>
<p class="auth-switch"><a href="{{ route('login') }}">Back to sign in</a></p>
@endsection
