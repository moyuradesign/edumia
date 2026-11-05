@extends('layouts.default')
@section('title', $post->title.' — Edumia')
@section('content')
<section class="page-hero"><div class="container">
    <p class="section-kicker">{{ $post->category?->name ?? 'EDUMIA JOURNAL' }}</p>
    <h1>{{ $post->title }}</h1>
    @if($post->excerpt)<p>{{ $post->excerpt }}</p>@endif
    <p class="subtle">{{ $post->published_at?->format('M d, Y') }} · {{ $post->author?->name ?? 'Edumia' }}</p>
</div></section>
<section class="course-section"><div class="container blog-article-content">
    @if($post->featuredImage)
        <figure class="blog-article-hero">
            <img src="{{ route('blog.image', ['slug' => $post->slug, 'media' => $post->featuredImage]) }}"
                 alt="{{ $post->featuredImage->alt_text ?: $post->title }}">
        </figure>
    @endif
    <article class="panel-card blog-article-panel">
        <div class="lesson-content blog-markdown">{!! $renderer->render($post->body, $post) !!}</div>
    </article>
    <a class="text-link" href="{{ \App\Helpers\Localization::url($locale, 'blog') }}">← All articles</a>
</div></section>
@endsection
