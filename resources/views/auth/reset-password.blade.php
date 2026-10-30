@extends('layouts.auth')
@section('title','New password')
@section('content')<h1>Choose a new password</h1><form class="stack-form" method="post" action="{{ route('password.store') }}">@csrf
<input type="hidden" name="token" value="{{ $token }}">
<label>Email<input type="email" name="email" required value="{{ old('email',$email) }}"></label>
<label>New password<input type="password" name="password" required minlength="12"></label>
<label>Confirm password<input type="password" name="password_confirmation" required minlength="12"></label>
<button class="btn btn-primary">Reset password</button></form>
@endsection
