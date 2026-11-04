@php
    $siteName = app(\App\Services\System\SettingsRepository::class)->general()['site_name'];
@endphp
<!doctype html>
<html lang="en" data-theme="light"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title','Dashboard') · {{ $siteName }}</title>
<script>try{document.documentElement.dataset.theme=localStorage.getItem('edumia-theme')||'light'}catch(e){}</script>
@vite(['resources/css/frontend/app.css', 'resources/css/admin/app.css', 'resources/js/frontend/app.js', 'resources/js/admin/app.js'])
</head>@if(auth()->user()->isAdmin())
<body class="panel-page admin-workspace">
<a class="skip-link" href="#admin-main">Skip to main content</a>
<div class="admin-shell" data-admin-shell>
    @include('partials.admin-sidebar')
    <div class="admin-frame">
        @include('partials.admin-topbar')
        <main class="admin-main" id="admin-main">
            @if(session('success'))<div class="flash" role="status">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="flash error" role="alert"><strong>Please review:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>
        <footer class="admin-footer"><span>{{ $siteName }} · Administration</span><span>© {{ date('Y') }}</span></footer>
    </div>
</div>
@else
<body class="panel-page admin-workspace member-workspace">
<a class="skip-link" href="#member-main">Skip to main content</a>
<div class="admin-shell" data-admin-shell>
    @include('partials.member-sidebar')
    <div class="admin-frame">
        @include('partials.member-topbar')
        <main class="admin-main" id="member-main">
            @if(session('success'))<div class="flash" role="status">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="flash error" role="alert"><strong>Please review:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>
        <footer class="admin-footer"><span>{{ $siteName }} · {{ auth()->user()->canTeach() ? 'Instructor workspace' : 'Student workspace' }}</span><span>© {{ date('Y') }}</span></footer>
    </div>
</div>
@endif
</body></html>
