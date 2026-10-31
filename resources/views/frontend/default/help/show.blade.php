@extends('layouts.default')
@section('title', $base->title.' — Help Center')
@section('content')
<section class="page-hero"><div class="container"><a class="back-link" href="{{ \App\Helpers\Localization::url($locale, 'help') }}">← Help Center</a>
<p class="section-kicker">EDUMIA HELP</p><h1>{{ $base->title }}</h1>
<div class="lesson-content">{!! nl2br(e($base->content ?? '')) !!}</div></div></section>
<section class="course-section"><div class="container">
@forelse($base->topics as $topic)
<article class="panel-card"><h2>{{ $topic->title }}</h2>
<p>{{ \Illuminate\Support\Str::limit($topic->body, 180) }}</p>
<a href="{{ \App\Helpers\Localization::url($locale, 'help/'.$base->slug.'/'.$topic->slug) }}">Read article</a></article>
@empty<p>No articles are available in this section yet.</p>@endforelse
</div></section>
@endsection
