<!doctype html>
<html lang="en" data-theme="light"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title','Dashboard') · Edumia</title>
<script>try{document.documentElement.dataset.theme=localStorage.getItem('edumia-theme')||'light'}catch(e){}</script>
@vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js', 'resources/js/admin.js'])
</head><body class="panel-page"><header class="panel-header"><div class="container panel-top">
<a class="brand" href="{{ route('home') }}"><span class="brand-mark">e</span> edumia<span class="brand-dot">.</span></a>
<nav class="panel-nav"><a href="{{ route('dashboard') }}">Dashboard</a><a href="{{ route('courses.index') }}">Explore</a><a href="{{ route('password.edit') }}">Security</a>
@if(auth()->user()->isAdmin())<a href="{{ route('admin.categories.index') }}">Categories</a><a href="{{ route('admin.media.index') }}">Media Library</a><a href="{{ route('admin.settings.edit') }}">Settings</a>@endif
@if(auth()->user()->canTeach())<a href="{{ route('instructor.courses.create') }}">Create course</a>@endif</nav>
<div class="panel-profile"><span>{{ auth()->user()->name }} · {{ ucfirst(auth()->user()->role) }}</span>
<button class="theme-toggle" type="button" aria-label="Toggle color theme"><span class="theme-icon">◐</span></button>
<form action="{{ route('logout') }}" method="post">@csrf<button class="btn btn-quiet" type="submit">Log out</button></form></div></div></header>
<main class="container panel-main">@if(session('success'))<div class="flash" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="flash error" role="alert"><strong>Please review:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')</main><footer class="container panel-foot">Edumia Learning Management System · © {{ date('Y') }} Ton Samith</footer></body></html>
