@extends('layouts.panel')
@section('title', $blog->exists ? 'Edit article' : 'Create article')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">CONTENT MANAGEMENT</span><h1>{{ $blog->exists ? 'Edit article' : 'Create article' }}</h1></div></div>
<section class="panel-card">
    <form class="stack-form" method="post" action="{{ $blog->exists ? route('admin.blogs.update', $blog) : route('admin.blogs.store') }}">
        @csrf @if($blog->exists) @method('PUT') @endif
        <label>Title <input name="title" required maxlength="190" value="{{ old('title', $blog->title) }}"></label>
        <label>URL slug <input name="slug" required pattern="[a-zA-Z0-9_-]+" maxlength="190" value="{{ old('slug', $blog->slug) }}"></label>
        <label>Excerpt <textarea name="excerpt" rows="3" maxlength="1500">{{ old('excerpt', $blog->excerpt) }}</textarea></label>
        <label>Article body (plain text) <textarea name="body" rows="12" maxlength="100000" required>{{ old('body', $blog->body) }}</textarea></label>
        <p class="subtle">Plain text is rendered safely. HTML scripts are never executed in public articles.</p>
        <label>Publication status
            <select name="status"><option value="draft" @selected(old('status', $blog->status ?? 'draft') === 'draft')>Draft</option><option value="published" @selected(old('status', $blog->status) === 'published')>Published</option></select>
        </label>
        <div><button class="btn btn-primary" type="submit">Save article</button> <a href="{{ route('admin.blogs.index') }}" class="btn btn-quiet">Cancel</a></div>
    </form>
</section>
@endsection
