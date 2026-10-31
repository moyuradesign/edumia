@extends('layouts.panel')
@section('title', $bootcamp->exists ? 'Edit bootcamp' : 'Create bootcamp')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">INSTRUCTOR · COHORT BUILDER</span>
<h1>{{ $bootcamp->exists ? $bootcamp->title : 'New bootcamp' }}</h1>
<p>Prepare curriculum, learning resources, capacity and session dates.</p></div>
<a class="btn btn-quiet" href="{{ route('instructor.bootcamps.index') }}">Back to bootcamps</a></div>
<section class="panel-card">
<form class="stack-form" method="post" action="{{ $bootcamp->exists ? route('instructor.bootcamps.update', $bootcamp) : route('instructor.bootcamps.store') }}">
@csrf @if($bootcamp->exists) @method('PUT') @endif
<label>Title <input name="title" maxlength="190" required value="{{ old('title', $bootcamp->title) }}"></label>
<label>Slug <input name="slug" pattern="[a-zA-Z0-9_-]+" maxlength="190" required value="{{ old('slug', $bootcamp->slug) }}"></label>
<label>Description <textarea name="description" rows="8" maxlength="30000">{{ old('description', $bootcamp->description) }}</textarea></label>
<div class="form-two">
<label>Price (USD) <input name="price" type="number" step="0.01" min="0" required value="{{ old('price', $bootcamp->price ?? 0) }}"></label>
<label>Maximum learners <input name="capacity" type="number" min="1" max="100000" required value="{{ old('capacity', $bootcamp->capacity ?? 20) }}"></label>
<label>Cohort start <input name="start_date" type="date" required value="{{ old('start_date', $bootcamp->start_date?->format('Y-m-d')) }}"></label>
<label>Cohort end <input name="end_date" type="date" required value="{{ old('end_date', $bootcamp->end_date?->format('Y-m-d')) }}"></label>
</div><p class="subtle">Saving returns the cohort to draft. Only free cohorts can be published until verified payment checkout is ready.</p>
<button class="btn btn-primary" type="submit">Save cohort</button></form>
</section>
@if($bootcamp->exists)
<section class="panel-card"><h2>Curriculum modules</h2>
@foreach($bootcamp->modules as $module)
<article class="builder-section"><h3>{{ $module->title }}</h3><p>{{ $module->description }}</p>
@foreach($module->resources as $resource)
<p>{{ $resource->title }} · private cohort resource</p>
@endforeach
<details><summary>Add a resource link</summary>
<form class="stack-form compact-form" method="post" action="{{ route('instructor.bootcamps.resources.store', [$bootcamp, $module]) }}">
@csrf
<label>Resource title <input name="title" maxlength="190" required></label>
<label>HTTPS URL <input name="external_url" type="url" maxlength="255" placeholder="https://" required></label>
<label>Order <input name="sort_order" type="number" min="0" max="100000" value="0" required></label>
<button class="btn btn-secondary" type="submit">Add resource</button>
</form></details>
</article>@endforeach
<h3>Add module</h3>
<form class="stack-form" method="post" action="{{ route('instructor.bootcamps.modules.store', $bootcamp) }}">
@csrf<label>Module title <input name="title" maxlength="190" required></label>
<label>Description <textarea name="description" maxlength="5000" rows="4"></textarea></label>
<label>Sort order <input name="sort_order" type="number" min="0" max="100000" value="0" required></label>
<button class="btn btn-primary" type="submit">Add module</button></form>
</section>
<section class="panel-card"><h2>Private live sessions</h2>
@foreach($bootcamp->sessions as $session)
<article class="builder-section"><h3>{{ $session->title }}</h3>
<p>{{ $session->starts_at?->format('M d, Y H:i') }} — {{ $session->ends_at?->format('H:i') }}</p></article>
@endforeach
<form class="stack-form" method="post" action="{{ route('instructor.bootcamps.sessions.store', $bootcamp) }}">
@csrf
<label>Session title <input name="title" required maxlength="190"></label>
<label>Private meeting URL <input name="meeting_url" type="url" maxlength="255" placeholder="https://" required></label>
<div class="form-two"><label>Start <input name="starts_at" type="datetime-local" required></label>
<label>End <input name="ends_at" type="datetime-local" required></label></div>
<button class="btn btn-primary" type="submit">Schedule session</button></form>
</section>
@endif
@endsection
