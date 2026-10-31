@extends('layouts.default')
@section('title', $topic->title.' — Edumia Help')
@section('content')
<section class="page-hero"><div class="container">
<a class="back-link" href="{{ \App\Helpers\Localization::url($locale, 'help/'.$base->slug) }}">← {{ $base->title }}</a>
<p class="section-kicker">EDUMIA HELP CENTER</p><h1>{{ $topic->title }}</h1></div></section>
<section class="course-section"><div class="container">
<article class="panel-card"><div class="lesson-content">{!! nl2br(e($topic->body ?? '')) !!}</div></article>
</div></section>
@endsection
