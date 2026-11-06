@extends('layouts.panel')
@section('title', 'Media library')
@section('content')
<section class="admin-media-workspace" aria-labelledby="media-heading">
    <div class="admin-welcome">
        <div>
            <span class="admin-eyebrow">CONTENT MANAGEMENT</span>
            <h1 id="media-heading">Media library</h1>
            <p>Organize private learning assets with secure uploads and downloads for authorized staff.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-primary" href="#media-upload">Upload media</a>
        </div>
    </div>

    <div class="admin-stats" aria-label="Media library statistics">
        <div class="admin-stat"><span class="admin-stat-top"><span>Total files</span><span class="admin-stat-icon admin-stat-icon--blue" aria-hidden="true">▤</span></span>
            <strong>{{ number_format($fileCount) }}</strong><small>Private assets</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>Images</span><span class="admin-stat-icon admin-stat-icon--orange" aria-hidden="true">▧</span></span>
            <strong>{{ number_format($imageCount) }}</strong><small>JPEG, PNG and WebP</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>Documents</span><span class="admin-stat-icon admin-stat-icon--mint" aria-hidden="true">▣</span></span>
            <strong>{{ number_format($documentCount) }}</strong><small>PDF documents</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>Stored file size</span><span class="admin-stat-icon admin-stat-icon--violet" aria-hidden="true">◫</span></span>
            <strong>{{ number_format($totalBytes / 1048576, 2) }} <span class="admin-metric-unit">MB</span></strong><small>Recorded uploaded file bytes</small></div>
    </div>

    <div class="admin-media-grid">
        <section class="admin-widget admin-media-library" aria-labelledby="media-list-heading">
            <div class="admin-widget-heading"><div><span class="admin-widget-kicker">ASSET DIRECTORY</span>
                <h2 id="media-list-heading">Stored files</h2>
                <p>Showing {{ $media->firstItem() ?? 0 }}–{{ $media->lastItem() ?? 0 }} of {{ number_format($media->total()) }} matching files.</p>
            </div></div>

            <form method="get" action="{{ route('admin.media.index') }}" class="admin-course-filters admin-media-filters">
                <div>
                    <label class="sr-only" for="admin-media-search">Search file name or alternative text</label>
                    <input id="admin-media-search" type="search" name="q" maxlength="100" value="{{ $search }}"
                           placeholder="Search filename or alternative text">
                </div>
                <div>
                    <label class="sr-only" for="admin-media-type">Filter file type</label>
                    <select id="admin-media-type" name="type">
                        <option value="all" @selected($type === 'all')>All files</option>
                        <option value="image" @selected($type === 'image')>Images</option>
                        <option value="document" @selected($type === 'document')>PDF documents</option>
                    </select>
                </div>
                <div>
                    <label class="sr-only" for="admin-media-description">Description coverage</label>
                    <select id="admin-media-description" name="description">
                        <option value="all" @selected($description === 'all')>All descriptions</option>
                        <option value="missing" @selected($description === 'missing')>Missing description</option>
                    </select>
                </div>
                <button class="admin-action-primary" type="submit">Apply filters</button>
                @if($search !== '' || $type !== 'all' || $description !== 'all')<a class="admin-action-secondary" href="{{ route('admin.media.index') }}">Clear</a>@endif
            </form>

            <div class="table-scroll"><table class="admin-table admin-media-table">
                <thead><tr><th scope="col">File</th><th scope="col">Type</th><th scope="col">Size</th><th scope="col">Uploader</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                @forelse($media as $item)
                    <tr>
                        <td>
                            <div class="admin-media-file">
                                @if(in_array($item->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true))
                                    <a href="{{ route('admin.media.preview', $item) }}" target="_blank"
                                       rel="noopener noreferrer" aria-label="Preview {{ $item->original_name }}">
                                        <img class="admin-media-thumbnail" src="{{ route('admin.media.preview', $item) }}"
                                            alt="{{ $item->alt_text ?: $item->original_name }}" loading="lazy" width="48" height="48">
                                    </a>
                                @else
                                    <span class="admin-media-file-icon" aria-hidden="true">▤</span>
                                @endif
                                <div><strong>{{ $item->original_name }}</strong>
                                    @if($item->alt_text)<small>{{ $item->alt_text }}</small>@endif
                                    <small>Private file #{{ $item->id }} · Uploaded {{ $item->created_at?->format('M j, Y · H:i') ?? 'date unknown' }}</small>
                                    @if($lastChanges->has($item->id))
                                        @php($change = $lastChanges->get($item->id))
                                        <small>Last activity: {{ str_replace('media.', '', str_replace('_', ' ', $change->action)) }}
                                            · {{ $change->actor?->name ?? 'Former team member' }}
                                            · {{ $change->created_at?->format('M j, Y · H:i') }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td><span class="admin-status">{{ $item->mime_type === 'application/pdf' ? 'PDF' : strtoupper(str_replace('image/', '', $item->mime_type)) }}</span></td>
                        <td>{{ number_format($item->size_bytes / 1024, 1) }} KB</td>
                        <td>{{ $item->uploader?->name ?? 'System' }}<small>Uploaded {{ $item->created_at?->format('M j, Y') ?? '—' }}</small></td>
                        <td><div class="admin-media-actions">
                            @if(in_array($item->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true))
                                <a class="admin-table-action" href="{{ route('admin.media.preview', $item) }}"
                                   target="_blank" rel="noopener noreferrer">Preview image</a>
                            @endif
                            <a class="admin-table-action admin-table-action--quiet" href="{{ route('admin.media.download', $item) }}">Download</a>
                            <details class="admin-media-edit">
                                <summary class="admin-table-action admin-table-action--quiet">Edit details</summary>
                                <form method="post" action="{{ route('admin.media.update', $item) }}">
                                    @csrf @method('PUT')
                                    <p>File type: {{ $item->mime_type }} · {{ number_format($item->size_bytes) }} bytes</p>
                                    <label for="admin-media-alt-{{ $item->id }}">Description / alternative text</label>
                                    <input id="admin-media-alt-{{ $item->id }}" name="alt_text" maxlength="255"
                                        value="{{ $item->alt_text }}" placeholder="Describe this file">
                                    <button class="admin-table-action" type="submit">Save details</button>
                                </form>
                            </details>
                            <form method="post" action="{{ route('admin.media.destroy', $item) }}"
                                  data-confirm="Permanently delete this file?">
                                @csrf @method('DELETE')
                                <button class="admin-table-action admin-table-action--quiet" type="submit">Delete</button>
                            </form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="admin-empty"><span aria-hidden="true">▤</span>
                        <strong>No matching media</strong><p>Upload a file or adjust the filters.</p></div></td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div class="admin-course-pagination">{{ $media->onEachSide(1)->links() }}</div>
        </section>

        <section id="media-upload" class="admin-widget admin-media-upload" aria-labelledby="media-upload-heading">
            <div class="admin-widget-heading"><div><span class="admin-widget-kicker">PRIVATE STORAGE</span>
                <h2 id="media-upload-heading">Upload media</h2><p>Add an image or PDF to the secure file library.</p></div></div>
            <form method="post" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="stack-form">
                @csrf
                <label for="media-file">Choose file
                    <input id="media-file" type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                </label>
                <label for="media-alt">Alternative text
                    <input id="media-alt" name="alt_text" maxlength="255" value="{{ old('alt_text') }}"
                           placeholder="Describe the image for accessibility">
                </label>
                <p class="admin-media-hint">JPEG, PNG, WebP or PDF · Maximum 10 MB · Private storage only. Staff with media.manage permission can preview, edit details, download, and delete. Publishing an article can expose only the images referenced by that published article.</p>
                <button class="admin-action-primary" type="submit">Upload file securely</button>
            </form>
        </section>
    </div>
    <section class="admin-widget admin-media-library" aria-labelledby="media-audit-heading">
        <div class="admin-widget-heading">
            <div><span class="admin-widget-kicker">ACCOUNTABILITY</span>
                <h2 id="media-audit-heading">Media audit history</h2>
                <p>Private activity log, including files that have been deleted.</p></div>
        </div>
        <div class="table-scroll"><table class="admin-table admin-media-table">
            <thead><tr><th>Date</th><th>Activity</th><th>File</th><th>Actor</th><th>Details</th></tr></thead>
            <tbody>
            @forelse($auditHistory as $event)
                <tr>
                    <td>{{ $event->created_at?->format('M j, Y · H:i') }}</td>
                    <td>{{ ucfirst(str_replace(['media.', '_'], ['', ' '], $event->action)) }}</td>
                    <td>Media #{{ $event->subject_id }}</td>
                    <td>{{ $event->actor?->name ?? 'Former team member' }}</td>
                    <td>
                        @if($event->action === 'media.description_updated')
                            Before: {{ data_get($event->previous_values, 'alt_text') ?: 'Not set' }}
                            After: {{ data_get($event->new_values, 'alt_text') ?: 'Not set' }}
                        @elseif($event->action === 'media.uploaded')
                            {{ data_get($event->new_values, 'original_name', 'Uploaded file') }}
                        @elseif($event->action === 'media.deleted')
                            {{ data_get($event->previous_values, 'original_name', 'Deleted file') }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="admin-empty"><strong>No media activity recorded yet</strong></div></td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="admin-course-pagination">{{ $auditHistory->onEachSide(1)->links() }}</div>
    </section>
</section>
@endsection
