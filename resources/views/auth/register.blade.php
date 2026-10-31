@extends('layouts.auth')
@section('title', 'Create account')
@section('content')
<div class="auth-heading">
    <span class="auth-eyebrow">JOIN THE COMMUNITY</span>
    <h1 id="auth-heading">Create your account</h1>
    <p>Your next learning experience starts here.</p>
</div>
<form class="auth-form" action="{{ route('register.store') }}" method="post">
    @csrf
    <x-auth-field name="name" label="Full name" icon="user" :value="old('name')"
                  autocomplete="name" required maxlength="150"/>
    <x-auth-field name="email" label="Email address" type="email" icon="mail"
                  :value="old('email')" autocomplete="email" required maxlength="190"/>
    <x-auth-field name="locale" label="Preferred language" type="select" icon="globe" required>
        @foreach (\App\Helpers\Localization::LANGUAGES as $code => $name)
            <option value="{{ $code }}" @selected(old('locale', request()->query('locale', app(\App\Services\System\SettingsRepository::class)->general()['default_locale'])) === $code)>{{ $name }}</option>
        @endforeach
    </x-auth-field>
    <x-auth-field name="password" label="Password" type="password" icon="lock" required
                  autocomplete="new-password" minlength="12"
                  help="12+ characters with uppercase, lowercase, number and symbol."/>
    <x-auth-field name="password_confirmation" label="Confirm password" type="password" icon="lock"
                  autocomplete="new-password" required minlength="12"/>
    <button class="auth-submit" type="submit">
        <span>Create account</span>
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
    </button>
</form>
<p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
@endsection
