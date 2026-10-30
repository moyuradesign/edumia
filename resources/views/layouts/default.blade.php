<!doctype html>
<html lang="{{ $locale ?? 'en' }}" data-theme="light">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark"><meta name="description" content="Edumia — modern learning management system by Ton Samith.">
<title>@yield('title', 'Edumia — Learning Management System')</title>
<script>try{document.documentElement.dataset.theme=localStorage.getItem('edumia-theme')==='dark'?'dark':'light'}catch(e){}</script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $locale = $locale ?? 'en';
    $segments = request()->segments();
    if (in_array($segments[0] ?? '', array_keys(\App\Helpers\Common_helper::LANGUAGES), true)) array_shift($segments);
    $path = implode('/', $segments);
@endphp
<a class="skip" href="#content">Skip to content</a>
<header class="header"><div class="container nav">
<a class="brand" href="{{ \App\Helpers\Common_helper::url($locale) }}"><span class="brand-mark">e</span>edumia<span class="dot">.</span></a>
<nav aria-label="Primary navigation"><a href="{{ \App\Helpers\Common_helper::url($locale) }}">Home</a><a href="{{ \App\Helpers\Common_helper::url($locale,'courses') }}">{{ \App\Helpers\Common_helper::label('courses',$locale) }}</a></nav>
<div class="tools"><label class="sr-only" for="lang">Language</label><select id="lang" aria-label="Language">@foreach($languages as $code => $name)<option value="{{ \App\Helpers\Common_helper::url($code,$path) }}" @selected($code===$locale)>{{ $name }}</option>@endforeach</select><button type="button" id="theme" aria-label="Toggle light and dark mode">◐</button></div>
</div></header>
<main id="content">@yield('content')</main>
<footer class="footer"><div class="container foot"><strong>edumia.</strong><span>© {{ date('Y') }} <a href="https://tonsamith.com" rel="author noopener">Ton Samith</a>. All rights reserved.</span></div></footer>
</body></html>
