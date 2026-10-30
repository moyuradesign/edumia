@extends('layouts.panel')
@section('title','Instructor dashboard')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">INSTRUCTOR WORKSPACE</span><h1>Your courses</h1><p>Build structured courses and submit them to the administrator for publishing.</p></div><a class="btn btn-primary" href="{{ route('instructor.courses.create') }}">Create course</a></div>
<div class="metric-grid"><article class="metric"><span>My courses</span><strong>{{ $courses->count() }}</strong></article><article class="metric"><span>Course enrollments</span><strong>{{ $enrollments }}</strong></article></div>
<div class="panel-card"><h2>Manage courses</h2>
@if($courses->isEmpty())<p>Your course library is empty.</p>@else<div class="course-grid">
@foreach($courses as $course)<article class="panel-card"><span class="section-kicker">{{ ucfirst($course->status) }}</span><h3>{{ $course->title }}</h3><p>{{ $course->lessons_count }} lessons · {{ $course->enrollments_count }} enrollments</p><a class="btn btn-secondary" href="{{ route('instructor.courses.edit',$course) }}">Edit course</a></article>@endforeach</div>@endif</div>
@endsection
