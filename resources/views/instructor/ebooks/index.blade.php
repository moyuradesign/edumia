@extends('layouts.panel')
@section('title', 'Manage ebooks')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">INSTRUCTOR · DIGITAL LIBRARY</span><h1>Manage ebooks</h1><p>Prepare original PDF ebooks for administrator approval.</p></div>
<a class="btn btn-primary" href="{{ route('instructor.ebooks.create') }}">Create ebook</a></div>
<section class="panel-card">
@if($ebooks->isEmpty())<p>No ebook drafts yet.</p>@else
<div class="table-scroll"><table><thead><tr><th>Title</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>@foreach($ebooks as $ebook)<tr>
<td><strong>{{ $ebook->title }}</strong><p class="subtle">{{ $ebook->slug }}</p></td>
<td>{{ $ebook->isFree() ? 'Free' : '$'.$ebook->price }}</td><td>{{ ucfirst($ebook->status) }}</td>
<td><a href="{{ route('instructor.ebooks.edit', $ebook) }}">Edit draft</a>
@if($ebook->status === 'published') · <a href="{{ route('ebooks.show', $ebook->slug) }}">View</a>@endif
</td></tr>@endforeach</tbody></table></div>{{ $ebooks->links() }}@endif
</section>
@endsection
