@extends('layouts.panel')
@section('title', 'My learning')
@section('content')
<div class="member-dashboard" data-member-dashboard>
    <div class="admin-welcome member-hero">
        <div>
            <span class="admin-eyebrow"><span class="admin-eyebrow-dot" aria-hidden="true"></span> STUDENT WORKSPACE</span>
            <h1>Welcome back, {{ auth()->user()->name }}</h1>
            <p>Pick up where you left off and keep track of your learning.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('account.courses') }}">My courses</a>
            <a class="admin-action-primary" href="{{ route('courses.index') }}">Explore courses</a>
        </div>
    </div>

    <div class="admin-stats" aria-label="Learning overview">
        <a class="admin-stat" href="{{ route('account.courses') }}">
            <span class="admin-stat-top"><span>Enrolled courses</span><span class="admin-stat-icon admin-stat-icon--blue" aria-hidden="true">▣</span></span>
            <strong>{{ number_format($enrollments->count()) }}</strong><small>Your learning library</small>
        </a>
        <a class="admin-stat" href="{{ route('account.courses', ['status' => 'completed']) }}">
            <span class="admin-stat-top"><span>Completed courses</span><span class="admin-stat-icon admin-stat-icon--mint" aria-hidden="true">✓</span></span>
            <strong>{{ number_format($enrollments->filter(fn ($e) => $e->status === 'completed')->count()) }}</strong><small>Finished learning</small>
        </a>
        <div class="admin-stat">
            <span class="admin-stat-top"><span>Passed quizzes</span><span class="admin-stat-icon admin-stat-icon--orange" aria-hidden="true">◉</span></span>
            <strong>{{ number_format($passed) }}</strong><small>Recorded passed attempts</small>
        </div>
        <a class="admin-stat" href="{{ route('account.certificates') }}">
            <span class="admin-stat-top"><span>Certificates</span><span class="admin-stat-icon admin-stat-icon--violet" aria-hidden="true">✧</span></span>
            <strong>{{ number_format($certificates->count()) }}</strong><small>Available to view</small>
        </a>
    </div>

    <div class="admin-section-title"><div><h2>Quick access</h2><p>Open your most-used learning tools.</p></div></div>
    <div class="admin-shortcut-grid member-shortcuts">
        <a href="{{ route('account.courses') }}"><span aria-hidden="true">▣</span><strong>My courses</strong><small>Continue learning</small></a>
        <a href="{{ route('ebooks.library') }}"><span aria-hidden="true">▤</span><strong>My ebooks</strong><small>Your reading library</small></a>
        <a href="{{ route('bootcamps.my') }}"><span aria-hidden="true">▦</span><strong>Bootcamps</strong><small>Joined programs</small></a>
        <a href="{{ route('saved-courses.index') }}"><span aria-hidden="true">☆</span><strong>Saved courses</strong><small>Your wishlist</small></a>
        <a href="{{ route('checkout.orders.index') }}"><span aria-hidden="true">▤</span><strong>My orders</strong><small>Purchase history</small></a>
        <a href="{{ route('account.certificates') }}"><span aria-hidden="true">✧</span><strong>Certificates</strong><small>Your achievements</small></a>
        <a href="{{ route('account.preferences.edit') }}"><span aria-hidden="true">⚙</span><strong>My profile</strong><small>Account preferences</small></a>
        <a href="{{ route('support.tickets.index') }}"><span aria-hidden="true">✉</span><strong>Support</strong><small>Your support tickets</small></a>
    </div>

    <section class="admin-widget member-learning-widget" aria-labelledby="member-learning-title">
        <div class="admin-widget-heading">
            <div><span class="admin-widget-kicker">CONTINUE LEARNING</span><h2 id="member-learning-title">My courses</h2><p>Your recent enrollments and current progress.</p></div>
            <a href="{{ route('account.courses') }}">View all courses ↗</a>
        </div>
        @if($enrollments->isEmpty())
            <div class="admin-empty"><span aria-hidden="true">▣</span><strong>No courses yet</strong><p>Browse the course catalog to get started.</p><a class="admin-action-primary" href="{{ route('courses.index') }}">Browse courses</a></div>
        @else
            <div class="member-learning-list">
                @foreach($enrollments->take(5) as $enrollment)
                    @if($enrollment->course)
                        <article class="member-learning-item">
                            <span class="member-course-symbol" aria-hidden="true">▣</span>
                            <div class="member-learning-detail">
                                <h3>{{ $enrollment->course->title }}</h3>
                                <div class="member-learning-meta">
                                    <span>{{ $enrollment->progress_percent }}% complete</span>
                                    <span class="admin-status admin-status--{{ $enrollment->status === 'completed' ? 'success' : 'neutral' }}">{{ $enrollment->status === 'completed' ? 'Completed' : 'In progress' }}</span>
                                </div>
                                <progress max="100" value="{{ min(100, max(0, $enrollment->progress_percent)) }}" aria-label="Progress for {{ $enrollment->course->title }}"></progress>
                            </div>
                            <a class="admin-table-action" href="{{ route('learning.course', $enrollment->course) }}">Continue</a>
                        </article>
                    @endif
                @endforeach
            </div>
        @endif
    </section>

    <section class="admin-widget" id="member-certificates" aria-labelledby="member-certificates-title">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">ACHIEVEMENTS</span><h2 id="member-certificates-title">Certificates</h2><p>Certificates issued for completed learning.</p></div><a href="{{ route('account.certificates') }}">View all certificates ↗</a></div>
        @if($certificates->isEmpty())
            <div class="admin-empty"><span aria-hidden="true">✧</span><strong>No certificates yet</strong><p>Your certificates will appear here when they are awarded.</p></div>
        @else
            <div class="table-scroll"><table class="admin-table">
                <thead><tr><th>Course</th><th>Certificate</th><th>Action</th></tr></thead>
                <tbody>@foreach($certificates as $certificate)
                    @if($certificate->course)
                        <tr><td><strong>{{ $certificate->course->title }}</strong></td><td>{{ $certificate->certificate_number }}</td>
                        <td><a class="admin-table-action" href="{{ route('certificate.show', $certificate) }}">View certificate</a></td></tr>
                    @endif
                @endforeach</tbody>
            </table></div>
        @endif
    </section>
</div>
@endsection
