@extends('layouts.panel')
@section('title','My learning')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">STUDENT WORKSPACE</span><h1>My learning</h1><p>Continue your courses and download printable certificates after completion.</p></div><a href="{{ route('courses.index') }}" class="btn btn-secondary">Explore courses</a></div>
<div class="metric-grid"><article class="metric"><span>Enrolled courses</span><strong>{{ $enrollments->count() }}</strong></article><article class="metric"><span>Passed quiz attempts</span><strong>{{ $passed }}</strong></article><article class="metric"><span>Certificates</span><strong>{{ $certificates->count() }}</strong></article></div>
<section class="panel-card"><h2>My courses</h2>@if($enrollments->isEmpty())<p>You haven't enrolled yet. Browse free published courses to start.</p>@else
<div class="course-grid">@foreach($enrollments as $enrollment)<article class="panel-card"><h3>{{ $enrollment->course->title }}</h3><p>{{ $enrollment->progress_percent }}% complete</p><progress max="100" value="{{ $enrollment->progress_percent }}"></progress><p><a href="{{ route('learning.course',$enrollment->course) }}" class="btn btn-primary">Continue learning</a></p></article>@endforeach</div>@endif</section>
<section class="panel-card"><h2>Certificates</h2>@if($certificates->isEmpty())<p>Complete every published lesson and pass all published quizzes to earn a certificate.</p>@else
@foreach($certificates as $certificate)<p><a href="{{ route('certificate.show',$certificate) }}">{{ $certificate->course->title }} · {{ $certificate->certificate_number }}</a></p>@endforeach@endif</section>
@endsection
