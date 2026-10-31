@extends('layouts.panel')
@section('title', 'Course live classes')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">INSTRUCTOR · LIVE LEARNING</span><h1>Live sessions</h1>
<p>{{ $course->title }} · Schedule an external video meeting for enrolled learners.</p></div>
<a class="btn btn-quiet" href="{{ route('instructor.courses.edit', $course) }}">Back to course</a></div>
<section class="panel-card"><h2>Schedule a class</h2>
<form class="stack-form" method="post" action="{{ route('instructor.live-classes.store', $course) }}">
@csrf
<label>Session title <input name="title" required maxlength="190"></label>
<label>Meeting provider <select name="provider" required>
<option value="zoom">Zoom</option><option value="google_meet">Google Meet</option>
<option value="teams">Microsoft Teams</option><option value="other">Other HTTPS meeting</option></select></label>
<label>HTTPS joining link <input name="meeting_url" type="url" required maxlength="255" placeholder="https://"></label>
<div class="form-two">
<label>Start time <input name="starts_at" type="datetime-local" required></label>
<label>End time <input name="ends_at" type="datetime-local" required></label>
</div><p class="subtle">Set times in the application timezone. Meeting links remain private to enrolled learners.</p>
<button class="btn btn-primary" type="submit">Schedule session</button>
</form></section>
<section class="panel-card"><h2>Scheduled and past classes</h2>
@forelse($classes as $class)
<article class="builder-section"><h3>{{ $class->title }}</h3>
<p>{{ $class->starts_at?->format('M d, Y H:i') }} — {{ $class->ends_at?->format('H:i') }} · {{ ucfirst($class->status) }}</p>
<details><summary>Edit session</summary>
<form class="stack-form compact-form" action="{{ route('instructor.live-classes.update', [$course, $class]) }}" method="post">
@csrf @method('PUT')
<label>Title <input name="title" maxlength="190" required value="{{ $class->title }}"></label>
<label>Provider <select name="provider">
@foreach(['zoom'=>'Zoom', 'google_meet'=>'Google Meet', 'teams'=>'Microsoft Teams', 'other'=>'Other HTTPS meeting'] as $code=>$name)
<option value="{{ $code }}" @selected($class->provider === $code)>{{ $name }}</option>
@endforeach</select></label>
<label>Meeting URL <input name="meeting_url" type="url" maxlength="255" required value="{{ $class->meeting_url }}"></label>
<label>Starts <input name="starts_at" type="datetime-local" required value="{{ $class->starts_at?->format('Y-m-d\TH:i') }}"></label>
<label>Ends <input name="ends_at" type="datetime-local" required value="{{ $class->ends_at?->format('Y-m-d\TH:i') }}"></label>
<label>Status <select name="status">
@foreach(['scheduled'=>'Scheduled', 'completed'=>'Completed', 'cancelled'=>'Cancelled'] as $code=>$name)
<option value="{{ $code }}" @selected($class->status === $code)>{{ $name }}</option>
@endforeach</select></label>
<button class="btn btn-secondary" type="submit">Save changes</button>
</form></details>
</article>
@empty<p>No live classes scheduled yet.</p>@endforelse
</section>
@endsection
