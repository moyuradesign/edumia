<!doctype html>
<html lang="{{ $locale ?? 'en' }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="description" content="Edumia — a modern learning management system by Ton Samith.">
    <title>@yield('title', 'Edumia — Learning Management System')</title>
    <script>try{document.documentElement.dataset.theme=localStorage.getItem('edumia-theme')==='dark'?'dark':'light'}catch(e){}</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $locale = $locale ?? 'en';
    $segments = request()->segments();
    if (in_array($segments[0] ?? '', array_keys(\App\Helpers\Common_helper::LANGUAGES), true)) array_shift($segments);
    $currentPath = implode('/', $segments);
@endphp
<a class="skip-link" href="#content">Skip to content</a>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="{{ \App\Helpers\Common_helper::url($locale) }}" aria-label="Edumia home"><span class="brand-mark">e</span><span>edumia<span class="brand-dot">.</span></span></a>
        <nav class="primary-nav" aria-label="Primary navigation">
            <a href="{{ \App\Helpers\Common_helper::url($locale) }}" class="{{ $currentPath === '' ? 'active' : '' }}">Home</a>
            <a href="{{ \App\Helpers\Common_helper::url($locale, 'courses') }}" class="{{ str_starts_with($currentPath, 'courses') ? 'active' : '' }}">{{ \App\Helpers\Common_helper::label('nav_courses', $locale) }}</a>
        </nav>
        <div class="header-actions">
            <label class="sr-only" for="language-select">{{ \App\Helpers\Common_helper::label('language', $locale) }}</label>
            <select id="language-select" class="language-select" aria-label="{{ \App\Helpers\Common_helper::label('language', $locale) }}">
                @foreach ($languages as $code => $name)
                    <option value="{{ \App\Helpers\Common_helper::url($code, $currentPath) }}" @selected($locale === $code)>{{ $name }}</option>
                @endforeach
            </select>
            @auth <a class="btn btn-quiet" href="{{ route('dashboard') }}">Dashboard</a> @else <a class="btn btn-quiet" href="{{ route('login') }}">Sign in</a><a class="btn btn-primary" href="{{ route('register') }}">Join</a> @endauth
            <button class="theme-toggle" type="button" aria-label="Toggle color theme" title="Toggle color theme"><span class="theme-icon">◐</span><span class="theme-label">{{ \App\Helpers\Common_helper::label('dark', $locale) }}</span></button>
        </div>
    </div>
</header>
<main id="content">@yield('content')</main>
<footer class="site-footer"><div class="container footer-inner"><div><strong>edumia.</strong><span> — Learning Management System</span></div><div>© {{ date('Y') }} <a href="https://tonsamith.com" rel="author noopener">Ton Samith</a>. {{ \App\Helpers\Common_helper::label('rights', $locale) }}</div></div></footer>
</body>
</html>
