<!doctype html>
<html lang="en" data-theme="light"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title') · Edumia</title><script>try{document.documentElement.dataset.theme=localStorage.getItem('edumia-theme')||'light'}catch(e){}</script>
@vite(['resources/css/frontend/app.css', 'resources/css/admin/app.css', 'resources/js/frontend/app.js', 'resources/js/admin/app.js'])</head>
<body><main class="auth-wrap"><a class="brand auth-brand" href="{{ route('home') }}"><span class="brand-mark">e</span> edumia<span class="brand-dot">.</span></a>
<div class="auth-card">@if($errors->any())<div class="flash error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')</div><p class="auth-note">© {{ date('Y') }} Ton Samith · <button class="theme-toggle" aria-label="Toggle color theme" type="button"><span class="theme-icon">◐</span></button></p>
</main></body></html>
