@extends('layouts.panel')
@section('title', 'Support ticket review')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">SUPPORT MANAGEMENT</span><h1>{{ $ticket->subject }}</h1>
    <p>Submitted by {{ $ticket->requester?->name }} · {{ $ticket->status?->name ?? 'Open' }}</p></div></div>
<section class="panel-card"><h2>Original request</h2><div class="lesson-content">{!! nl2br(e($ticket->description)) !!}</div></section>
<section class="panel-card"><h2>Conversation</h2>
    @forelse($ticket->messages as $message)
        <article class="builder-section"><p><strong>{{ $message->sender?->name ?? 'Unknown' }}</strong> · {{ $message->created_at?->format('M d, Y H:i') }}</p>
        <div class="lesson-content">{!! nl2br(e($message->body)) !!}</div></article>
    @empty<p>No replies yet.</p>@endforelse
    <a class="btn btn-quiet" href="{{ route('support.tickets.show', $ticket) }}">Reply to ticket</a>
</section>
<section class="panel-card"><h2>Ticket status</h2>
    <form class="stack-form" method="post" action="{{ route('admin.tickets.update', $ticket) }}">
        @csrf @method('PATCH')
        <label>Status <select name="status">
            <option value="open" @selected($ticket->status?->slug === 'open')>Open</option>
            <option value="closed" @selected($ticket->status?->slug === 'closed')>Closed</option>
        </select></label>
        <div><button class="btn btn-primary" type="submit">Save status</button>
        <a class="btn btn-quiet" href="{{ route('admin.tickets.index') }}">Back to tickets</a></div>
    </form>
</section>
@endsection
