@extends('layouts.panel')
@section('title', 'Contact details')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">SUPPORT & RELATIONSHIPS</span><h1>{{ $contact->subject ?: 'General inquiry' }}</h1><p>Received {{ $contact->created_at?->format('M d, Y H:i') }}</p></div></div>
<section class="panel-card"><h2>Message</h2><p><strong>From:</strong> {{ $contact->name }} · {{ $contact->email }}</p><div class="lesson-content">{!! nl2br(e($contact->message)) !!}</div></section>
<section class="panel-card"><h2>Review status</h2>
    <p class="subtle">Changing status does not send an email. Reply separately through your mail service.</p>
    <form class="stack-form" method="post" action="{{ route('admin.contacts.update', $contact) }}">
        @csrf @method('PATCH')
        <label>Status <select name="status">
            <option value="new" @selected($contact->status === 'new')>New</option>
            <option value="responded" @selected($contact->status === 'responded')>Responded</option>
            <option value="archived" @selected($contact->status === 'archived')>Archived</option>
        </select></label>
        <div><button class="btn btn-primary" type="submit">Save status</button> <a class="btn btn-quiet" href="{{ route('admin.contacts.index') }}">Back to inbox</a></div>
    </form>
</section>
@endsection
