@extends('layouts.panel')
@section('title', 'Bootcamp approvals')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">LEARNING APPROVAL</span><h1>Bootcamp approvals</h1>
<p>Review free cohort curricula before public enrollment is enabled.</p></div></div>
<section class="panel-card">
@forelse($bootcamps as $bootcamp)
<article class="builder-section">
<h2>{{ $bootcamp->title }}</h2>
<p>{{ $bootcamp->instructor?->name ?? 'Unknown instructor' }} · {{ $bootcamp->modules_count }} modules ·
{{ $bootcamp->isFree() ? 'Free' : '$'.$bootcamp->price }} · {{ ucfirst($bootcamp->status) }}</p>
<a href="{{ route('instructor.bootcamps.edit', $bootcamp) }}">Review curriculum</a>
<form class="inline-form" method="post" action="{{ route('admin.bootcamps.publication', $bootcamp) }}">
@csrf @method('PATCH')
<input type="hidden" name="published" value="{{ $bootcamp->status === 'published' ? 0 : 1 }}">
<button class="btn btn-secondary" type="submit">{{ $bootcamp->status === 'published' ? 'Unpublish' : 'Publish free bootcamp' }}</button>
</form></article>
@empty<p>No bootcamps awaiting review.</p>@endforelse
{{ $bootcamps->links() }}
</section>
@endsection
