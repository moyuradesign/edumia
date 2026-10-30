@extends('layouts.auth')
@section('title','Sign in')
@section('content')
<h1>Welcome back</h1><p>Sign in to continue your learning.</p>
<form class="stack-form" action="{{ route('login.store') }}" method="post">@csrf
<label>Email address<input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required maxlength="190"></label>
<label>Password<input type="password" name="password" autocomplete="current-password" required></label>
<label class="checkline"><input type="checkbox" name="remember" value="1"> Remember me</label>
<button class="btn btn-primary" type="submit">Sign in</button></form>
<p><a href="{{ route('password.request') }}">Forgot password?</a></p><p>New to Edumia? <a href="{{ route('register') }}">Create a student account</a></p>
@endsection
