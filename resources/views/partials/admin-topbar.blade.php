<header class="panel-header admin-topbar">
    <div class="admin-topbar-main">
        <button class="menu-toggle admin-menu-toggle" type="button" aria-expanded="false"
                aria-controls="panel-navigation" data-admin-open
                aria-label="Open dashboard menu" data-open-label="Open dashboard menu"
                data-close-label="Close dashboard menu">
            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="admin-page-location">
            <span class="admin-location-label">ADMINISTRATION / WORKSPACE</span>
            <strong>@yield('title', 'Dashboard')</strong>
        </div>
        <div class="admin-topbar-search">
            <label class="sr-only" for="admin-nav-search">Find an admin module</label>
            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><circle cx="10.7" cy="10.7" r="7"/><path d="m16 16 5 5"/></svg>
            <input id="admin-nav-search" type="search" autocomplete="off" placeholder="Find a module…" data-admin-search aria-controls="panel-navigation">
            <kbd>/</kbd>
        </div>
        <div class="admin-topbar-actions">
            <a class="admin-topbar-icon" href="{{ route('admin.tickets.index') }}" aria-label="Support tickets" title="Support tickets">
                <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v13H8l-4 4V4Z"/><path d="M8 9h8M8 13h5"/></svg>
            </a>
            <button class="theme-toggle admin-topbar-icon" type="button" aria-label="Toggle color theme" title="Toggle color theme">
                <span class="theme-icon" aria-hidden="true">◐</span>
            </button>
            <details class="admin-user-popover" data-admin-profile>
                <summary class="admin-user-trigger" aria-label="Administrator account menu">
                    <span class="admin-user-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="admin-user-summary"><strong>{{ auth()->user()->name }}</strong><small>Administrator</small></span>
                    <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
                </summary>
                <div class="admin-user-dropdown">
                    <p class="admin-user-email">{{ auth()->user()->email }}</p>
                    <a href="{{ route('account.preferences.edit') }}">Account preferences</a>
                    <a href="{{ route('password.edit') }}">Password & security</a>
                    <a href="{{ route('home') }}">View website</a>
                    <form action="{{ route('logout') }}" method="post">
                        @csrf <button type="submit">Sign out</button>
                    </form>
                </div>
            </details>
        </div>
    </div>
</header>
