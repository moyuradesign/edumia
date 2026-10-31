@extends('layouts.default')
@section('title', $bootcamp->title.' — Edumia')
@section('content')
<section class="page-hero"><div class="container"><a class="back-link" href="{{ \App\Helpers\Localization::url($locale, 'bootcamps') }}">← All bootcamps</a>
<p class="section-kicker">EDUMIA BOOTCAMPS</p><h1>{{ $bootcamp->title }}</h1>
<p>Instructor: {{ $bootcamp->instructor?->name ?? 'Edumia' }} ·
{{ $bootcamp->isFree() ? 'Free' : '$'.$bootcamp->price }} ·
{{ $bootcamp->start_date?->format('M d, Y') }} – {{ $bootcamp->end_date?->format('M d, Y') }}</p>
</div></section>
<section class="course-section"><div class="container">
<article class="panel-card"><h2>About this cohort</h2><div class="lesson-content">{!! nl2br(e($bootcamp->description ?? '')) !!}</div>
<p>Capacity: {{ $bootcamp->capacity ?? 'Unlimited' }} learners</p></article>
<article class="panel-card"><h2>What you will learn</h2>
@foreach($bootcamp->modules as $module)
<div class="builder-section"><h3>{{ $module->title }}</h3><p>{{ $module->description }}</p></div>
@endforeach</article>
<article class="panel-card">
@if(auth()->check())
@if($bootcamp->isFree())
<form method="post" action="{{ route('bootcamps.enroll', $bootcamp) }}">@csrf
<button class="btn btn-primary" type="submit">Enroll in free cohort</button></form>
@else
<form method="post" action="{{ route('checkout.bootcamps.store', $bootcamp) }}">@csrf
<button class="btn btn-primary" type="submit">Review bootcamp order</button></form>@endif
@else<a href="{{ route('login') }}">Sign in to enroll</a>@endif
</article></div></section>
@endsection
