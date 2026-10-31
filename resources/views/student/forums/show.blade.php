@extends('layouts.panel')
@section('title', $forum->title)
@section('content')
<div class="panel-heading"><div><span class="section-kicker">COURSE COMMUNITY</span><h1>{{ $forum->title }}</h1>
<p>{{ $course->title }} · {{ ucfirst($forum->status) }}</p></div>
<a class="btn btn-quiet" href="{{ route('learning.forums.index', $course) }}">All discussions</a></div>
<section class="panel-card"><p><strong>{{ $forum->author?->name ?? 'Learner' }}</strong> · {{ $forum->created_at?->format('M d, Y H:i') }}</p>
<div class="lesson-content">{!! nl2br(e($forum->body)) !!}</div></section>
<section class="panel-card"><h2>Replies</h2>
@forelse($replies as $reply)
<article class="builder-section"><p><strong>{{ $reply->author?->name ?? 'Learner' }}</strong> ·
{{ $reply->created_at?->format('M d, Y H:i') }}
@if(auth()->user()->isAdmin()) · {{ ucfirst($reply->status) }} @endif</p>
<div class="lesson-content">{!! nl2br(e($reply->body)) !!}</div>
@if(auth()->user()->isAdmin())
<form class="inline-form" method="post" action="{{ route('admin.forum-replies.update', $reply) }}">
@csrf @method('PATCH')
<input type="hidden" name="status" value="{{ $reply->status === 'hidden' ? 'visible' : 'hidden' }}">
<button class="btn btn-quiet" type="submit">{{ $reply->status === 'hidden' ? 'Restore reply' : 'Hide reply' }}</button>
</form>
@endif
</article>
@empty<p>No replies yet.</p>@endforelse
{{ $replies->links() }}
</section>
@if($forum->status === 'open')
<section class="panel-card"><h2>Post a reply</h2>
<form class="stack-form" method="post" action="{{ route('learning.forums.reply', [$course, $forum]) }}">
@csrf
<label>Reply <textarea name="body" rows="5" required minlength="2" maxlength="10000"></textarea></label>
<button class="btn btn-primary" type="submit">Reply</button></form>
</section>
@else<section class="panel-card"><p>This discussion is currently closed to replies.</p></section>@endif
@endsection
