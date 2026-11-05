@extends('layouts.panel')
@section('title', $blog->exists ? 'Edit article' : 'Create article')
@section('content')
<section class="blog-workspace" data-blog-editor
    data-blog-preview-url="{{ route('admin.blogs.preview') }}"
    data-blog-media-search-url="{{ route('admin.blogs.media.search') }}"
    data-blog-media-upload-url="{{ route('admin.blogs.media.upload') }}"
    data-blog-media-preview-template="{{ route('admin.blogs.media.preview', ['media' => '__MEDIA_ID__']) }}"
    data-blog-category-url="{{ route('admin.blogs.categories.store') }}"
    data-blog-public-url="{{ route('blog.show', ['slug' => '__SLUG__']) }}">
    <header class="admin-welcome blog-workspace-heading">
        <div>
            <span class="admin-eyebrow"><span class="admin-eyebrow-dot" aria-hidden="true"></span> CONTENT / BLOG</span>
            <h1>{{ $blog->exists ? 'Edit article' : 'Create article' }}</h1>
            <p>Compose, review and publish an article with your existing Edumia media library.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('admin.blogs.index') }}">All articles</a>
            @if($blog->exists && $blog->status === 'published' && $blog->published_at && $blog->published_at->isPast())
                <a class="admin-action-secondary" href="{{ route('blog.show', $blog->slug) }}" target="_blank" rel="noopener">View published article</a>
            @endif
        </div>
    </header>

    <form id="blog-article-form" method="post" class="blog-workspace-grid" action="{{ $blog->exists ? route('admin.blogs.update', $blog) : route('admin.blogs.store') }}">
        @csrf
        @if($blog->exists) @method('PUT') @endif

        <div class="blog-workspace-main">
            <section class="admin-widget blog-composer" aria-labelledby="blog-content-title">
                <div class="admin-widget-heading">
                    <div><span class="admin-widget-kicker">ARTICLE CONTENT</span>
                        <h2 id="blog-content-title">Story editor</h2>
                        <p>Use the formatting toolbar and Preview to review the final article.</p></div>
                </div>

                <div class="blog-field">
                    <label for="blog-title">Article title <span aria-hidden="true">*</span></label>
                    <input id="blog-title" name="title" type="text" maxlength="190" required
                        value="{{ old('title', $blog->title) }}" placeholder="Enter an article title"
                        data-blog-title autocomplete="off">
                    @error('title')<small class="blog-field-error">{{ $message }}</small>@enderror
                </div>
                <div class="blog-field">
                    <div class="blog-field-label-row">
                        <label for="blog-slug">URL slug</label>
                        <span class="blog-field-tip">Automatically generated; editable</span>
                    </div>
                    <div class="blog-slug-row">
                        <input id="blog-slug" name="slug" type="text" maxlength="190"
                            pattern="[a-z0-9]+(?:[-_][a-z0-9]+)*" required
                            value="{{ old('slug', $blog->slug) }}" placeholder="article-url-slug"
                            data-blog-slug autocomplete="off">
                        <button class="blog-icon-button" type="button" data-blog-copy-url
                            aria-label="Copy public article URL" title="Copy public article URL">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="8" y="8" width="12" height="12" rx="2"/>
                                <path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/>
                            </svg>
                        </button>
                    </div>
                    <small class="blog-url-preview" data-blog-url-preview>Public URL becomes accessible after publication.</small>
                    @error('slug')<small class="blog-field-error">{{ $message }}</small>@enderror
                </div>
                <div class="blog-field">
                    <label for="blog-excerpt">Summary</label>
                    <textarea id="blog-excerpt" name="excerpt" rows="3" maxlength="1500"
                        placeholder="Briefly describe this article for the blog listing.">{{ old('excerpt', $blog->excerpt) }}</textarea>
                    @error('excerpt')<small class="blog-field-error">{{ $message }}</small>@enderror
                </div>

                <div class="blog-field">
                    <div class="blog-field-label-row"><label for="blog-body">Article body <span aria-hidden="true">*</span></label>
                        <span class="blog-field-tip">Markdown with safe formatted preview</span></div>
                    <div class="blog-editor-shell">
                        <div class="blog-toolbar" role="toolbar" aria-label="Article formatting" data-blog-toolbar>
                            <select aria-label="Text style" data-blog-style>
                                <option value="">Paragraph</option>
                                <option value="h2">Heading 2</option>
                                <option value="h3">Heading 3</option>
                            </select>
                            <span class="blog-toolbar-divider" aria-hidden="true"></span>
                            <button type="button" data-blog-format="bold" title="Bold (Ctrl+B)" aria-label="Bold"><strong>B</strong></button>
                            <button type="button" data-blog-format="italic" title="Italic (Ctrl+I)" aria-label="Italic"><em>I</em></button>
                            <button type="button" data-blog-format="strike" title="Strikethrough" aria-label="Strikethrough"><s>S</s></button>
                            <span class="blog-toolbar-divider" aria-hidden="true"></span>
                            <button type="button" data-blog-format="unordered" title="Bulleted list" aria-label="Bulleted list">≡</button>
                            <button type="button" data-blog-format="ordered" title="Numbered list" aria-label="Numbered list">1.</button>
                            <button type="button" data-blog-format="quote" title="Blockquote" aria-label="Blockquote">❞</button>
                            <button type="button" data-blog-format="link" title="Insert link" aria-label="Insert link">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M10 13a5 5 0 0 0 7.07 0l3-3a5 5 0 0 0-7.07-7.07l-1.5 1.5"/>
                                    <path d="M14 11a5 5 0 0 0-7.07 0l-3 3a5 5 0 0 0 7.07 7.07l1.5-1.5"/>
                                </svg>
                            </button>
                            <button type="button" data-blog-format="code" title="Inline code" aria-label="Inline code">&lt;/&gt;</button>
                            <button type="button" data-blog-format="rule" title="Horizontal rule" aria-label="Horizontal rule">―</button>
                            <button type="button" data-blog-format="image" title="Choose article image" aria-label="Insert image from library">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>
                                </svg>
                            </button>
                            <button class="blog-toolbar-preview" type="button" data-blog-preview-button aria-pressed="false">
                                Preview
                            </button>
                        </div>
                        <textarea name="body" id="blog-body" rows="19" maxlength="100000" required
                            spellcheck="true" data-blog-body
                            placeholder="Write your article here. Use the toolbar for headings, formatting, links, lists and images.">{{ old('body', $blog->body) }}</textarea>
                        <div class="blog-render-preview" data-blog-preview hidden aria-live="polite"></div>
                        <div class="blog-editor-footer"><small>Supports headings, bold, italic, lists, quotations, links and media-library images.</small>
                            <span data-blog-word-count>0 words</span></div>
                    </div>
                    @error('body')<small class="blog-field-error">{{ $message }}</small>@enderror
                </div>
            </section>
        </div>

        <aside class="blog-workspace-sidebar" aria-label="Article publication settings">
            <section class="admin-widget blog-side-panel" aria-labelledby="blog-publish-heading">
                <div class="blog-side-panel-header">
                    <span class="blog-side-icon" aria-hidden="true">✓</span>
                    <h2 id="blog-publish-heading">Publish</h2>
                </div>
                <div class="blog-field">
                    <label for="blog-status">Article status</label>
                    <select name="status" id="blog-status" data-blog-status required>
                        <option value="draft" @selected(old('status', $blog->status ?: 'draft') === 'draft')>Draft — private</option>
                        <option value="published" @selected(old('status', $blog->status) === 'published')>Published — public</option>
                    </select>
                    <small>Drafts are not visible on the public site.</small>
                </div>
                @if($blog->exists)
                    <div class="blog-publish-meta"><span>Last updated</span><strong>{{ $blog->updated_at?->format('M j, Y · H:i') }}</strong></div>
                    @if($blog->published_at)
                        <div class="blog-publish-meta"><span>First published</span><strong>{{ $blog->published_at->format('M j, Y · H:i') }}</strong></div>
                    @endif
                @endif
                <div class="blog-publish-actions">
                    <button type="submit" class="admin-action-primary" data-blog-save>
                        {{ old('status', $blog->status ?: 'draft') === 'published' ? 'Publish article' : 'Save draft' }}
                    </button>
                    <a class="admin-action-secondary" href="{{ route('admin.blogs.index') }}">Cancel</a>
                </div>
                <p class="blog-publish-help">Saving as published makes the article and its selected images publicly accessible.</p>
            </section>

            <section class="admin-widget blog-side-panel" aria-labelledby="blog-category-heading">
                <div class="blog-side-panel-header"><span class="blog-side-icon" aria-hidden="true">▦</span><h2 id="blog-category-heading">Category</h2></div>
                <div class="blog-field">
                    <label for="blog-category">Article category</label>
                    <select id="blog-category" name="category_id" data-blog-category>
                        <option value="">Uncategorized</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->getKey() }}"
                                @selected((string) old('category_id', $blog->category_id) === (string) $category->getKey())>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<small class="blog-field-error">{{ $message }}</small>@enderror
                </div>
                <button class="blog-link-button" type="button" data-blog-new-category>+ Create category</button>
                <div class="blog-category-new" data-blog-category-new hidden>
                    <label for="blog-category-name">New category name</label>
                    <input id="blog-category-name" type="text" maxlength="150" data-blog-category-name>
                    <button type="button" data-blog-category-save class="admin-action-secondary">Add category</button>
                </div>
            </section>

            <section class="admin-widget blog-side-panel" aria-labelledby="blog-image-heading">
                <div class="blog-side-panel-header"><span class="blog-side-icon" aria-hidden="true">▧</span><h2 id="blog-image-heading">Featured Image</h2></div>
                <input type="hidden" name="featured_media_id" data-blog-featured-id
                    value="{{ old('featured_media_id', $blog->featured_media_id) }}">
                <div class="blog-featured-image">
                    <img data-blog-featured-preview alt="Selected featured image"
                        src="{{ $featured ? route('admin.blogs.media.preview', $featured) : '' }}"
                        @if(!$featured) hidden @endif>
                    <div class="blog-featured-empty" data-blog-featured-empty @if($featured) hidden @endif>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="3"/>
                            <circle cx="8.5" cy="8" r="1.5"/><path d="m4 18 5-5 4 4 3-3 4 4"/>
                        </svg>
                        <span>No featured image selected</span>
                    </div>
                </div>
                <p class="blog-featured-label" data-blog-featured-name>{{ $featured?->original_name }}</p>
                <div class="blog-image-actions">
                    <button class="admin-action-secondary" type="button" data-blog-open-images>Choose image</button>
                    <button class="blog-link-button" type="button" data-blog-clear-image>Remove</button>
                </div>
                <p class="blog-side-hint">Use your existing private Media Library. An image becomes public only when its article is published.</p>
                @error('featured_media_id')<small class="blog-field-error">{{ $message }}</small>@enderror
            </section>
        </aside>
    </form>

    <dialog class="blog-media-dialog" data-blog-media-dialog aria-labelledby="blog-media-heading">
        <div class="blog-media-head">
            <div><h2 id="blog-media-heading">Choose an image</h2>
                <p>Select an existing library image or upload a new one.</p></div>
            <button class="blog-icon-button" type="button" data-blog-close-images aria-label="Close image library">✕</button>
        </div>
        <div class="blog-media-tools">
            <input type="search" data-blog-media-search placeholder="Search by image name or alternative text"
                aria-label="Search media library" maxlength="80">
            <label class="admin-action-secondary blog-upload-action">
                Upload image
                <input type="file" accept="image/jpeg,image/png,image/webp" data-blog-media-file hidden>
            </label>
            <a class="blog-link-button" href="{{ route('admin.media.index') }}" target="_blank" rel="noopener">Media Library</a>
        </div>
        <div class="blog-media-grid" data-blog-media-grid aria-label="Available images">
            @foreach($images as $image)
                <div class="blog-media-item" data-blog-image-item>
                    <img loading="lazy" src="{{ route('admin.blogs.media.preview', $image) }}" alt="{{ $image->alt_text ?: $image->original_name }}">
                    <strong>{{ $image->original_name }}</strong>
                    <div class="blog-media-item-actions">
                        <button type="button" data-blog-image-action="featured" data-id="{{ $image->getKey() }}"
                            data-url="{{ route('admin.blogs.media.preview', $image) }}" data-name="{{ $image->original_name }}"
                            data-alt="{{ $image->alt_text ?: $image->original_name }}">Feature</button>
                        <button type="button" data-blog-image-action="inline" data-id="{{ $image->getKey() }}"
                            data-url="{{ route('admin.blogs.media.preview', $image) }}" data-name="{{ $image->original_name }}"
                            data-alt="{{ $image->alt_text ?: $image->original_name }}">Insert</button>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="blog-media-bottom">
            <p class="blog-media-feedback" data-blog-media-feedback role="status">
                JPEG, PNG and WebP images · Maximum 10 MB.
            </p>
            <button class="admin-action-secondary" type="button" data-blog-media-more>Load more</button>
        </div>
    </dialog>
    <p class="sr-only" data-blog-feedback role="status" aria-live="polite"></p>
</section>
@endsection
