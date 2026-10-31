@extends('layouts.panel')
@section('title', 'Forum moderation')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">COMMUNITY MODERATION</span><h1>Course discussions</h1>
<p>Review, close, hide, or reopen discussions. Replies can be moderated inside each course thread.</p></div></div>
<section class="panel-card">
@forelse($discussions as $forum)
<article class="builder-section"><h2>{{ $forum->title }}</h2>
<p>{{ $forum->course?->title ?? 'Unassigned course' }} · {{ $forum->author?->name ?? 'Unknown' }} · {{ ucfirst($forum->status) }}</p>
<div class="lesson-content">{!! nl2br(e($forum->body)) !!}</div>
<form method="post" class="inline-form" action="{{ route('admin.forums.update', $forum) }}">
@csrf @method('PATCH')
<label>Status <select name="status">
@foreach(['open'=>'Open', 'closed'=>'Closed', 'hidden'=>'Hidden'] as $value=>$title)
<option value="{{ $value }}" @selected($forum->status === $value)>{{ $title }}</option>
@endforeach</select></label>
<button class="btn btn-secondary" type="submit">Save moderation</button></form>
@if($forum->course_id)
<a href="{{ route('learning.forums.show', [$forum->course_id, $forum]) }}">Review replies</a>
@endif
</article>
@empty<p>No course discussions to moderate.</p>@endforelse
{{ $discussions->links() }}
</section>
@endsection
