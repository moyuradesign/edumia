@extends('layouts.default')
@section('title', $ebook->title.' — Edumia')
@section('content')
<section class="page-hero"><div class="container">
<a class="back-link" href="{{ \App\Helpers\Localization::url($locale, 'ebooks') }}">← {{ \App\Helpers\Localization::label('all_ebooks', $locale) }}</a>
<p class="section-kicker">EDUMIA DIGITAL LIBRARY</p><h1>{{ $ebook->title }}</h1>
<p>{{ \App\Helpers\Localization::label('by', $locale) }} {{ $ebook->author?->name ?? 'Edumia' }} · {{ $ebook->isFree() ? \App\Helpers\Localization::label('free', $locale) : \App\Helpers\Localization::money((string) $ebook->price, 'USD', $locale) }}</p>
</div></section>
<section class="course-section"><div class="container"><article class="panel-card">
<div class="lesson-content">{!! nl2br(e($ebook->description ?? '')) !!}</div>
@if(auth()->check())
@if($ebook->isFree())
<form action="{{ route('ebooks.claim', $ebook) }}" method="post">@csrf
<button class="btn btn-primary" type="submit">Add free ebook to my library</button></form>
@else
<form method="post" action="{{ route('checkout.ebooks.store', $ebook) }}">@csrf
<button class="btn btn-primary" type="submit">Review ebook order</button></form>@endif
@else<p><a href="{{ route('login') }}">Sign in</a> to add a free ebook to your library.</p>@endif
</article></div></section>
@endsection
