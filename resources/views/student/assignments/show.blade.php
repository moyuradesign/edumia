@extends('layouts.panel')
@section('title', $assignment->title)
@section('content')
<div class="panel-heading">
    <div><span class="section-kicker">YOUR CLASSROOM · ASSIGNMENT</span>
        <h1>{{ $assignment->title }}</h1>
        <p>{{ $course->title }} · Maximum {{ $assignment->max_score }} points
        @if($assignment->due_at) · Due {{ $assignment->due_at->format('M d, Y H:i') }} @endif
        </p></div>
    <a class="btn btn-quiet" href="{{ route('learning.course', $course) }}">Back to classroom</a>
</div>
<section class="panel-card">
    <h2>Instructions</h2>
    <div class="lesson-content">{!! nl2br(e($assignment->instructions)) !!}</div>
</section>
@if($submission)
<section class="panel-card">
    <h2>Your submission</h2>
    <p>Submitted {{ $submission->submitted_at?->format('M d, Y H:i') }}</p>
    <div class="lesson-content">{!! nl2br(e($submission->response)) !!}</div>
    @if($submission->graded_at)
        <p><strong>Grade: {{ $submission->score }} / {{ $assignment->max_score }}</strong></p>
        @if($submission->feedback)<h3>Instructor feedback</h3><div class="lesson-content">{!! nl2br(e($submission->feedback)) !!}</div>@endif
    @else
        <p>Awaiting instructor review.</p>
    @endif
</section>
@endif
@if((!$submission || !$submission->graded_at) && (!$assignment->due_at || now()->lessThanOrEqualTo($assignment->due_at)))
<section class="panel-card">
    <h2>{{ $submission ? 'Update your submission' : 'Submit your response' }}</h2>
    <form class="stack-form" method="post" action="{{ route('learning.assignments.submit', [$course, $assignment]) }}">
        @csrf
        <label>Your written response
            <textarea name="response" rows="12" required minlength="10" maxlength="50000">{{ old('response', $submission?->response) }}</textarea>
        </label>
        <p class="subtle">You can revise until the deadline or until an instructor grades your work.</p>
        <div><button class="btn btn-primary" type="submit">Submit assignment</button></div>
    </form>
</section>
@elseif(!$submission && $assignment->due_at && now()->greaterThan($assignment->due_at))
<section class="panel-card"><p>The deadline has passed; this assignment is no longer accepting submissions.</p></section>
@endif
@endsection
