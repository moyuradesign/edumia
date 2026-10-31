@extends('layouts.panel')
@section('title', 'New support ticket')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">SUPPORT</span><h1>Create support ticket</h1><p>Describe your question and our support team can follow up.</p></div></div>
<section class="panel-card">
    <form class="stack-form" method="post" action="{{ route('support.tickets.store') }}">
        @csrf
        <label>Subject <input name="subject" required maxlength="190" value="{{ old('subject') }}"></label>
        <label>Description <textarea name="description" required minlength="10" maxlength="20000" rows="8">{{ old('description') }}</textarea></label>
        <div><button class="btn btn-primary" type="submit">Create ticket</button> <a class="btn btn-quiet" href="{{ route('support.tickets.index') }}">Cancel</a></div>
    </form>
</section>
@endsection
