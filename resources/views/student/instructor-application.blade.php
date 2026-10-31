@extends('layouts.panel')
@section('title', 'Instructor application')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">YOUR CAREER</span><h1>Become an instructor</h1>
<p>Apply to teach on Edumia. Administrators review applications before granting instructor access.</p></div></div>
<section class="panel-card"><h2>Application history</h2>
@forelse($applications as $application)
    <article class="builder-section"><p><strong>{{ ucfirst($application->status) }}</strong> · Submitted {{ $application->created_at?->format('M d, Y') }}</p>
    <p>Specialization: {{ $application->details['specialization'] ?? '—' }}</p>
    @if(!empty($application->details['review_note']))<p>Review note: {{ $application->details['review_note'] }}</p>@endif
    </article>
@empty<p>You have not applied yet.</p>@endforelse</section>
@if(!auth()->user()->canTeach() && !$applications->contains('status', 'pending'))
<section class="panel-card"><h2>Apply to teach</h2>
    <form class="stack-form" method="post" action="{{ route('instructor-application.store') }}">
        @csrf
        <label>Teaching specialization <input name="specialization" required maxlength="190" value="{{ old('specialization') }}"></label>
        <label>Relevant experience <textarea name="experience" rows="6" required minlength="30" maxlength="8000">{{ old('experience') }}</textarea></label>
        <label>Why do you want to teach? <textarea name="motivation" rows="6" required minlength="30" maxlength="8000">{{ old('motivation') }}</textarea></label>
        <label>Optional portfolio URL (HTTPS only) <input type="url" name="portfolio_url" placeholder="https://" maxlength="255" value="{{ old('portfolio_url') }}"></label>
        <p class="subtle">Submitting an application does not grant instructor permissions.</p>
        <button class="btn btn-primary" type="submit">Submit application</button>
    </form>
</section>
@endif
@endsection
