@extends('layouts.panel')
@section('title', 'Instructor overview')
@section('content')
<div class="member-dashboard">
    <div class="admin-welcome member-hero">
        <div><span class="admin-eyebrow"><span class="admin-eyebrow-dot" aria-hidden="true"></span> INSTRUCTOR WORKSPACE</span>
            <h1>Welcome back, {{ auth()->user()->name }}</h1>
            <p>Manage your courses, review student activity, and create new lessons.</p></div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('instructor.reports.index') }}">View reports</a>
            <a class="admin-action-primary" href="{{ route('instructor.courses.create') }}">Create course</a>
        </div>
    </div>
    <div class="admin-stats" aria-label="Instructor overview">
        <a class="admin-stat" href="{{ route('instructor.courses.index') }}"><span class="admin-stat-top"><span>My courses</span><span class="admin-stat-icon admin-stat-icon--blue" aria-hidden="true">▣</span></span><strong>{{ $courses->count() }}</strong><small>All course drafts and publications</small></a>
        <a class="admin-stat" href="{{ route('instructor.reports.index') }}"><span class="admin-stat-top"><span>Course enrollments</span><span class="admin-stat-icon admin-stat-icon--mint" aria-hidden="true">◎</span></span><strong>{{ number_format($enrollments) }}</strong><small>Enrollment records</small></a>
        <a class="admin-stat" href="{{ route('instructor.courses.index', ['status' => 'published']) }}"><span class="admin-stat-top"><span>Published courses</span><span class="admin-stat-icon admin-stat-icon--orange" aria-hidden="true">✓</span></span><strong>{{ $courses->where('status', 'published')->count() }}</strong><small>Available in the catalog</small></a>
        <a class="admin-stat" href="{{ route('instructor.courses.index', ['status' => 'draft']) }}"><span class="admin-stat-top"><span>Draft courses</span><span class="admin-stat-icon admin-stat-icon--violet" aria-hidden="true">✎</span></span><strong>{{ $courses->where('status', 'draft')->count() }}</strong><small>Not yet submitted for review</small></a>
    </div>
    <div class="admin-section-title"><div><h2>Quick management</h2><p>Access your teaching tools.</p></div></div>
    <div class="admin-shortcut-grid member-shortcuts">
        <a href="{{ route('instructor.courses.index') }}"><span aria-hidden="true">▣</span><strong>My courses</strong><small>Manage content</small></a>
        <a href="{{ route('instructor.courses.create') }}"><span aria-hidden="true">＋</span><strong>New course</strong><small>Create a draft</small></a>
        <a href="{{ route('instructor.ebooks.index') }}"><span aria-hidden="true">▤</span><strong>Ebooks</strong><small>Manage titles</small></a>
        <a href="{{ route('instructor.bootcamps.index') }}"><span aria-hidden="true">▦</span><strong>Bootcamps</strong><small>Programs & sessions</small></a>
        <a href="{{ route('instructor.reports.index') }}"><span aria-hidden="true">▥</span><strong>Reports</strong><small>Student activity</small></a>
        <a href="{{ route('instructor.earnings.index') }}"><span aria-hidden="true">◈</span><strong>Earnings</strong><small>Verified entries only</small></a>
    </div>
    <section class="admin-widget" aria-labelledby="instructor-course-title">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">COURSE MANAGEMENT</span><h2 id="instructor-course-title">Your courses</h2><p>Review and edit your most recent courses.</p></div><a href="{{ route('instructor.courses.index') }}">View all courses ↗</a></div>
        @if($courses->isEmpty())
            <div class="admin-empty"><span aria-hidden="true">▣</span><strong>No courses yet</strong><p>Create a course draft to begin preparing lessons.</p><a class="admin-action-primary" href="{{ route('instructor.courses.create') }}">Create course</a></div>
        @else
            <div class="table-scroll"><table class="admin-table">
                <thead><tr><th>Course</th><th>Lessons</th><th>Enrollments</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>@foreach($courses->take(8) as $course)
                <tr><td><strong>{{ $course->title }}</strong></td><td>{{ $course->lessons_count }}</td><td>{{ $course->enrollments_count }}</td>
                    <td><span class="admin-status admin-status--{{ $course->status === 'published' ? 'success' : 'neutral' }}">{{ ucfirst($course->status) }}</span></td>
                    <td><a class="admin-table-action" href="{{ route('instructor.courses.edit', $course) }}">Manage course</a></td>
                </tr>@endforeach</tbody>
            </table></div>
        @endif
    </section>
</div>
@endsection
