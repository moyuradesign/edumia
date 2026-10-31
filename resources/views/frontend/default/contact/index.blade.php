@extends('layouts.default')
@section('title', 'Contact — Edumia')
@section('content')
<section class="page-hero"><div class="container"><p class="section-kicker">GET IN TOUCH</p><h1>Contact Edumia</h1><p>Questions about learning or your account? Send us a message.</p></div></section>
<section class="course-section"><div class="container">
    <div class="panel-card" style="max-width:720px;margin-inline:auto">
        @if(session('success'))<div class="flash" role="status">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="flash error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form class="stack-form" method="post" action="{{ route('contact.store') }}">
            @csrf
            <label>Full name <input name="name" required maxlength="150" autocomplete="name" value="{{ old('name') }}"></label>
            <label>Email <input type="email" name="email" required maxlength="190" autocomplete="email" value="{{ old('email') }}"></label>
            <label>Subject <input name="subject" maxlength="190" value="{{ old('subject') }}"></label>
            <label>Message <textarea name="message" required minlength="10" maxlength="10000" rows="7">{{ old('message') }}</textarea></label>
            <p class="subtle">Please do not include passwords or payment details.</p>
            <div><button class="btn btn-primary" type="submit">Send message</button></div>
        </form>
    </div>
</div></section>
@endsection
