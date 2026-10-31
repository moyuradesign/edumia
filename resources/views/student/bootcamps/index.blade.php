@extends('layouts.panel')
@section('title', 'My bootcamps')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">YOUR LEARNING</span><h1>My bootcamps</h1>
<p>Access cohorts you are enrolled in.</p></div>
<a class="btn btn-quiet" href="{{ route('bootcamps.index') }}">Find bootcamps</a></div>
<section class="panel-card">
@forelse($registrations as $registration)
<article class="builder-section"><h2>{{ $registration->bootcamp?->title ?? 'Unavailable cohort' }}</h2>
@if($registration->bootcamp)
<a class="btn btn-primary" href="{{ route('bootcamps.classroom', $registration->bootcamp) }}">Open cohort classroom</a>
@endif</article>
@empty<p>No bootcamp registrations yet.</p>@endforelse
{{ $registrations->links() }}</section>
@endsection
