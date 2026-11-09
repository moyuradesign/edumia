@php
    $adminGroups = [
        [
            'label' => 'Workspace',
            'items' => [
                ['label' => 'Overview', 'route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'admin-overview'],
                ['label' => 'Learning reports', 'route' => 'admin.reports.learning', 'match' => 'admin.reports.*', 'icon' => 'admin-learning-reports'],
            ],
        ],
        [
            'label' => 'Learning management',
            'items' => [
                ['label' => 'All courses', 'route' => 'admin.courses.index', 'match' => 'admin.courses.*', 'icon' => 'admin-courses'],
                ['label' => 'Course reviews', 'route' => 'admin.reviews.index', 'match' => 'admin.reviews.*', 'icon' => 'admin-course-reviews'],
                ['label' => 'Categories', 'route' => 'admin.categories.index', 'match' => 'admin.categories.*', 'icon' => 'admin-categories'],
                ['label' => 'Ebook approvals', 'route' => 'admin.ebooks.index', 'match' => 'admin.ebooks.*', 'icon' => 'admin-ebook-approvals'],
                ['label' => 'Bootcamp approvals', 'route' => 'admin.bootcamps.index', 'match' => 'admin.bootcamps.*', 'icon' => 'admin-bootcamp-approvals'],
                ['label' => 'Team packages', 'route' => 'admin.team-training.index', 'match' => 'admin.team-training.*', 'icon' => 'admin-team-packages'],
            ],
        ],
        [
            'label' => 'People & access',
            'items' => [
                ['label' => 'All members', 'route' => 'admin.users.index', 'match' => 'admin.users.*', 'icon' => 'admin-members'],
                ['label' => 'Instructor applications', 'route' => 'admin.instructor-applications.index', 'match' => 'admin.instructor-applications.*', 'icon' => 'admin-instructor-applications'],
                ['label' => 'Staff roles', 'route' => 'admin.roles.index', 'match' => 'admin.roles.*', 'icon' => 'admin-staff-roles'],
            ],
        ],
        [
            'label' => 'Commerce',
            'items' => [
                ['label' => 'Orders', 'route' => 'admin.commerce.orders.index', 'match' => 'admin.commerce.orders.*', 'icon' => 'admin-orders'],
                ['label' => 'Accounting', 'route' => 'admin.commerce.accounting.index', 'match' => 'admin.commerce.accounting.*', 'icon' => 'admin-accounting'],
                ['label' => 'Payment readiness', 'route' => 'admin.commerce.gateway-readiness.index', 'match' => 'admin.commerce.gateway-readiness.*', 'icon' => 'admin-payments'],
                ['label' => 'Refund reviews', 'route' => 'admin.commerce.refunds.index', 'match' => 'admin.commerce.refunds.*', 'icon' => 'admin-refunds'],
            ],
        ],
        [
            'label' => 'Content & community',
            'items' => [
                ['label' => 'Blog posts', 'route' => 'admin.blogs.index', 'match' => 'admin.blogs.*', 'icon' => 'admin-blog'],
                ['label' => 'Media library', 'route' => 'admin.media.index', 'match' => 'admin.media.*', 'icon' => 'admin-media'],
                ['label' => 'Help center', 'route' => 'admin.knowledge-base.index', 'match' => 'admin.knowledge-base.*', 'icon' => 'admin-help'],
                ['label' => 'Forum moderation', 'route' => 'admin.forums.index', 'match' => 'admin.forums.*', 'icon' => 'admin-forums'],
            ],
        ],
        [
            'label' => 'Operations',
            'items' => [
                ['label' => 'Support tickets', 'route' => 'admin.tickets.index', 'match' => 'admin.tickets.*', 'icon' => 'admin-support'],
                ['label' => 'Contact inbox', 'route' => 'admin.contacts.index', 'match' => 'admin.contacts.*', 'icon' => 'admin-contacts'],
                ['label' => 'General settings', 'route' => 'admin.settings.edit', 'match' => 'admin.settings.*', 'icon' => 'admin-settings'],
                ['label' => 'Release readiness', 'route' => 'admin.readiness.index', 'match' => 'admin.readiness.*', 'icon' => 'admin-release'],
            ],
        ],
        [
            'label' => 'My account',
            'items' => [
                ['label' => 'Notifications', 'route' => 'account.notifications.index', 'match' => 'account.notifications.*', 'icon' => 'account-notifications'],
                ['label' => 'Account preferences', 'route' => 'account.preferences.edit', 'match' => 'account.preferences.*', 'icon' => 'member-preferences'],
                ['label' => 'Password & security', 'route' => 'password.edit', 'match' => 'password.*', 'icon' => 'member-security'],
            ],
        ],
    ];
@endphp
<aside class="admin-sidebar" id="panel-navigation" data-admin-sidebar aria-label="Administration sidebar">
    <div class="admin-sidebar-brand">
        <a class="admin-sidebar-logo" href="{{ route('dashboard') }}" aria-label="{{ $siteName }} administration">
            <span class="admin-brand-text">
                @include('partials.brand-logo')
                <small>ADMIN CONSOLE</small>
            </span>
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
                            @include('partials.sidebar-icon', ['icon' => $item['icon']])
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
