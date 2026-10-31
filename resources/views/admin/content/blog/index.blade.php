@extends('layouts.panel')
@section('title', 'Blog manager')
@section('content')
<div class="panel-heading">
    <div><span class="section-kicker">CONTENT MANAGEMENT</span><h1>Blog manager</h1><p>Publish original articles and announcements.</p></div>
    <a class="btn btn-primary" href="{{ route('admin.blogs.create') }}">New article</a>
</div>
<section class="panel-card">
@if($posts->isEmpty())<p>No articles yet.</p>@else
    <div class="table-scroll"><table>
    <thead><tr><th>Article</th><th>Author</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>@foreach($posts as $post)
    <tr><td><strong>{{ $post->title }}</strong><br><span class="subtle">{{ $post->slug }}</span></td>
        <td>{{ $post->author?->name ?? 'Unknown' }}</td>
        <td>{{ ucfirst($post->status) }}</td>
        <td><a href="{{ route('admin.blogs.edit', $post) }}">Edit</a>
            @if($post->status === 'published')<a href="{{ route('blog.show', $post->slug) }}">View</a>@endif
            <form class="inline-form" method="post" action="{{ route('admin.blogs.destroy', $post) }}" data-confirm="Delete this article?">@csrf @method('DELETE')<button class="btn btn-small btn-quiet" type="submit">Delete</button></form>
        </td>
    </tr>@endforeach</tbody></table></div>
    {{ $posts->links() }}
@endif
</section>
@endsection
