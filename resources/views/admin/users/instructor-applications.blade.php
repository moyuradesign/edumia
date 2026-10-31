@extends('layouts.panel')
@section('title', 'Instructor applications')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">USER MANAGEMENT</span><h1>Instructor applications</h1>
<p>Review each application before granting instructor permissions.</p></div></div>
<section class="panel-card">
@forelse($applications as $application)
    <article class="builder-section">
        <h2>{{ $application->applicant?->name ?? 'Deleted user' }} · {{ ucfirst($application->status) }}</h2>
        <p>{{ $application->applicant?->email }} · Submitted {{ $application->created_at?->format('M d, Y') }}</p>
        <p><strong>Specialization:</strong> {{ $application->details['specialization'] ?? '—' }}</p>
        <h3>Experience</h3><div class="lesson-content">{!! nl2br(e($application->details['experience'] ?? '')) !!}</div>
        <h3>Motivation</h3><div class="lesson-content">{!! nl2br(e($application->details['motivation'] ?? '')) !!}</div>
        @if(!empty($application->details['portfolio_url']))<p><a href="{{ $application->details['portfolio_url'] }}" target="_blank" rel="noopener noreferrer">Portfolio</a></p>@endif
        @if($application->status === 'pending')
            <form class="stack-form compact-form" method="post" action="{{ route('admin.instructor-applications.review', $application) }}">
                @csrf @method('PATCH')
                <label>Decision <select name="decision" required><option value="approved">Approve instructor</option><option value="rejected">Reject application</option></select></label>
                <label>Note to applicant <textarea name="review_note" rows="3" maxlength="2000"></textarea></label>
                <button class="btn btn-primary" type="submit">Save decision</button>
            </form>
        @else
            <p>Reviewed by {{ $application->reviewer?->name ?? 'Administrator' }} on {{ $application->reviewed_at?->format('M d, Y') }}</p>
        @endif
    </article>
@empty
    <p>No instructor applications yet.</p>
@endforelse
{{ $applications->links() }}
</section>
@endsection
