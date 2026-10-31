@extends('layouts.default')
@section('title', 'Bootcamps — Edumia')
@section('content')
<section class="page-hero"><div class="container"><p class="section-kicker">INTENSIVE LEARNING</p><h1>{{ \App\Helpers\Localization::label('bootcamps_title', $locale) }}</h1>
<p>{{ \App\Helpers\Localization::label('bootcamps_intro', $locale) }}</p></div></section>
<section class="course-section"><div class="container"><div class="course-grid">
@forelse($bootcamps as $bootcamp)
<article class="course-card"><div class="course-body">
<span class="course-category">{{ $bootcamp->isFree() ? \App\Helpers\Localization::label('free_cohort', $locale) : \App\Helpers\Localization::money((string) $bootcamp->price, 'USD', $locale) }}</span>
<h2>{{ $bootcamp->title }}</h2><p>{{ \Illuminate\Support\Str::limit($bootcamp->description, 160) }}</p>
<p>{{ \App\Helpers\Localization::date($bootcamp->start_date, $locale) }} · {{ $bootcamp->capacity ?? 'Open' }} seats</p>
<a class="course-link" href="{{ \App\Helpers\Localization::url($locale, 'bootcamps/'.$bootcamp->slug) }}">{{ \App\Helpers\Localization::label('view_bootcamp', $locale) }} →</a>
</div></article>
@empty<div class="empty-state"><h2>No bootcamps yet</h2><p>Explore our course catalogue for more learning options.</p></div>
@endforelse</div>{{ $bootcamps->links() }}</div></section>
@endsection
