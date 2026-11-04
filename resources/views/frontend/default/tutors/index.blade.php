@extends('layouts.default')
@section('title', 'Find a tutor — Edumia')
@section('content')
<section class="page-hero"><div class="container">
    <p class="section-kicker">PERSONAL LEARNING</p>
    <h1>Find a tutor</h1><p>Book a free one-hour session directly with an instructor who has published availability.</p>
</div></section>
<section class="course-section"><div class="container">
    <form method="get" action="{{ route('tutors.index') }}" class="form-row" role="search">
        <label class="grow">Find subjects or instructors
            <input type="search" name="q" value="{{ $search }}" maxlength="80" placeholder="Search tutors"></label>
        <button class="btn btn-primary">Search</button>
        @if($search!=='')<a class="btn btn-secondary" href="{{ route('tutors.index') }}">Clear</a>@endif
    </form>
    <div class="course-grid">
        @forelse($offerings as $offering)
            <article class="course-card"><div class="course-body">
                <span class="course-category">Free · 60-minute tutoring</span>
                <h2>{{ $offering->subject->name }}</h2>
                <p>Instructor: <strong>{{ $offering->tutor->name }}</strong></p>
                <a class="course-link" href="{{ route('tutors.show',$offering) }}">View available hours →</a>
            </div></article>
        @empty
            <div class="empty-state"><h2>No available tutors found</h2><p>Try another subject or check again after instructors publish hours.</p></div>
        @endforelse
    </div>
    {{ $offerings->links() }}
</div></section>
@endsection
