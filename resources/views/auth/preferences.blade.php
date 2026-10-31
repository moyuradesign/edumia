@extends('layouts.panel')
@section('title', 'Account preferences')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">MY ACCOUNT</span><h1>Account preferences</h1>
<p>Choose your display name and preferred language. Your email address and account permissions are managed separately.</p></div></div>
<section class="panel-card">
<form class="stack-form" method="post" action="{{ route('account.preferences.update') }}">
@csrf @method('PUT')
<label>Display name<input name="name" value="{{ old('name', $member->name) }}" required maxlength="150" autocomplete="name"></label>
<label>Preferred language
<select name="locale" required>
@foreach ($languages as $code => $name)
<option value="{{ $code }}" @selected(old('locale', $member->locale) === $code)>{{ $name }}</option>
@endforeach
</select></label>
<p class="subtle">Public navigation is available in eight languages. Some course content and management screens are presented in their original language.</p>
<button type="submit" class="btn btn-primary">Save preferences</button>
</form>
</section>
@endsection
