@extends('layouts.panel')
@section('title', 'General settings')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">PLATFORM SYSTEM</span><h1>General settings</h1><p>Manage the main platform details and student registration.</p></div></div>
<section class="panel-card">
    <form class="stack-form" method="post" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')
        <label>Platform name <input name="site_name" maxlength="120" required value="{{ old('site_name', $settings['site_name']) }}"></label>
        <label>Support email <input name="support_email" type="email" maxlength="190" value="{{ old('support_email', $settings['support_email']) }}"></label>
        <label>Homepage notice <textarea name="homepage_notice" rows="3" maxlength="500">{{ old('homepage_notice', $settings['homepage_notice']) }}</textarea></label>
        <label class="checkline"><input type="checkbox" name="allow_registration" value="1" @checked(old('allow_registration', $settings['allow_registration']) == '1')> Allow new student registrations</label>
        <p class="subtle">Disabling registration blocks both the registration form and account submission. Existing users can still sign in.</p>
        <div><button class="btn btn-primary" type="submit">Save settings</button></div>
    </form>
</section>
@endsection
