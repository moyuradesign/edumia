@extends('layouts.panel')
@section('title', 'General settings')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">PLATFORM SYSTEM</span><h1>General settings</h1><p>Manage the main platform details and student registration.</p></div></div>
<section class="panel-card">
    <form class="stack-form" method="post" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')
        <label>Platform name <input name="site_name" maxlength="120" required value="{{ old('site_name', $settings['site_name']) }}"></label>
        <label>Default language
            <select name="default_locale" required>
                @foreach (\App\Helpers\Localization::LANGUAGES as $code => $name)
                    <option value="{{ $code }}" @selected(old('default_locale', $settings['default_locale']) === $code)>{{ $name }}</option>
                @endforeach
            </select>
        </label>
        <p class="subtle">Visitors can switch among the eight languages. Untranslated course content remains in its original language.</p>
        <label>Display timezone (IANA)
            <input name="site_timezone" list="edumia-timezone-suggestions" maxlength="80"
                value="{{ old('site_timezone', $settings['site_timezone']) }}" required
                placeholder="UTC">
        </label>
        <datalist id="edumia-timezone-suggestions">
            @foreach (['UTC', 'Asia/Phnom_Penh', 'Asia/Bangkok', 'Asia/Tokyo', 'Asia/Shanghai', 'Asia/Kolkata', 'Europe/London', 'Europe/Paris', 'America/New_York', 'America/Los_Angeles', 'Australia/Sydney'] as $zone)
                <option value="{{ $zone }}"></option>
            @endforeach
        </datalist>
        <p class="subtle">Use an IANA timezone such as Europe/Paris. Displayed dates use this setting; stored times and payment timestamps are never changed. Prices always retain their original currency.</p>
        <label>Support email <input name="support_email" type="email" maxlength="190" value="{{ old('support_email', $settings['support_email']) }}"></label>
        <label>Homepage notice <textarea name="homepage_notice" rows="3" maxlength="500">{{ old('homepage_notice', $settings['homepage_notice']) }}</textarea></label>
        <label class="checkline"><input type="checkbox" name="allow_registration" value="1" @checked(old('allow_registration', $settings['allow_registration']) == '1')> Allow new student registrations</label>
        <p class="subtle">Disabling registration blocks both the registration form and account submission. Existing users can still sign in.</p>
        <div><button class="btn btn-primary" type="submit">Save settings</button></div>
    </form>
</section>
@endsection
