@extends('layouts.panel')
@section('title',$lesson->title)
@section('content')
<a class="back-link" href="{{ route('learning.course',$course) }}">← Back to {{ $course->title }}</a>
<section class="panel-card"><span class="section-kicker">LESSON</span><h1>{{ $lesson->title }}</h1>
@if($lesson->video_url)<p><a href="{{ $lesson->video_url }}" target="_blank" rel="noopener noreferrer">Open lesson video in new tab</a></p>@endif
<div class="lesson-content">{!! nl2br(e($lesson->content ?? '')) !!}</div>
<form action="{{ route('learning.complete',[$course,$lesson]) }}" method="post">@csrf<button class="btn btn-primary">Mark lesson complete</button></form></section>
@endsection
