@extends('layouts.default')
@section('title', 'Ebooks — Edumia')
@section('content')
<section class="page-hero"><div class="container"><p class="section-kicker">EDUMIA DIGITAL LIBRARY</p><h1>Ebooks and guides</h1>
<p>Explore original digital learning resources.</p></div></section>
<section class="course-section"><div class="container"><div class="course-grid">
@forelse($ebooks as $ebook)
<article class="course-card"><div class="course-body"><span class="course-category">{{ $ebook->isFree() ? 'Free' : '$'.$ebook->price }}</span>
<h2>{{ $ebook->title }}</h2><p>{{ $ebook->description }}</p>
<a class="course-link" href="{{ \App\Helpers\Localization::url($locale, 'ebooks/'.$ebook->slug) }}">Explore ebook →</a></div></article>
@empty<div class="empty-state"><h2>No ebooks published yet</h2><p>Check again as new resources become available.</p></div>
@endforelse
</div>{{ $ebooks->links() }}</div></section>
@endsection
