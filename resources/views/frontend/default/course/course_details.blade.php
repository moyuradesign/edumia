@extends('layouts.default')
@section('title',$course->title.' — Edumia')
@section('content')
<section class="detail-section"><div class="container"><a class="back-link" href="{{ \App\Helpers\Localization::url($locale,'courses') }}">← Back to courses</a>
<div class="detail-grid"><div><span class="section-kicker">{{ $course->category?->name??'Learning' }}</span><h1>{{ $course->title }}</h1><p class="detail-lead">{{ $course->summary }}</p>
<div class="detail-stats"><span>{{ $course->lessons_count }} lessons</span><span>{{ ucfirst($course->level) }}</span><span>{{ $course->isFree()?'Free':('$'.$course->price) }}</span></div>
<p>{{ $course->description }}</p><div class="curriculum"><h2>Course curriculum</h2>
@foreach($course->sections as $section)<h3>{{ $section->title }}</h3><ul>@foreach($section->lessons as $lesson)<li>✓ {{ $lesson->title }}</li>@endforeach</ul>@endforeach
</div></div><aside class="detail-aside"><div class="detail-cover tone-amber"><span>✦</span><b>edumia.</b></div><div class="detail-aside-body">
<p>Instructor: <strong>{{ $course->instructor->name }}</strong></p>
@if(auth()->check())
@if(auth()->user()->enrollments()->where('course_id',$course->id)->exists())<a class="btn btn-primary btn-block" href="{{ route('learning.course',$course) }}">Continue learning</a>
@elseif($course->isFree())<form action="{{ route('enrollments.store',$course) }}" method="post">@csrf<button class="btn btn-primary btn-block">Enroll for free</button></form>
@else<p>Enrollment unavailable until secure checkout is enabled.</p>@endif
@else<a class="btn btn-primary btn-block" href="{{ route('login') }}">Sign in to enroll</a>@endif
</div></aside></div></div></section>
@endsection
