@extends('layouts.panel')
@section('title',$quiz->title)
@section('content')
<a class="back-link" href="{{ route('learning.course',$quiz->course) }}">← Back to learning</a>
<section class="panel-card"><span class="section-kicker">QUIZ</span><h1>{{ $quiz->title }}</h1><p>{{ $quiz->instructions }} · Passing score {{ $quiz->passing_score }}% · Attempts {{ $attempts }}/{{ $quiz->attempts_allowed }}</p>
@if($attempts < $quiz->attempts_allowed)
<form class="stack-form" method="post" action="{{ route('learning.quiz.submit',$quiz) }}">@csrf
@foreach($quiz->questions as $question)<fieldset class="question-block"><legend>{{ $loop->iteration }}. {{ $question->question }} ({{ $question->points }} points)</legend>
@foreach($question->options??[] as $idx=>$choice)<label class="checkline"><input type="radio" name="answers[{{ $question->id }}]" value="{{ $idx }}" required>{{ $choice }}</label>@endforeach</fieldset>@endforeach
<button class="btn btn-primary">Submit quiz</button></form>
@else<p>No attempts remain. Contact your instructor if you need assistance.</p>@endif</section>
@if($past->isNotEmpty())<section class="panel-card"><h2>Your attempts</h2><ul>@foreach($past as $submission)<li>{{ $submission->submitted_at?->format('M j, Y H:i') }} — {{ $submission->score }}% — {{ $submission->passed?'Passed':'Not passed' }}</li>@endforeach</ul></section>@endif
@endsection
