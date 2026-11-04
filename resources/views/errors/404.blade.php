@extends('layouts.default')
@section('title', 'Page not found · Edumia')
@section('robots', 'noindex, follow')
@section('content')
<section class="site-error-page" aria-labelledby="page-not-found-title">
    <div class="site-error-content">
            <span class="site-error-icon" aria-hidden="true">
                <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 7h19l7 7v27H12z"/>
                    <path d="M31 7v8h7M19 26h12M19 33h9"/>
                    <circle cx="13" cy="12" r="5" fill="currentColor" stroke="none"/>
                </svg>
            </span>
            <p class="site-error-kicker">404 · Not found</p>
            <h1 id="page-not-found-title">Page not found</h1>
            <p class="site-error-description">The page you're looking for doesn't exist or has moved.</p>
            <div class="site-error-actions">
                <a class="site-error-action site-error-action--primary" href="{{ route('home') }}">Go to homepage</a>
                <a class="site-error-action" href="{{ route('courses.index') }}">Browse courses</a>
                @auth
                    <a class="site-error-action" href="{{ route('dashboard') }}">My dashboard</a>
                @endauth
            </div>
    </div>
</section>
@endsection
