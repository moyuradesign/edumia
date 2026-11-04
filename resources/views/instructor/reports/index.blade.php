@extends('layouts.panel')
@section('title', 'My learning reports')
@section('content')
<div class="member-dashboard">
    <div class="admin-welcome member-hero">
        <div>
            <span class="admin-eyebrow"><span class="admin-eyebrow-dot" aria-hidden="true"></span> INSTRUCTOR / INSIGHTS</span>
            <h1>My learning reports</h1>
            <p>Completion, activity and grading results from your own courses.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('instructor.students.index') }}">View students</a>
            <a class="admin-action-primary" href="{{ route('instructor.courses.index') }}">Manage courses</a>
        </div>
    </div>
    <div class="admin-stats" aria-label="Instructor course statistics">
        <a class="admin-stat" href="{{ route('instructor.courses.index') }}">
            <span class="admin-stat-top"><span>My courses</span><span class="admin-stat-icon admin-stat-icon--blue" aria-hidden="true">▣</span></span>
            <strong>{{ number_format($courseCount) }}</strong><small>Courses under your account</small>
        </a>
        <a class="admin-stat" href="{{ route('instructor.students.index') }}">
            <span class="admin-stat-top"><span>Course enrollments</span><span class="admin-stat-icon admin-stat-icon--mint" aria-hidden="true">◉</span></span>
            <strong>{{ number_format($enrollmentCount) }}</strong><small>{{ number_format($completedCount) }} completed</small>
        </a>
        <div class="admin-stat">
            <span class="admin-stat-top"><span>Average progress</span><span class="admin-stat-icon admin-stat-icon--orange" aria-hidden="true">✓</span></span>
            <strong>{{ number_format($averageProgress, 1) }}%</strong><small>Recorded enrollment progress</small>
        </div>
        <div class="admin-stat">
            <span class="admin-stat-top"><span>Quiz submissions</span><span class="admin-stat-icon admin-stat-icon--violet" aria-hidden="true">◇</span></span>
            <strong>{{ number_format($quizAttempts) }}</strong><small>{{ number_format($quizPasses) }} passed attempts</small>
        </div>
    </div>
    <section class="admin-widget" aria-labelledby="instructor-course-report-heading">
        <div class="admin-widget-heading">
            <div><span class="admin-widget-kicker">COURSE PERFORMANCE</span>
                <h2 id="instructor-course-report-heading">My courses</h2>
                <p>Live enrollment progress and completion by course.</p>
            </div>
            <span class="admin-status admin-status--neutral">{{ number_format($ungradedSubmissions) }} ungraded submissions</span>
        </div>
        @if($courses->isEmpty())
            <div class="admin-empty"><span aria-hidden="true">▣</span>
                <strong>No courses yet</strong><p>Course reports will appear after you create a course.</p>
                <a class="admin-action-primary" href="{{ route('instructor.courses.create') }}">Create course</a>
            </div>
        @else
            <div class="table-scroll"><table class="admin-table">
                <thead><tr><th>Course</th><th>Enrolled</th><th>Completed</th><th>Completion ratio</th><th>Average progress</th><th>Action</th></tr></thead>
                <tbody>
                @foreach($courses as $course)
                    <tr>
                        <td><strong>{{ $course->title }}</strong></td>
                        <td>{{ number_format($course->enrollments_count) }}</td>
                        <td>{{ number_format($course->completed_count) }}</td>
                        <td>{{ $course->enrollments_count ? number_format(100 * $course->completed_count / $course->enrollments_count, 1).'%' : '—' }}</td>
                        <td>{{ $course->enrollments_count ? number_format((float) $course->average_progress, 1).'%' : '—' }}</td>
                        <td><a class="admin-table-action" href="{{ route('instructor.students.index', ['course' => $course->getKey()]) }}">View learners</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
        {{ $courses->links() }}
    </section>
</div>
@endsection
