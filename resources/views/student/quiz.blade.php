@extends('layouts.panel')
@section('title', $quiz->title)
@section('content')
<div class="member-dashboard">
    <div class="admin-welcome member-hero">
        <div><span class="admin-eyebrow">STUDENT / ASSESSMENTS</span>
            <h1>{{ $quiz->title }}</h1>
            <p>{{ $quiz->instructions ?: 'Complete every question and submit when you are ready.' }}</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('learning.course',$quiz->course) }}">Back to course</a>
        </div>
    </div>
    <div class="admin-stats" aria-label="Quiz rules">
        <div class="admin-stat"><span class="admin-stat-top"><span>Passing score</span></span>
            <strong>{{ $quiz->passing_score }}%</strong><small>Minimum required to pass</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>Attempts used</span></span>
            <strong>{{ $attempts }} / {{ $quiz->attempts_allowed }}</strong><small>Expired timed sessions also use one attempt</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>Time limit</span></span>
            <strong>{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' min' : 'None' }}</strong>
            <small>{{ $requiresSession ? 'Your attempt must be started first' : 'Untimed knowledge check' }}</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>Question order</span></span>
            <strong>{{ $quiz->randomize_questions ? 'Shuffled' : 'Standard' }}</strong><small>Each answer remains tied to its question</small></div>
    </div>
    <section class="admin-widget">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">KNOWLEDGE CHECK</span>
            <h2>{{ $activeSession ? 'Active assessment' : 'Your assessment' }}</h2></div></div>
        @if($requiresSession && !$activeSession && $attempts < $quiz->attempts_allowed)
            <p>Start a new attempt when you are ready. The server enforces the deadline, even if the browser timer is closed.</p>
            <form method="post" action="{{ route('learning.quiz.start',$quiz) }}">
                @csrf
                <button type="submit" class="btn btn-primary">Start assessment</button>
            </form>
        @elseif($attempts >= $quiz->attempts_allowed && !$activeSession)
            <p>No attempts remain. Contact your instructor if additional review is needed.</p>
        @else
            @if($activeSession)
                <p><strong>Attempt opened:</strong> {{ $activeSession->started_at->format('M j, Y H:i:s') }} UTC</p>
                @if($quiz->time_limit_minutes)
                    <p role="timer" data-quiz-deadline="{{ $activeSession->expires_at->utc()->toIso8601String() }}">
                        Time remaining: <strong data-quiz-remaining>—</strong>
                    </p>
                @endif
            @endif
            <form class="stack-form" method="post" action="{{ route('learning.quiz.submit',$quiz) }}" data-quiz-form>
                @csrf
                @if($activeSession)<input type="hidden" name="attempt_token" value="{{ $activeSession->token }}">@endif
                @foreach($questions as $question)
                    <fieldset class="question-block">
                        <legend>{{ $loop->iteration }}. {{ $question->question }} ({{ $question->points }} {{ $question->points===1?'point':'points' }})</legend>
                        @foreach($question->options ?? [] as $idx=>$choice)
                            <label class="checkline">
                                <input type="radio" name="answers[{{ $question->id }}]" value="{{ $idx }}" required>
                                {{ $choice }}
                            </label>
                        @endforeach
                    </fieldset>
                @endforeach
                <button type="submit" class="btn btn-primary" data-quiz-submit>Submit answers for grading</button>
            </form>
        @endif
    </section>
    @if($past->isNotEmpty())
        <section class="admin-widget">
            <div class="admin-widget-heading"><div><span class="admin-widget-kicker">RESULTS</span><h2>Your previous submissions</h2>
                <p>These results come from recorded, server-graded attempts.</p></div></div>
            <div class="table-scroll"><table class="admin-table">
                <thead><tr><th>Submitted</th><th>Score</th><th>Result</th></tr></thead>
                <tbody>@foreach($past as $submission)
                    <tr><td>{{ $submission->submitted_at?->format('M j, Y H:i') ?? 'Unknown' }}</td>
                        <td>{{ number_format((float) $submission->score,1) }}%</td>
                        <td><span class="admin-status admin-status--{{ $submission->passed ? 'success' : 'neutral' }}">{{ $submission->passed ? 'Passed' : 'Not passed' }}</span></td></tr>
                @endforeach</tbody>
            </table></div>
        </section>
    @endif
</div>
@endsection
