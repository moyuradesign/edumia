@extends('layouts.panel')
@section('title', 'Course categories')
@section('content')
<div class="panel-heading">
    <div><span class="section-kicker">COURSE MANAGEMENT</span><h1>Categories</h1><p>Organize the course catalogue and its hierarchy.</p></div>
    <a class="btn btn-primary" href="{{ route('admin.categories.create') }}">Add category</a>
</div>
<section class="panel-card">
    @if($categories->isEmpty())
        <p>No categories yet. Create your first category.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Name</th><th>Slug</th><th>Parent</th><th>Courses</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @foreach($categories as $category)
                    <tr>
                        <td><strong>{{ $category->name }}</strong></td>
                        <td>{{ $category->slug }}</td>
                        <td>{{ $category->parent?->name ?? '—' }}</td>
                        <td>{{ $category->courses_count }}</td>
                        <td>{{ ucfirst($category->status) }}</td>
                        <td>
                            <a href="{{ route('admin.categories.edit', $category) }}">Edit</a>
                            @if($category->courses_count === 0 && $category->children_count === 0)
                                <form class="inline-form" method="post" action="{{ route('admin.categories.destroy', $category) }}" data-confirm="Delete this category?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-small btn-quiet">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $categories->links() }}
    @endif
</section>
@endsection
