@extends('layouts.default')
@section('title', 'Articles — Edumia')
@section('content')
<section class="page-hero"><div class="container"><p class="section-kicker">EDUMIA JOURNAL</p><h1>Learning insights and news</h1><p>Articles, stories and announcements from the Edumia team.</p></div></section>
<section class="course-section"><div class="container"><div class="course-grid blog-public-grid">
    @forelse($posts as $post)
        <article class="course-card blog-public-card">
            @if($post->featuredImage)
                <a href="{{ \App\Helpers\Localization::url($locale, 'blog/'.$post->slug) }}"
                    class="blog-card-image-link" aria-label="Read {{ $post->title }}">
                    <img class="blog-card-image" loading="lazy"
                        src="{{ route('blog.image', ['slug' => $post->slug, 'media' => $post->featuredImage]) }}"
                        alt="{{ $post->featuredImage->alt_text ?: $post->title }}">
                </a>
            @endif
            <div class="course-body">
                <span class="course-category">{{ $post->category?->name ?? 'Article' }} · {{ \App\Helpers\Localization::date($post->published_at, $locale) }}</span>
                <h2>{{ $post->title }}</h2><p>{{ $post->excerpt }}</p>
                <a class="course-link" href="{{ \App\Helpers\Localization::url($locale, 'blog/'.$post->slug) }}">Read article →</a>
            </div>
        </article>
    @empty
        <div class="empty-state"><h2>No articles yet</h2><p>Visit again for learning insights.</p></div>
    @endforelse
</div><div class="pagination">{{ $posts->links() }}</div></div></section>
@endsection
