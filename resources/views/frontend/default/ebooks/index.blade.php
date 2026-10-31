@extends('layouts.default')
@section('title', 'Ebooks — Edumia')
@section('content')
<section class="page-hero"><div class="container"><p class="section-kicker">EDUMIA DIGITAL LIBRARY</p><h1>{{ \App\Helpers\Localization::label('ebooks_title', $locale) }}</h1>
<p>{{ \App\Helpers\Localization::label('ebooks_intro', $locale) }}</p></div></section>
<section class="course-section"><div class="container"><div class="course-grid">
@forelse($ebooks as $ebook)
<article class="course-card"><div class="course-body"><span class="course-category">{{ $ebook->isFree() ? \App\Helpers\Localization::label('free', $locale) : \App\Helpers\Localization::money((string) $ebook->price, 'USD', $locale) }}</span>
<h2>{{ $ebook->title }}</h2><p>{{ $ebook->description }}</p>
<a class="course-link" href="{{ \App\Helpers\Localization::url($locale, 'ebooks/'.$ebook->slug) }}">{{ \App\Helpers\Localization::label('explore_ebook', $locale) }} →</a></div></article>
@empty<div class="empty-state"><h2>No ebooks published yet</h2><p>Check again as new resources become available.</p></div>
@endforelse
</div>{{ $ebooks->links() }}</div></section>
@endsection
