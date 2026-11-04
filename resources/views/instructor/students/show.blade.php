@extends('layouts.panel')
@section('title', 'Learner progress')
@section('content')
<div class="member-dashboard">
    <div class="admin-welcome member-hero">
        <div>
            <span class="admin-eyebrow"><span class="admin-eyebrow-dot" aria-hidden="true"></span> INSTRUCTOR / LEARNER PROGRESS</span>
            <h1>{{ $enrollment->user->name }}</h1>
            <p>{{ $enrollment->course->title }} · {{ ucfirst(str_replace('_', ' ', $enrollment->status)) }}</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('instructor.students.index', ['course' => $enrollment->course_id]) }}">Back to students</a>
            <a class="admin-action-primary" href="{{ route('instructor.courses.edit', $enrollment->course) }}">Manage course</a>
        </div>
    </div>
    <div class="admin-stats" aria-label="Learner progress metrics">
        <div class="admin-stat">
            <span class="admin-stat-top"><span>Overall progress</span><span class="admin-stat-icon admin-stat-icon--orange" aria-hidden="true">◉</span></span>
            <strong>{{ min(100, max(0, (int) $enrollment->progress_percent)) }}%</strong>
            <small>Recorded course progress</small>
        </div>
        <div class="admin-stat">
            <span class="admin-stat-top"><span>Lessons completed</span><span class="admin-stat-icon admin-stat-icon--blue" aria-hidden="true">✓</span></span>
            <strong>{{ count($completedLessonIds) }} / {{ $lessons->count() }}</strong>
            <small>Published course lessons</small>
        </div>
        <div class="admin-stat">
            <span class="admin-stat-top"><span>Quizzes passed</span><span class="admin-stat-icon admin-stat-icon--mint" aria-hidden="true">✓</span></span>
            <strong>{{ $quizzes->filter(fn ($quiz) => ($quizAttempts->get($quiz->id) ?? collect())->contains('passed', true))->count() }} / {{ $quizzes->count() }}</strong>
            <small>Published assessments</small>
        </div>
        <div class="admin-stat">
            <span class="admin-stat-top"><span>Certificate</span><span class="admin-stat-icon admin-stat-icon--violet" aria-hidden="true">◇</span></span>
            <strong>{{ $certificate ? 'Issued' : 'Not issued' }}</strong>
            <small>{{ $certificate?->issued_at?->format('M j, Y') ?? 'No valid certificate recorded' }}</small>
        </div>
    </div>
    <div class="admin-data-grid">
        <section class="admin-widget" aria-labelledby="learner-lessons-title">
            <div class="admin-widget-heading"><div><span class="admin-widget-kicker">COURSE CONTENT</span><h2 id="learner-lessons-title">Lesson activity</h2>
                <p>Completion records for published lessons.</p></div></div>
            @if($lessons->isEmpty())
                <div class="admin-empty"><strong>No published lessons</strong><p>Lesson activity appears after lessons are published.</p></div>
            @else
                <div class="table-scroll"><table class="admin-table">
                    <thead><tr><th>Lesson</th><th>Status</th></tr></thead>
                    <tbody>@foreach($lessons as $lesson)
                        <tr><td><strong>{{ $lesson->title }}</strong></td>
                            <td><span class="admin-status admin-status--{{ in_array((int) $lesson->id, $completedLessonIds, true) ? 'success' : 'neutral' }}">
                                {{ in_array((int) $lesson->id, $completedLessonIds, true) ? 'Completed' : 'Not completed' }}
                            </span></td></tr>
                    @endforeach</tbody>
                </table></div>
            @endif
        </section>
        <section class="admin-widget" aria-labelledby="learner-quizzes-title">
            <div class="admin-widget-heading"><div><span class="admin-widget-kicker">ASSESSMENTS</span><h2 id="learner-quizzes-title">Quiz results</h2>
                <p>Latest attempt and pass history per quiz.</p></div></div>
            @if($quizzes->isEmpty())
                <div class="admin-empty"><strong>No published quizzes</strong><p>Quiz results appear when assessments are available.</p></div>
            @else
                <div class="table-scroll"><table class="admin-table">
                    <thead><tr><th>Quiz</th><th>Attempts</th><th>Latest score</th><th>Result</th></tr></thead>
                    <tbody>@foreach($quizzes as $quiz)
                        @php $attempts = $quizAttempts->get($quiz->id, collect()); $latest = $attempts->first(); @endphp
                        <tr>
                            <td><strong>{{ $quiz->title }}</strong></td>
                            <td>{{ $attempts->count() }}</td>
                            <td>{{ $latest?->score === null ? '—' : number_format((float) $latest->score, 1).'%' }}</td>
                            <td><span class="admin-status admin-status--{{ $attempts->contains('passed', true) ? 'success' : 'neutral' }}">
                                {{ $attempts->contains('passed', true) ? 'Passed' : ($latest ? 'Not passed' : 'Not attempted') }}
                            </span></td>
                        </tr>
                    @endforeach</tbody>
                </table></div>
            @endif
        </section>
    </div>
    <section class="admin-widget" aria-labelledby="learner-assignments-title">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">COURSEWORK</span><h2 id="learner-assignments-title">Assignments</h2>
            <p>Submission and grading status for this course.</p></div></div>
        @if($assignments->isEmpty())
            <div class="admin-empty"><strong>No published assignments</strong><p>Assignment records will appear after assignments are published.</p></div>
        @else
            <div class="table-scroll"><table class="admin-table">
                <thead><tr><th>Assignment</th><th>Submission</th><th>Grade</th><th>Action</th></tr></thead>
                <tbody>@foreach($assignments as $assignment)
                    @php $submission = $assignment->submissions->first(); @endphp
                    <tr>
                        <td><strong>{{ $assignment->title }}</strong></td>
                        <td>{{ $submission?->submitted_at?->format('M j, Y') ?? 'Not submitted' }}</td>
                        <td>{{ $submission?->graded_at ? number_format((float) $submission->score, 1).' / '.number_format((float) $assignment->max_score, 0) : ($submission ? 'Awaiting grade' : '—') }}</td>
                        <td><a class="admin-table-action" href="{{ route('instructor.assignments.gradebook', [$enrollment->course, $assignment]) }}">Open gradebook</a></td>
                    </tr>
                @endforeach</tbody>
            </table></div>
        @endif
    </section>
</div>
@endsection
