@extends('layouts.default')
@section('title', $post->title.' — Edumia')
@section('content')
<section class="page-hero"><div class="container">
    <p class="section-kicker">EDUMIA JOURNAL</p><h1>{{ $post->title }}</h1>
    <p>{{ $post->excerpt }}</p>
    <p class="subtle">{{ $post->published_at?->format('M d, Y') }} · {{ $post->author?->name ?? 'Edumia' }}</p>
</div></section>
<section class="course-section"><div class="container">
    <article class="panel-card"><div class="lesson-content">{!! nl2br(e($post->body)) !!}</div></article>
    <a class="text-link" href="{{ \App\Helpers\Localization::url($locale, 'blog') }}">← All articles</a>
</div></section>
@endsection
