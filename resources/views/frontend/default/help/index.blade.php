@extends('layouts.default')
@section('title', 'Help Center — Edumia')
@section('content')
<section class="page-hero"><div class="container"><p class="section-kicker">HELP & SUPPORT</p><h1>Edumia Help Center</h1><p>Find useful guides and answers.</p>
<form method="get" action="{{ \App\Helpers\Localization::url($locale, 'help') }}" class="stack-form">
<label>Search knowledge base <input name="q" type="search" maxlength="100" value="{{ $search }}" placeholder="Search guides and topics"></label>
<button class="btn btn-primary" type="submit">Search</button>
</form></div></section>
<section class="course-section"><div class="container"><div class="course-grid">
@forelse($bases as $base)
<article class="course-card"><div class="course-body"><span class="course-category">{{ $base->category ?? 'Help' }}</span>
<h2>{{ $base->title }}</h2><p>{{ \Illuminate\Support\Str::limit($base->content, 150) }}</p>
<p>{{ $base->topics_count }} articles</p>
<a class="course-link" href="{{ \App\Helpers\Localization::url($locale, 'help/'.$base->slug) }}">Read guides →</a></div></article>
@empty<div class="empty-state"><h2>No matching guides</h2><p>Try another search term or contact support.</p></div>@endforelse
</div>{{ $bases->links() }}</div></section>
@endsection
