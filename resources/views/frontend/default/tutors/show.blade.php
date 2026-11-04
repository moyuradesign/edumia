@extends('layouts.default')
@section('title', $offering->subject->name.' tutor — Edumia')
@section('content')
<section class="page-hero"><div class="container">
    <a class="back-link" href="{{ route('tutors.index') }}">← All tutors</a>
    <p class="section-kicker">ONE-TO-ONE LEARNING</p>
    <h1>{{ $offering->subject->name }}</h1>
    <p>Learn with {{ $offering->tutor->name }} · Free one-hour appointment</p>
</div></section>
<section class="course-section"><div class="container">
    <div class="course-grid">
        <article class="course-card"><div class="course-body">
            <h2>Weekly available hours</h2>
            <p>All displayed hours are in <strong>{{ $schedules->first()?->timezone ?? 'UTC' }}</strong>.</p>
            @foreach($schedules as $schedule)
                <p>{{ [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'][$schedule->weekday] }}
                    · {{ substr($schedule->start_time,0,5) }}–{{ substr($schedule->end_time,0,5) }}</p>
            @endforeach
            <p>Bookings must begin on the hour, at least two hours ahead, and within 60 days. Existing reservations are checked at submission.</p>
        </div></article>
        <article class="course-card"><div class="course-body">
            <h2>Request an appointment</h2>
            @auth
                <form method="post" action="{{ route('tutors.book',$offering) }}" class="stack-form">
                    @csrf
                    <label>Choose a date and local time ({{ $schedules->first()?->timezone ?? 'UTC' }})
                        <input type="datetime-local" name="scheduled_at" step="3600" value="{{ old('scheduled_at') }}" required></label>
                    <button class="btn btn-primary">Request free booking</button>
                </form>
                <a href="{{ route('tutor.bookings.index') }}">View my bookings</a>
            @else
                <p>Sign in to request an available time.</p>
                <a class="btn btn-primary" href="{{ route('login') }}">Sign in</a>
            @endauth
        </div></article>
    </div>
</div></section>
@endsection
