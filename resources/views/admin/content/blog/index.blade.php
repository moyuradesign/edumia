@extends('layouts.panel')
@section('title', 'Blog manager')
@section('content')
<div class="admin-welcome">
    <div><span class="admin-eyebrow"><span class="admin-eyebrow-dot" aria-hidden="true"></span> CONTENT / JOURNAL</span>
        <h1>Blog manager</h1><p>Create, revise and publish articles with categories and original images.</p></div>
    <a class="admin-action-primary" href="{{ route('admin.blogs.create') }}">Create article</a>
</div>
<section class="admin-widget">
    <div class="admin-widget-heading"><div><h2>Articles</h2><p>Drafts stay private until published.</p></div></div>
    @if($posts->isEmpty())
        <div class="admin-empty"><strong>No articles yet</strong><p>Use Create article to write the first post.</p></div>
    @else
        <div class="table-scroll"><table class="admin-table">
            <thead><tr><th>Article</th><th>Category</th><th>Author</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @foreach($posts as $post)
                    <tr>
                        <td><strong>{{ $post->title }}</strong><small>{{ $post->slug }}</small></td>
                        <td>{{ $post->category?->name ?? 'Uncategorized' }}</td>
                        <td>{{ $post->author?->name ?? 'Unknown' }}</td>
                        <td><span class="admin-status admin-status--{{ $post->status === 'published' ? 'success' : 'neutral' }}">{{ ucfirst($post->status) }}</span></td>
                        <td><div class="blog-table-actions">
                            <a class="admin-table-action" href="{{ route('admin.blogs.edit', $post) }}">Edit</a>
                            @if($post->status === 'published')<a class="admin-table-action" href="{{ route('blog.show', $post->slug) }}">View</a>@endif
                            <form method="post" action="{{ route('admin.blogs.destroy', $post) }}"
                                data-confirm="Delete this article permanently?">
                                @csrf @method('DELETE')
                                <button class="admin-table-action" type="submit">Delete</button>
                            </form>
                        </div></td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
        {{ $posts->links() }}
    @endif
</section>
@endsection
