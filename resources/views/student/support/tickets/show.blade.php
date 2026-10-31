@extends('layouts.panel')
@section('title', 'Support ticket')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">SUPPORT</span>
    <h1>{{ $ticket->subject }}</h1>
    <p>Ticket #{{ $ticket->id }} · {{ $ticket->status?->name ?? 'Open' }}</p>
</div></div>
<section class="panel-card">
    <h2>Original request</h2><div class="lesson-content">{!! nl2br(e($ticket->description)) !!}</div>
</section>
<section class="panel-card"><h2>Conversation</h2>
    @forelse($ticket->messages as $message)
        <article class="builder-section">
            <p><strong>{{ $message->sender?->name ?? 'Support' }}</strong> · {{ $message->created_at?->format('M d, Y H:i') }}</p>
            <div class="lesson-content">{!! nl2br(e($message->body)) !!}</div>
        </article>
    @empty<p>No replies yet.</p>@endforelse
</section>
@if(! $ticket->status?->is_closed)
<section class="panel-card"><h2>Reply</h2>
    <form class="stack-form" method="post" action="{{ route('support.tickets.reply', $ticket) }}">
        @csrf
        <label>Your reply <textarea name="body" rows="5" minlength="2" maxlength="20000" required>{{ old('body') }}</textarea></label>
        <div><button class="btn btn-primary" type="submit">Send reply</button></div>
    </form>
</section>
@else
<section class="panel-card"><p>This ticket is closed. Contact an administrator if you need it reopened.</p></section>
@endif
@endsection
