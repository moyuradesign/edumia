@extends('layouts.default')
@section('title', 'Bootcamps — Edumia')
@section('content')
<section class="page-hero"><div class="container"><p class="section-kicker">INTENSIVE LEARNING</p><h1>Edumia Bootcamps</h1>
<p>Join a guided learning cohort with structured modules and live sessions.</p></div></section>
<section class="course-section"><div class="container"><div class="course-grid">
@forelse($bootcamps as $bootcamp)
<article class="course-card"><div class="course-body">
<span class="course-category">{{ $bootcamp->isFree() ? 'Free cohort' : '$'.$bootcamp->price }}</span>
<h2>{{ $bootcamp->title }}</h2><p>{{ \Illuminate\Support\Str::limit($bootcamp->description, 160) }}</p>
<p>{{ $bootcamp->start_date?->format('M d, Y') }} · {{ $bootcamp->capacity ?? 'Open' }} seats</p>
<a class="course-link" href="{{ \App\Helpers\Localization::url($locale, 'bootcamps/'.$bootcamp->slug) }}">View bootcamp →</a>
</div></article>
@empty<div class="empty-state"><h2>No bootcamps yet</h2><p>Explore our course catalogue for more learning options.</p></div>
@endforelse</div>{{ $bootcamps->links() }}</div></section>
@endsection
