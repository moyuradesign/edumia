@php
    $siteName = app(\App\Services\System\SettingsRepository::class)->general()['site_name'];
@endphp
<!doctype html>
<html lang="{{ $locale ?? 'en' }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="description" content="{{ $siteName }} — Learning Management System">
    <title>@yield('title', $siteName.' — Learning Management System')</title>
    <script>try{document.documentElement.dataset.theme=localStorage.getItem('edumia-theme')==='dark'?'dark':'light'}catch(e){}</script>
    @vite(['resources/css/frontend/app.css', 'resources/js/frontend/app.js'])
</head>
<body>
@php
    $locale = $locale ?? \App\Helpers\Localization::resolve();
    $segments = request()->segments();
    if (in_array($segments[0] ?? '', array_keys(\App\Helpers\Localization::LANGUAGES), true)) array_shift($segments);
    $currentPath = implode('/', $segments);
@endphp
<a class="skip-link" href="#content">Skip to content</a>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="{{ \App\Helpers\Localization::url($locale) }}" aria-label="{{ $siteName }} home"><span class="brand-mark">e</span><span>{{ $siteName }}<span class="brand-dot">.</span></span></a>
        <nav class="primary-nav" aria-label="Primary navigation">
            <a href="{{ \App\Helpers\Localization::url($locale) }}" class="{{ $currentPath === '' ? 'active' : '' }}">{{ \App\Helpers\Localization::label('home', $locale) }}</a>
            <a href="{{ \App\Helpers\Localization::url($locale, 'courses') }}" class="{{ str_starts_with($currentPath, 'courses') ? 'active' : '' }}">{{ \App\Helpers\Localization::label('nav_courses', $locale) }}</a>
            <a href="{{ \App\Helpers\Localization::url($locale, 'bootcamps') }}" class="{{ str_starts_with($currentPath, 'bootcamps') ? 'active' : '' }}">{{ \App\Helpers\Localization::label('nav_bootcamps', $locale) }}</a>
            <a href="{{ \App\Helpers\Localization::url($locale, 'ebooks') }}" class="{{ $currentPath === 'ebooks' ? 'active' : '' }}">{{ \App\Helpers\Localization::label('nav_ebooks', $locale) }}</a>
            <a href="{{ \App\Helpers\Localization::url($locale, 'blog') }}" class="{{ str_starts_with($currentPath, 'blog') ? 'active' : '' }}">{{ \App\Helpers\Localization::label('nav_blog', $locale) }}</a>
            <a href="{{ \App\Helpers\Localization::url($locale, 'help') }}" class="{{ str_starts_with($currentPath, 'help') ? 'active' : '' }}">{{ \App\Helpers\Localization::label('nav_help', $locale) }}</a>
            <a href="{{ \App\Helpers\Localization::url($locale, 'contact') }}" class="{{ $currentPath === 'contact' ? 'active' : '' }}">{{ \App\Helpers\Localization::label('nav_contact', $locale) }}</a>
        </nav>
        <div class="header-actions">
            <label class="sr-only" for="language-select">{{ \App\Helpers\Localization::label('language', $locale) }}</label>
            <select id="language-select" class="language-select" aria-label="{{ \App\Helpers\Localization::label('language', $locale) }}">
                @foreach ($languages as $code => $name)
                    <option value="{{ \App\Helpers\Localization::url($code, $currentPath).(request()->only(['q', 'page']) ? '?'.http_build_query(request()->only(['q', 'page'])) : '') }}" @selected($locale === $code)>{{ $name }}</option>
                @endforeach
            </select>
            @auth <a class="btn btn-quiet" href="{{ route('dashboard') }}">{{ \App\Helpers\Localization::label('dashboard', $locale) }}</a> @else <a class="btn btn-quiet" href="{{ route('login', ['locale' => $locale]) }}">{{ \App\Helpers\Localization::label('sign_in', $locale) }}</a><a class="btn btn-primary" href="{{ route('register', ['locale' => $locale]) }}">{{ \App\Helpers\Localization::label('join', $locale) }}</a> @endauth
            <button class="theme-toggle" type="button" aria-label="Toggle color theme" title="Toggle color theme"><span class="theme-icon">◐</span><span class="theme-label">{{ \App\Helpers\Localization::label('dark', $locale) }}</span></button>
        </div>
    </div>
</header>
<main id="content">@yield('content')</main>
<section class="course-section" aria-label="Newsletter subscription"><div class="container">
<h2>{{ $siteName }} Newsletter</h2><p>Receive occasional learning updates. Email confirmation is required.</p>
<form class="stack-form" method="post" action="{{ route('newsletter.subscribe') }}">
@csrf
<label>Email address <input type="email" name="email" maxlength="190" required autocomplete="email"></label>
<label>Name (optional) <input name="name" maxlength="160" autocomplete="name"></label>
<label class="checkline"><input type="checkbox" name="consent" value="1" required>
I agree to receive {{ $siteName }} email updates and understand I can unsubscribe at any time.</label>
<button class="btn btn-secondary" type="submit">Subscribe</button>
</form></div></section>
<footer class="site-footer"><div class="container footer-inner"><div><strong>{{ $siteName }}</strong><span> — Learning Management System</span></div><div>© {{ date('Y') }} {{ $siteName }}. {{ \App\Helpers\Localization::label('rights', $locale) }}</div></div></footer>
</body>
</html>
