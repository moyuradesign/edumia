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
    @include('partials.favicons')
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
                @include('partials.brand-logo')
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
