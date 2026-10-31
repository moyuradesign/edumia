@extends('layouts.panel')
@section('title', 'Media library')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">CONTENT MANAGEMENT</span><h1>Media library</h1><p>Private uploads accessible only to administrators.</p></div></div>
<section class="panel-card">
    <h2>Upload media</h2>
    <form method="post" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="stack-form">
        @csrf
        <label>Choose file <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf" required></label>
        <label>Alternative text <input name="alt_text" maxlength="255" value="{{ old('alt_text') }}"></label>
        <p class="subtle">JPEG, PNG, WebP or PDF, up to 10 MB. Private storage; no public media URL.</p>
        <div><button class="btn btn-primary" type="submit">Upload file</button></div>
    </form>
</section>
<section class="panel-card">
    <h2>Stored files</h2>
    @if($media->isEmpty())<p>No media files uploaded.</p>@else
        <div class="table-scroll"><table>
        <thead><tr><th>File</th><th>Type</th><th>Size</th><th>Uploader</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($media as $item)
            <tr>
                <td>{{ $item->original_name }}</td>
                <td>{{ $item->mime_type }}</td>
                <td>{{ number_format($item->size_bytes / 1024, 1) }} KB</td>
                <td>{{ $item->uploader?->name ?? 'System' }}</td>
                <td><a href="{{ route('admin.media.download', $item) }}">Download</a>
                    <form class="inline-form" method="post" action="{{ route('admin.media.destroy', $item) }}" data-confirm="Permanently delete this file?">@csrf @method('DELETE')<button class="btn btn-small btn-quiet" type="submit">Delete</button></form>
                </td>
            </tr>
        @endforeach
        </tbody></table></div>
        {{ $media->links() }}
    @endif
</section>
@endsection
