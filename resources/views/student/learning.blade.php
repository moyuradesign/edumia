@extends('layouts.panel')
@section('title',$course->title)
@section('content')
<div class="panel-heading"><div><span class="section-kicker">YOUR CLASSROOM</span><h1>{{ $course->title }}</h1><p>{{ $course->summary }}</p></div><a href="{{ route('dashboard') }}" class="btn btn-secondary">My learning</a></div>
<section class="panel-card"><h2>Lesson progress: {{ $enrollment->progress_percent }}%</h2><progress max="100" value="{{ $enrollment->progress_percent }}"></progress>
@if($enrollment->completed_at)<p>Completed {{ $enrollment->completed_at->format('M j, Y') }}. Your certificate is available on the dashboard.</p>@endif</section>
<section class="panel-card"><h2>Lessons</h2>@forelse($sections as $section)<div class="builder-section"><h3>{{ $section->title }}</h3><ul class="lesson-list">
@forelse($section->lessons as $lesson)<li><a href="{{ route('learning.lesson',[$course,$lesson]) }}">{{ $lesson->title }}</a><span>{{ in_array($lesson->id,$completed)?'✓ Complete':'Not started' }}</span></li>@empty<li>No published lessons in this section.</li>@endforelse</ul></div>@empty<p>No lessons have been published yet.</p>@endforelse</section>
<section class="panel-card"><h2>Quizzes</h2>@forelse($quizzes as $quiz)<p><a href="{{ route('learning.quiz',$quiz) }}">{{ $quiz->title }} ({{ $quiz->questions_count }} questions, passing score {{ $quiz->passing_score }}%)</a></p>@empty<p>No quizzes are published yet.</p>@endforelse</section>
<section class="panel-card">
    <h2>Assignments</h2>
    @forelse($assignments as $assignment)
        @php $submitted = $assignmentSubmissions->get($assignment->id); @endphp
        <div class="builder-section">
            <h3>{{ $assignment->title }}</h3>
            <p>{{ $assignment->max_score }} points · {{ $assignment->due_at?->format('M d, Y H:i') ?? 'No deadline' }}
            · {{ $submitted?->graded_at ? 'Graded' : ($submitted ? 'Submitted' : 'Not submitted') }}</p>
            <a class="btn btn-secondary" href="{{ route('learning.assignments.show', [$course, $assignment]) }}">View assignment</a>
        </div>
    @empty
        <p>No assignments are currently published.</p>
    @endforelse
</section>
@endsection
