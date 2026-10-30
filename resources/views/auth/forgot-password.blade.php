@extends('layouts.auth')
@section('title','Forgot password')
@section('content')<h1>Reset your password</h1><p>Enter your email address to request a password-reset link.</p>
@if(session('success'))<div class="flash">{{ session('success') }}</div>@endif
<form class="stack-form" action="{{ route('password.email') }}" method="post">@csrf
<label>Email<input type="email" name="email" required value="{{ old('email') }}" autocomplete="email"></label>
<button class="btn btn-primary">Send reset link</button></form><p><a href="{{ route('login') }}">Return to login</a></p>
@endsection
