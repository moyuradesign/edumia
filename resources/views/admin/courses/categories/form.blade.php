@extends('layouts.panel')
@section('title', $category->exists ? 'Edit category' : 'Create category')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">COURSE MANAGEMENT</span>
    <h1>{{ $category->exists ? 'Edit category' : 'Create category' }}</h1>
    <p>Use a unique URL slug and an optional parent category.</p>
</div></div>
<section class="panel-card">
    <form method="post" class="stack-form" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
        @csrf
        @if($category->exists) @method('PUT') @endif
        <label>Name <input name="name" maxlength="160" required value="{{ old('name', $category->name) }}"></label>
        <label>Slug <input name="slug" maxlength="190" pattern="[a-zA-Z0-9_-]+" required value="{{ old('slug', $category->slug) }}"></label>
        <label>Description <textarea name="description" rows="4" maxlength="5000">{{ old('description', $category->description) }}</textarea></label>
        <label>Parent category
            <select name="parent_id"><option value="">None</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $parent->id)>{{ $parent->name }}</option>
                @endforeach
            </select>
        </label>
        <div class="form-two">
            <label>Status
                <select name="status"><option value="active" @selected(old('status', $category->status ?? 'active') === 'active')>Active</option><option value="inactive" @selected(old('status', $category->status) === 'inactive')>Inactive</option></select>
            </label>
            <label>Sort order <input type="number" name="sort_order" min="0" max="100000" required value="{{ old('sort_order', $category->sort_order ?? 0) }}"></label>
        </div>
        <div><button class="btn btn-primary" type="submit">Save category</button> <a class="btn btn-quiet" href="{{ route('admin.categories.index') }}">Cancel</a></div>
    </form>
</section>
@endsection
