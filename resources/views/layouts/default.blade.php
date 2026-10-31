@php
    $publicSettings = app(\App\Services\System\SettingsRepository::class)->general();
    $siteName = $publicSettings['site_name'];
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
@include('partials.site-header')
<main id="content">@yield('content')</main>
@include('partials.site-footer')
</body>
</html>
