@extends('layouts.default')
@section('title','Courses — Edumia')
@section('content')
<section class="page-hero"><div class="container"><p class="section-kicker">EDUMIA CATALOGUE</p><h1>{{ \App\Helpers\Localization::label('all_courses',$locale) }}</h1>
<p>{{ \App\Helpers\Localization::label('featured_intro',$locale) }}</p><form class="search-form compact" action="{{ \App\Helpers\Localization::url($locale,'courses') }}" method="get" role="search"><input type="search" name="q" value="{{ $query }}" maxlength="100" aria-label="Search courses" placeholder="Search published courses"><button class="btn btn-primary">Search</button></form></div></section>
<section class="course-section"><div class="container"><div class="course-grid">@forelse($courses as $course)<article class="course-card"><div class="course-cover tone-amber"><span class="course-icon">✦</span><span class="cover-mark">edumia.</span></div><div class="course-body"><span class="course-category">{{ $course->category?->name??'Learning' }}</span>
<h2>{{ $course->title }}</h2><p>{{ $course->summary }}</p><div class="course-meta"><span>{{ $course->lessons_count }} lessons</span><span>{{ ucfirst($course->level) }}</span></div><a class="course-link" href="{{ \App\Helpers\Localization::url($locale,'courses/'.$course->slug) }}">View course →</a></div></article>
@empty<div class="empty-state"><strong>No published courses found.</strong><p>Instructors can create courses and submit them for approval.</p></div>@endforelse</div><div class="pagination">{{ $courses->links() }}</div></div></section>
@endsection
