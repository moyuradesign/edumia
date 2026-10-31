@php
    $adminGroups = [
        [
            'label' => 'Workspace',
            'items' => [
                ['label' => 'Overview', 'route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Learning reports', 'route' => 'admin.reports.learning', 'match' => 'admin.reports.*', 'icon' => 'chart'],
            ],
        ],
        [
            'label' => 'Learning management',
            'items' => [
                ['label' => 'All courses', 'route' => 'admin.courses.index', 'match' => 'admin.courses.*', 'icon' => 'book'],
                ['label' => 'Course reviews', 'route' => 'admin.reviews.index', 'match' => 'admin.reviews.*', 'icon' => 'star'],
                ['label' => 'Categories', 'route' => 'admin.categories.index', 'match' => 'admin.categories.*', 'icon' => 'grid'],
                ['label' => 'Ebook approvals', 'route' => 'admin.ebooks.index', 'match' => 'admin.ebooks.*', 'icon' => 'book'],
                ['label' => 'Bootcamp approvals', 'route' => 'admin.bootcamps.index', 'match' => 'admin.bootcamps.*', 'icon' => 'book'],
            ],
        ],
        [
            'label' => 'People & access',
            'items' => [
                ['label' => 'Instructor applications', 'route' => 'admin.instructor-applications.index', 'match' => 'admin.instructor-applications.*', 'icon' => 'users'],
                ['label' => 'Staff roles', 'route' => 'admin.roles.index', 'match' => 'admin.roles.*', 'icon' => 'shield'],
            ],
        ],
        [
            'label' => 'Commerce',
            'items' => [
                ['label' => 'Orders', 'route' => 'admin.commerce.orders.index', 'match' => 'admin.commerce.orders.*', 'icon' => 'cart'],
                ['label' => 'Accounting', 'route' => 'admin.commerce.accounting.index', 'match' => 'admin.commerce.accounting.*', 'icon' => 'chart'],
                ['label' => 'Refund reviews', 'route' => 'admin.commerce.refunds.index', 'match' => 'admin.commerce.refunds.*', 'icon' => 'shield'],
            ],
        ],
        [
            'label' => 'Content & community',
            'items' => [
                ['label' => 'Blog posts', 'route' => 'admin.blogs.index', 'match' => 'admin.blogs.*', 'icon' => 'book'],
                ['label' => 'Media library', 'route' => 'admin.media.index', 'match' => 'admin.media.*', 'icon' => 'grid'],
                ['label' => 'Help center', 'route' => 'admin.knowledge-base.index', 'match' => 'admin.knowledge-base.*', 'icon' => 'book'],
                ['label' => 'Forum moderation', 'route' => 'admin.forums.index', 'match' => 'admin.forums.*', 'icon' => 'chat'],
            ],
        ],
        [
            'label' => 'Operations',
            'items' => [
                ['label' => 'Support tickets', 'route' => 'admin.tickets.index', 'match' => 'admin.tickets.*', 'icon' => 'chat'],
                ['label' => 'Contact inbox', 'route' => 'admin.contacts.index', 'match' => 'admin.contacts.*', 'icon' => 'mail'],
                ['label' => 'General settings', 'route' => 'admin.settings.edit', 'match' => 'admin.settings.*', 'icon' => 'settings'],
            ],
        ],
    ];
@endphp
<aside class="admin-sidebar" id="panel-navigation" data-admin-sidebar aria-label="Administration sidebar">
    <div class="admin-sidebar-brand">
        <a class="admin-sidebar-logo" href="{{ route('dashboard') }}" aria-label="{{ $siteName }} administration">
            <span class="admin-logo-mark" aria-hidden="true">
                <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 14 20 6l15 8-15 8L5 14Z"/><path d="M11 19v10c6 4 12 4 18 0V19"/><path d="M35 14v12"/>
                </svg>
            </span>
            <span class="admin-brand-text"><strong>{{ $siteName }}</strong><small>ADMIN CONSOLE</small></span>
        </a>
        <button type="button" class="admin-sidebar-close" data-admin-close aria-label="Close sidebar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m5 5 14 14M19 5 5 19"/></svg>
        </button>
    </div>
    <div class="admin-sidebar-scroll">
        @foreach($adminGroups as $group)
            <section class="admin-nav-group" data-admin-nav-group aria-label="{{ $group['label'] }}">
                <h2>{{ $group['label'] }}</h2>
                <nav aria-label="{{ $group['label'] }}">
                    @foreach($group['items'] as $item)
                        <a class="admin-nav-link {{ request()->routeIs($item['match']) ? 'is-active' : '' }}"
                           data-admin-nav-link href="{{ route($item['route']) }}"
                           @if(request()->routeIs($item['match'])) aria-current="page" @endif>
                            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                @switch($item['icon'])
                                    @case('home')<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>@break
                                    @case('chart')<path d="M4 20V10m6 10V4m6 16v-7m5 7H3"/>@break
                                    @case('star')<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3l-5.6 2.9 1.1-6.2L3 9.6l6.2-.9L12 3Z"/>@break
                                    @case('grid')<rect x="3" y="3" width="7" height="7" rx="1.3"/><rect x="14" y="3" width="7" height="7" rx="1.3"/><rect x="3" y="14" width="7" height="7" rx="1.3"/><rect x="14" y="14" width="7" height="7" rx="1.3"/>@break
                                    @case('book')<path d="M4 5h6a3 3 0 0 1 3 3v12a3 3 0 0 0-3-3H4a2 2 0 0 0-2 2V7a2 2 0 0 1 2-2Zm16 0h-6a3 3 0 0 0-3 3v12a3 3 0 0 1 3-3h6a2 2 0 0 1 2 2V7a2 2 0 0 0-2-2Z"/>@break
                                    @case('users')<circle cx="9" cy="8" r="3"/><path d="M3 20v-1c0-3 2-5 6-5s6 2 6 5v1M16 5a3 3 0 0 1 0 6m2 4c2 1 3 2 3 5"/>@break
                                    @case('shield')<path d="M12 3 4 6v6c0 5.5 3.5 8 8 9 4.5-1 8-3.5 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>@break
                                    @case('cart')<circle cx="9" cy="20" r="1"/><circle cx="19" cy="20" r="1"/><path d="M2 3h2l3 13h13l2-10H5"/>@break
                                    @case('chat')<path d="M4 4h16v12H8l-4 4V4Z"/><path d="M8 9h8m-8 4h5"/>@break
                                    @case('mail')<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>@break
                                    @case('settings')<circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3M5 5l2 2m10 10 2 2M19 5l-2 2M7 17l-2 2"/>@break
                                @endswitch
                            </svg>
                            <span>{{ $item['label'] }}</span>
                            @if(request()->routeIs($item['match']))<span class="admin-active-indicator" aria-hidden="true"></span>@endif
                        </a>
                    @endforeach
                </nav>
            </section>
        @endforeach
    </div>
    <div class="admin-sidebar-bottom">
        <a href="{{ route('home') }}" class="admin-view-site">
            <span>View public website</span><span aria-hidden="true">↗</span>
        </a>
    </div>
</aside>
<div class="admin-sidebar-backdrop" data-admin-backdrop hidden></div>
