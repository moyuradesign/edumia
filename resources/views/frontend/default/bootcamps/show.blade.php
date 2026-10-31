@extends('layouts.default')
@section('title', $bootcamp->title.' — Edumia')
@section('content')
<section class="page-hero"><div class="container"><a class="back-link" href="{{ \App\Helpers\Localization::url($locale, 'bootcamps') }}">← {{ \App\Helpers\Localization::label('all_bootcamps', $locale) }}</a>
<p class="section-kicker">EDUMIA BOOTCAMPS</p><h1>{{ $bootcamp->title }}</h1>
<p>{{ \App\Helpers\Localization::label('instructor', $locale) }}: {{ $bootcamp->instructor?->name ?? 'Edumia' }} ·
{{ $bootcamp->isFree() ? \App\Helpers\Localization::label('free', $locale) : \App\Helpers\Localization::money((string) $bootcamp->price, 'USD', $locale) }} ·
{{ \App\Helpers\Localization::date($bootcamp->start_date, $locale) }} – {{ \App\Helpers\Localization::date($bootcamp->end_date, $locale) }}</p>
</div></section>
<section class="course-section"><div class="container">
<article class="panel-card"><h2>{{ \App\Helpers\Localization::label('about_cohort', $locale) }}</h2><div class="lesson-content">{!! nl2br(e($bootcamp->description ?? '')) !!}</div>
<p>{{ \App\Helpers\Localization::label('capacity', $locale) }}: {{ $bootcamp->capacity ?? 'Unlimited' }} learners</p></article>
<article class="panel-card"><h2>{{ \App\Helpers\Localization::label('what_learn', $locale) }}</h2>
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
