@extends('layouts.default')
@section('title', 'Page not found · Edumia')
@section('robots', 'noindex, follow')
@section('content')
<section class="site-error-page" aria-labelledby="page-not-found-title">
    <div class="site-error-content">
            <span class="site-error-icon" aria-hidden="true">
                <svg class="site-error-artwork" viewBox="0 0 420 188" fill="none"
                     xmlns="http://www.w3.org/2000/svg" focusable="false">
                    <path d="M86 27 26 112h98M104 27v135"
                          stroke="currentColor" stroke-width="19"
                          stroke-linecap="round" stroke-linejoin="round"/>
                    <rect x="164" y="27" width="92" height="135" rx="46"
                          stroke="var(--accent)" stroke-width="19"/>
                    <path d="m345 27-60 85h98M363 27v135"
                          stroke="currentColor" stroke-width="19"
                          stroke-linecap="round" stroke-linejoin="round"/>
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
