@extends('layouts.panel')
@section('title','Change password')
@section('content')<section class="panel-card"><h1>Change password</h1><form class="stack-form" method="post" action="{{ route('password.update') }}">@csrf @method('PUT')
<label>Current password<input name="current_password" type="password" required autocomplete="current-password"></label>
<label>New password<input name="password" type="password" required minlength="12" autocomplete="new-password"></label>
<label>Confirm new password<input name="password_confirmation" type="password" required minlength="12" autocomplete="new-password"></label>
<button class="btn btn-primary">Update password</button></form></section>
@endsection
