@extends('layouts.panel')
@section('title', $knowledgeBase->exists ? 'Edit help center' : 'Create help center')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">SUPPORT CONTENT</span>
<h1>{{ $knowledgeBase->exists ? 'Edit help center' : 'Create help center' }}</h1>
<p>Only published help centers are visible publicly.</p></div></div>
<section class="panel-card">
<form method="post" class="stack-form" action="{{ $knowledgeBase->exists ? route('admin.knowledge-base.update', $knowledgeBase) : route('admin.knowledge-base.store') }}">
@csrf @if($knowledgeBase->exists) @method('PUT') @endif
<label>Title <input name="title" maxlength="190" required value="{{ old('title', $knowledgeBase->title) }}"></label>
<label>Slug <input name="slug" maxlength="190" required pattern="[a-zA-Z0-9_-]+" value="{{ old('slug', $knowledgeBase->slug) }}"></label>
<label>Category <input name="category" maxlength="120" value="{{ old('category', $knowledgeBase->category) }}"></label>
<label>Introduction <textarea name="content" rows="6" maxlength="50000">{{ old('content', $knowledgeBase->content) }}</textarea></label>
<label class="checkline"><input type="checkbox" name="published" value="1" @checked(old('published', $knowledgeBase->published))> Publicly published</label>
<button class="btn btn-primary" type="submit">Save help center</button>
<a class="btn btn-quiet" href="{{ route('admin.knowledge-base.index') }}">Back to list</a>
</form>
</section>
@if($knowledgeBase->exists)
<section class="panel-card"><h2>Articles and topics</h2>
@foreach($knowledgeBase->topics as $topic)
<details class="builder-section"><summary>{{ $topic->title }}</summary>
<form class="stack-form compact-form" method="post" action="{{ route('admin.knowledge-base.topics.update', [$knowledgeBase, $topic]) }}">
@csrf @method('PUT')
<label>Title <input name="title" maxlength="190" required value="{{ $topic->title }}"></label>
<label>Slug <input name="slug" maxlength="190" required pattern="[a-zA-Z0-9_-]+" value="{{ $topic->slug }}"></label>
<label>Article text <textarea name="body" rows="8" maxlength="50000" required>{{ $topic->body }}</textarea></label>
<label>Order <input type="number" name="sort_order" min="0" max="100000" value="{{ $topic->sort_order }}" required></label>
<button type="submit" class="btn btn-secondary">Update article</button>
</form>
<form class="inline-form" method="post" action="{{ route('admin.knowledge-base.topics.destroy', [$knowledgeBase, $topic]) }}" data-confirm="Delete this article?">
@csrf @method('DELETE')<button class="btn btn-quiet" type="submit">Delete article</button></form>
</details>
@endforeach
<h3>Add article</h3>
<form method="post" class="stack-form" action="{{ route('admin.knowledge-base.topics.store', $knowledgeBase) }}">
@csrf
<label>Title <input name="title" maxlength="190" required></label>
<label>Slug <input name="slug" maxlength="190" required pattern="[a-zA-Z0-9_-]+"></label>
<label>Article text <textarea name="body" rows="8" maxlength="50000" required></textarea></label>
<label>Sort order <input type="number" name="sort_order" value="0" min="0" max="100000" required></label>
<button class="btn btn-primary" type="submit">Add article</button>
</form>
</section>
@endif
@endsection
