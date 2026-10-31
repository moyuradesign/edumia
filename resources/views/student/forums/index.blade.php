@extends('layouts.panel')
@section('title', 'Course discussions')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">COURSE COMMUNITY</span><h1>Discussions</h1>
<p>{{ $course->title }} · Conversations are visible only to enrolled learners and course instructors.</p></div>
<a class="btn btn-quiet" href="{{ route('learning.course', $course) }}">Back to classroom</a></div>
<section class="panel-card"><h2>Start a discussion</h2>
<form class="stack-form" method="post" action="{{ route('learning.forums.store', $course) }}">
@csrf
<label>Topic <input name="title" maxlength="190" required value="{{ old('title') }}"></label>
<label>Message <textarea name="body" minlength="10" maxlength="10000" required rows="5">{{ old('body') }}</textarea></label>
<button class="btn btn-primary" type="submit">Post discussion</button></form>
</section>
<section class="panel-card"><h2>Course conversations</h2>
@forelse($discussions as $forum)
<article class="builder-section"><h3><a href="{{ route('learning.forums.show', [$course, $forum]) }}">{{ $forum->title }}</a></h3>
<p>{{ $forum->author?->name ?? 'Learner' }} · {{ $forum->replies_count }} replies · {{ ucfirst($forum->status) }}</p></article>
@empty<p>No discussions yet. Start the conversation.</p>@endforelse
{{ $discussions->links() }}
</section>
@endsection
