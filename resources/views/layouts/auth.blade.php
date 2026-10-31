@php
    $siteName = app(\App\Services\System\SettingsRepository::class)->general()['site_name'];
    $requestedLocale = request()->query('locale');
    $authLocale = \App\Helpers\Localization::resolve(is_string($requestedLocale) ? $requestedLocale : null);
@endphp
<!doctype html>
<html lang="{{ $authLocale }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="description" content="{{ $siteName }} — Secure account access">
    <title>@yield('title') · {{ $siteName }}</title>
    <script>try{document.documentElement.dataset.theme=localStorage.getItem('edumia-theme')==='dark'?'dark':'light'}catch(e){}</script>
    @vite(['resources/css/frontend/app.css', 'resources/css/admin/app.css', 'resources/js/frontend/app.js', 'resources/js/admin/app.js'])
</head>
<body class="auth-page">
<main class="auth-wrap" id="main-content">
    <section class="auth-card" aria-labelledby="auth-heading">
        <div class="auth-identity">
            <a class="auth-logo" href="{{ route('home') }}" aria-label="{{ $siteName }} home">
                <span class="auth-logo-icon" aria-hidden="true">
                    <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="2" y="2" width="36" height="36" rx="12" fill="currentColor"/>
                        <path d="M9 16.2 20 10l11 6.2-11 6.1L9 16.2Z" stroke="#fff" stroke-width="2" stroke-linejoin="round"/>
                        <path d="M13 20v7.2c4.6 3.4 9.5 3.4 14 0V20" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="auth-logo-name">{{ $siteName }}</span>
            </a>
            <button class="theme-toggle auth-theme" type="button" aria-label="Toggle color theme" title="Toggle color theme">
                <span class="theme-icon" aria-hidden="true">◐</span>
            </button>
        </div>
        @if(session('success'))
            <div class="flash auth-feedback" role="status">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="flash error auth-feedback" role="alert" aria-live="polite">
                <strong>Check your details</strong>
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </section>
</main>
</body>
</html>
