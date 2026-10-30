@extends('layouts.auth')
@section('title','Create account')
@section('content')
<h1>Start learning</h1><p>Create your student account. An administrator can later grant instructor access.</p>
<form class="stack-form" action="{{ route('register.store') }}" method="post">@csrf
<label>Full name<input name="name" value="{{ old('name') }}" required maxlength="150" autocomplete="name"></label>
<label>Email address<input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required maxlength="190"></label>
<label>Password<input type="password" name="password" autocomplete="new-password" required minlength="12"><small>At least 12 characters, with upper/lowercase letters, a number and a symbol.</small></label>
<label>Confirm password<input type="password" name="password_confirmation" autocomplete="new-password" required minlength="12"></label>
<button class="btn btn-primary" type="submit">Create account</button></form>
<p>Already a member? <a href="{{ route('login') }}">Sign in</a></p>
@endsection
