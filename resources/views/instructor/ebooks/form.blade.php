@extends('layouts.panel')
@section('title', $ebook->exists ? 'Edit ebook' : 'New ebook')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">INSTRUCTOR · DIGITAL LIBRARY</span><h1>{{ $ebook->exists ? 'Edit ebook' : 'Create ebook' }}</h1>
<p>Only original or properly licensed PDF material may be uploaded.</p></div></div>
<section class="panel-card"><form class="stack-form" method="post" enctype="multipart/form-data"
    action="{{ $ebook->exists ? route('instructor.ebooks.update', $ebook) : route('instructor.ebooks.store') }}">
@csrf @if($ebook->exists) @method('PUT') @endif
<label>Title <input name="title" maxlength="190" required value="{{ old('title', $ebook->title) }}"></label>
<label>URL slug <input name="slug" pattern="[a-zA-Z0-9_-]+" maxlength="190" required value="{{ old('slug', $ebook->slug) }}"></label>
<label>Description <textarea name="description" rows="7" maxlength="20000">{{ old('description', $ebook->description) }}</textarea></label>
<label>Price (USD) <input name="price" type="number" required step="0.01" min="0" max="99999999" value="{{ old('price', $ebook->price ?? 0) }}"></label>
<p class="subtle">Only free ebooks can be published at this stage. Paid checkout is not available.</p>
<label>Private PDF file (maximum 20 MB)
<input type="file" name="file" accept="application/pdf,.pdf" @required(!$ebook->exists)></label>
@if($ebook->exists && $ebook->file_path)<p>Current PDF is stored privately. A new file replaces the existing PDF and returns the ebook to draft.</p>@endif
<p class="subtle">Saving changes returns the ebook to draft so the administrator can review it.</p>
<button class="btn btn-primary" type="submit">Save ebook draft</button>
<a class="btn btn-quiet" href="{{ route('instructor.ebooks.index') }}">Cancel</a>
</form></section>
@endsection
