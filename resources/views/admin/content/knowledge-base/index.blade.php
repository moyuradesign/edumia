@extends('layouts.panel')
@section('title', 'Knowledge base')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">SUPPORT CONTENT</span><h1>Knowledge base</h1>
<p>Maintain searchable help articles and their topics.</p></div>
<a class="btn btn-primary" href="{{ route('admin.knowledge-base.create') }}">Create help center</a></div>
<section class="panel-card">
@forelse($bases as $base)
<article class="builder-section"><h2>{{ $base->title }}</h2>
<p>{{ $base->category ?? 'General' }} · {{ $base->topics_count }} topics · {{ $base->published ? 'Published' : 'Draft' }}</p>
<a href="{{ route('admin.knowledge-base.edit', $base) }}">Manage articles</a>
@if($base->published) · <a href="{{ route('help.show', $base->slug) }}">Public view</a>@endif
</article>
@empty<p>No help centers yet.</p>@endforelse
{{ $bases->links() }}
</section>
@endsection
