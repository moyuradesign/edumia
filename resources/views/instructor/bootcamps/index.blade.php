@extends('layouts.panel')
@section('title', 'Manage bootcamps')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">INSTRUCTOR · BOOTCAMPS</span><h1>Manage bootcamps</h1>
<p>Build focused cohorts with learning modules and scheduled sessions.</p></div>
<a class="btn btn-primary" href="{{ route('instructor.bootcamps.create') }}">New bootcamp</a></div>
<section class="panel-card">
@forelse($bootcamps as $bootcamp)
<article class="builder-section"><h2>{{ $bootcamp->title }}</h2>
<p>{{ ucfirst($bootcamp->status) }} · {{ $bootcamp->isFree() ? 'Free' : '$'.$bootcamp->price }}
· {{ $bootcamp->start_date?->format('M d, Y') ?? 'Date TBD' }}</p>
<a href="{{ route('instructor.bootcamps.edit', $bootcamp) }}">Edit cohort</a></article>
@empty<p>No bootcamps created yet.</p>@endforelse
{{ $bootcamps->links() }}
</section>
@endsection
