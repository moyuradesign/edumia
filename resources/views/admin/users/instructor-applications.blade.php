@extends('layouts.panel')
@section('title', 'Instructor applications')
@section('content')
<section class="admin-instructor-workspace" aria-labelledby="instructor-heading">
    <div class="admin-welcome">
        <div><span class="admin-eyebrow">PEOPLE & ACCESS</span>
            <h1 id="instructor-heading">Instructor management</h1>
            <p>Review teaching applications and grant instructor access through the existing approval workflow.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('admin.users.index', ['role' => 'instructor']) }}">All instructors</a>
            <a class="admin-action-secondary" href="{{ route('admin.roles.index') }}">Staff roles</a>
        </div>
    </div>

    <div class="admin-stats" aria-label="Instructor application statistics">
        <div class="admin-stat"><span class="admin-stat-top"><span>Applications</span><span class="admin-stat-icon admin-stat-icon--blue" aria-hidden="true">▤</span></span>
            <strong>{{ number_format($allApplications) }}</strong><small>All teaching requests</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>Pending review</span><span class="admin-stat-icon admin-stat-icon--orange" aria-hidden="true">◷</span></span>
            <strong>{{ number_format($pendingApplications) }}</strong><small>Awaiting decision</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>Approved</span><span class="admin-stat-icon admin-stat-icon--mint" aria-hidden="true">✓</span></span>
            <strong>{{ number_format($approvedApplications) }}</strong><small>Requests granted</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>Rejected</span><span class="admin-stat-icon admin-stat-icon--violet" aria-hidden="true">×</span></span>
            <strong>{{ number_format($rejectedApplications) }}</strong><small>Requests declined</small></div>
    </div>

    <section class="admin-widget admin-instructor-list" aria-labelledby="instructor-list-title">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">APPLICATION REVIEW</span>
            <h2 id="instructor-list-title">Teaching applications</h2>
            <p>Showing {{ $applications->firstItem() ?? 0 }}–{{ $applications->lastItem() ?? 0 }} of {{ number_format($applications->total()) }} matching applications.</p>
        </div></div>
        <form method="get" action="{{ route('admin.instructor-applications.index') }}" class="admin-course-filters admin-instructor-filters">
            <div>
                <label class="sr-only" for="instructor-query">Search applicants and specializations</label>
                <input id="instructor-query" type="search" name="q" maxlength="100" value="{{ $search }}"
                       placeholder="Applicant name, email or specialization">
            </div>
            <div>
                <label class="sr-only" for="instructor-status">Filter application status</label>
                <select id="instructor-status" name="status">
                    <option value="all" @selected($status === 'all')>All applications</option>
                    <option value="pending" @selected($status === 'pending')>Pending review</option>
                    <option value="approved" @selected($status === 'approved')>Approved</option>
                    <option value="rejected" @selected($status === 'rejected')>Rejected</option>
                </select>
            </div>
            <button class="admin-action-primary" type="submit">Apply filters</button>
            @if($search !== '' || $status !== 'all')<a href="{{ route('admin.instructor-applications.index') }}" class="admin-action-secondary">Clear</a>@endif
        </form>

        <div class="admin-instructor-cards">
            @forelse($applications as $application)
                <article class="admin-instructor-card" aria-labelledby="instructor-application-{{ $application->id }}">
                    <div class="admin-instructor-card-head">
                        <div class="admin-instructor-identity">
                            <span class="admin-member-initial" aria-hidden="true">{{ mb_strtoupper(mb_substr($application->applicant?->name ?? 'U', 0, 1)) }}</span>
                            <div>
                                <h3 id="instructor-application-{{ $application->id }}">{{ $application->applicant?->name ?? 'Deleted user' }}</h3>
                                <p>{{ $application->applicant?->email ?? 'Account no longer available' }}</p>
                            </div>
                        </div>
                        <span class="admin-status admin-status--{{ $application->status === 'approved' ? 'success' : 'neutral' }}">{{ ucfirst($application->status) }}</span>
                    </div>
                    <div class="admin-instructor-meta">
                        <span><strong>Specialization</strong>{{ $application->details['specialization'] ?? 'Not specified' }}</span>
                        <span><strong>Submitted</strong><time datetime="{{ $application->created_at?->toDateString() }}">{{ $application->created_at?->format('M j, Y') ?? '—' }}</time></span>
                        <span><strong>Application</strong>#{{ $application->id }}</span>
                    </div>

                    <details class="admin-instructor-details" @if($application->status === 'pending') open @endif>
                        <summary>Experience, motivation & portfolio</summary>
                        <div class="admin-instructor-details-content">
                            <div><h4>Experience</h4><p>{!! nl2br(e($application->details['experience'] ?? 'No experience supplied.')) !!}</p></div>
                            <div><h4>Motivation</h4><p>{!! nl2br(e($application->details['motivation'] ?? 'No motivation supplied.')) !!}</p></div>
                            @if(!empty($application->details['portfolio_url']) && str_starts_with($application->details['portfolio_url'], 'https://'))
                                <a href="{{ $application->details['portfolio_url'] }}" target="_blank" rel="noopener noreferrer">Review applicant portfolio ↗</a>
                            @endif
                        </div>
                    </details>

                    @if($application->status === 'pending')
                        <form class="admin-instructor-decision" method="post"
                              action="{{ route('admin.instructor-applications.review', $application) }}"
                              data-confirm="Submit this instructor application decision?">
                            @csrf @method('PATCH')
                            <div class="admin-instructor-decision-fields">
                                <label for="application-decision-{{ $application->id }}">Decision
                                    <select id="application-decision-{{ $application->id }}" name="decision" required>
                                        <option value="approved">Approve instructor</option>
                                        <option value="rejected">Reject application</option>
                                    </select>
                                </label>
                                <label for="application-note-{{ $application->id }}">Review note (optional)
                                    <textarea id="application-note-{{ $application->id }}" name="review_note" rows="2"
                                              maxlength="2000" placeholder="Explain the decision for the applicant"></textarea>
                                </label>
                            </div>
                            <button class="admin-action-primary" type="submit">Save review decision</button>
                        </form>
                    @else
                        <div class="admin-instructor-reviewed">
                            Reviewed by {{ $application->reviewer?->name ?? 'Administrator' }}
                            @if($application->reviewed_at) on <time datetime="{{ $application->reviewed_at->toDateString() }}">{{ $application->reviewed_at->format('M j, Y') }}</time>@endif
                            @if(!empty($application->details['review_note']))
                                <p><strong>Review note:</strong> {{ $application->details['review_note'] }}</p>
                            @endif
                        </div>
                    @endif
                </article>
            @empty
                <div class="admin-empty"><span aria-hidden="true">✓</span>
                    <strong>No matching applications</strong><p>Try another status or applicant search.</p>
                </div>
            @endforelse
        </div>
        <div class="admin-course-pagination">{{ $applications->onEachSide(1)->links() }}</div>
        <p class="admin-widget-footnote">Only active eligible applicants can be approved. Decisions are audited and cannot be silently reapplied.</p>
    </section>
</section>
@endsection
