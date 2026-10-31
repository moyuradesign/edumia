@extends('layouts.panel')
@section('title', 'Course live sessions')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">MY CLASSROOM</span><h1>Live sessions</h1>
<p>{{ $course->title }} · Instructor-hosted external sessions</p></div>
<a class="btn btn-quiet" href="{{ route('learning.course', $course) }}">Back to course</a></div>
<section class="panel-card">
@forelse($classes as $class)
<article class="builder-section">
<h2>{{ $class->title }}</h2>
<p>{{ $class->starts_at?->format('M d, Y H:i') }} — {{ $class->ends_at?->format('M d, Y H:i') }}</p>
@if($class->meeting_url)
<a class="btn btn-primary" target="_blank" rel="noopener noreferrer" href="{{ $class->meeting_url }}">Open meeting link</a>
@endif
</article>
@empty<p>No upcoming live sessions are currently available.</p>@endforelse
</section>
@endsection
