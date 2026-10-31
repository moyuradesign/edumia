@extends('layouts.panel')
@section('title', $bootcamp->title)
@section('content')
<div class="panel-heading"><div><span class="section-kicker">YOUR COHORT</span><h1>{{ $bootcamp->title }}</h1>
<p>{{ $bootcamp->start_date?->format('M d, Y') }} — {{ $bootcamp->end_date?->format('M d, Y') }}</p></div>
<a class="btn btn-quiet" href="{{ route('bootcamps.my') }}">My bootcamps</a></div>
<section class="panel-card"><h2>Curriculum and resources</h2>
@foreach($bootcamp->modules as $module)
<article class="builder-section"><h3>{{ $module->title }}</h3>
<p>{{ $module->description }}</p>
@foreach($module->resources as $resource)
<p><a href="{{ $resource->external_url }}" rel="noopener noreferrer" target="_blank">{{ $resource->title }}</a></p>
@endforeach
</article>@endforeach</section>
<section class="panel-card"><h2>Private cohort sessions</h2>
@forelse($bootcamp->sessions as $session)
<article class="builder-section"><h3>{{ $session->title }}</h3>
<p>{{ $session->starts_at?->format('M d, Y H:i') }} — {{ $session->ends_at?->format('M d, Y H:i') }}</p>
<a class="btn btn-secondary" href="{{ $session->meeting_url }}" target="_blank" rel="noopener noreferrer">Join external meeting</a>
</article>
@empty<p>No live sessions scheduled yet.</p>@endforelse
</section>
@endsection
