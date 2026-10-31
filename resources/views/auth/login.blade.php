@extends('layouts.auth')
@section('title', 'Sign in')
@section('content')
<div class="auth-heading">
    <span class="auth-eyebrow">WELCOME BACK</span>
    <h1 id="auth-heading">Sign in to your account</h1>
    <p>Continue learning, teaching, and growing.</p>
</div>
<form class="auth-form" action="{{ route('login.store') }}" method="post">
    @csrf
    <x-auth-field name="email" label="Email address" type="email" icon="mail"
                  :value="old('email')" autocomplete="username" required maxlength="190"/>
    <x-auth-field name="password" label="Password" type="password" icon="lock"
                  autocomplete="current-password" required/>
    <div class="auth-options">
        <label class="auth-remember"><input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Remember me</span></label>
        <a href="{{ route('password.request') }}">Forgot password?</a>
    </div>
    <button class="auth-submit" type="submit">
        <span>Sign in</span>
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
    </button>
</form>
<p class="auth-switch">New to {{ $siteName }}? <a href="{{ route('register') }}">Create an account</a></p>
@endsection
