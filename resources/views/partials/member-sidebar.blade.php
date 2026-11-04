@php
    $teaching = $memberAudience === 'instructor';
    $homeRoute = $teaching ? 'instructor.dashboard' : 'account.home';
    $memberGroups = $teaching ? [
        ['label' => 'Workspace', 'items' => [
            ['label' => 'Overview', 'route' => 'instructor.dashboard', 'match' => 'instructor.dashboard', 'icon' => 'home'],
            ['label' => 'Learning reports', 'route' => 'instructor.reports.index', 'match' => 'instructor.reports.*', 'icon' => 'chart'],
        ]],
        ['label' => 'Teaching management', 'items' => [
            ['label' => 'My courses', 'route' => 'instructor.courses.index', 'match' => 'instructor.courses.*', 'icon' => 'book'],
            ['label' => 'My students', 'route' => 'instructor.students.index', 'match' => 'instructor.students.*', 'icon' => 'users'],
            ['label' => 'Create a course', 'route' => 'instructor.courses.create', 'match' => 'instructor.courses.create', 'icon' => 'plus'],
            ['label' => 'Manage ebooks', 'route' => 'instructor.ebooks.index', 'match' => 'instructor.ebooks.*', 'icon' => 'book'],
            ['label' => 'Manage bootcamps', 'route' => 'instructor.bootcamps.index', 'match' => 'instructor.bootcamps.*', 'icon' => 'grid'],
            ['label' => 'Tutoring studio', 'route' => 'instructor.tutors.index', 'match' => 'instructor.tutors.*', 'icon' => 'users'],
            ['label' => 'Team training', 'route' => 'team.workspaces.index', 'match' => 'team.workspaces.*', 'icon' => 'users'],
        ]],
        ['label' => 'Account & support', 'items' => [
            ['label' => 'My earnings', 'route' => 'instructor.earnings.index', 'match' => 'instructor.earnings.*', 'icon' => 'chart'],
            ['label' => 'My learning', 'route' => 'account.home', 'match' => 'account.home', 'icon' => 'book'],
            ['label' => 'Account preferences', 'route' => 'account.preferences.edit', 'match' => 'account.preferences.*', 'icon' => 'settings'],
            ['label' => 'Password & security', 'route' => 'password.edit', 'match' => 'password.*', 'icon' => 'shield'],
            ['label' => 'Support tickets', 'route' => 'support.tickets.index', 'match' => 'support.tickets.*', 'icon' => 'chat'],
        ]],
    ] : [
        ['label' => 'Workspace', 'items' => [
            ['label' => 'Overview', 'route' => 'account.home', 'match' => 'account.home', 'icon' => 'home'],
            ['label' => 'My courses', 'route' => 'account.courses', 'match' => 'account.courses', 'icon' => 'book'],
            ['label' => 'Certificates', 'route' => 'account.certificates', 'match' => 'account.certificates', 'icon' => 'shield'],
        ]],
        ['label' => 'My learning', 'items' => [
            ['label' => 'Saved courses', 'route' => 'saved-courses.index', 'match' => 'saved-courses.*', 'icon' => 'star'],
            ['label' => 'My ebooks', 'route' => 'ebooks.library', 'match' => 'ebooks.library', 'icon' => 'book'],
            ['label' => 'My bootcamps', 'route' => 'bootcamps.my', 'match' => 'bootcamps.my', 'icon' => 'grid'],
            ['label' => 'Tutor appointments', 'route' => 'tutor.bookings.index', 'match' => 'tutor.bookings.*', 'icon' => 'users'],
            ['label' => 'Team training', 'route' => 'team.workspaces.index', 'match' => 'team.workspaces.*', 'icon' => 'users'],
            ['label' => 'Purchase history', 'route' => 'checkout.orders.index', 'match' => 'checkout.orders.*', 'icon' => 'cart'],
        ]],
        ['label' => 'Account & support', 'items' => [
            ['label' => 'Account preferences', 'route' => 'account.preferences.edit', 'match' => 'account.preferences.*', 'icon' => 'settings'],
            ['label' => 'Password & security', 'route' => 'password.edit', 'match' => 'password.*', 'icon' => 'shield'],
            ['label' => 'Support tickets', 'route' => 'support.tickets.index', 'match' => 'support.tickets.*', 'icon' => 'chat'],
            ['label' => 'Become an instructor', 'route' => 'instructor-application.index', 'match' => 'instructor-application.*', 'icon' => 'users'],
        ]],
    ];
    // In a student view, never offer the instructor-application action to existing teachers or admins.
    if (!$teaching && auth()->user()->canTeach()) {
        $memberGroups[2]['items'] = array_values(array_filter(
            $memberGroups[2]['items'],
            fn ($item) => $item['route'] !== 'instructor-application.index'
        ));
    }
@endphp
<aside class="admin-sidebar" id="panel-navigation" data-admin-sidebar aria-label="{{ $teaching ? 'Instructor' : 'Student' }} navigation">
    <div class="admin-sidebar-brand">
        <a class="admin-sidebar-logo" href="{{ route($homeRoute) }}" aria-label="{{ $siteName }} workspace">
            <span class="admin-logo-mark" aria-hidden="true">
                <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 14 20 6l15 8-15 8L5 14Z"/><path d="M11 19v10c6 4 12 4 18 0V19"/><path d="M35 14v12"/></svg>
            </span>
            <span class="admin-brand-text"><strong>{{ $siteName }}</strong><small>{{ $teaching ? 'INSTRUCTOR STUDIO' : 'STUDENT ACCOUNT' }}</small></span>
        </a>
        <button type="button" class="admin-sidebar-close" data-admin-close aria-label="Close sidebar">
            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 5 14 14M19 5 5 19"/></svg>
        </button>
    </div>
    <div class="admin-sidebar-scroll">
        @foreach($memberGroups as $group)
            <section class="admin-nav-group" data-admin-nav-group aria-label="{{ $group['label'] }}">
                <h2>{{ $group['label'] }}</h2>
                <nav aria-label="{{ $group['label'] }}">
                    @foreach($group['items'] as $item)
                        @php $active = request()->routeIs($item['match']) || ($item['route'] === $homeRoute && request()->routeIs('dashboard')); @endphp
                        <a class="admin-nav-link {{ $active ? 'is-active' : '' }}" href="{{ route($item['route']) }}"
                           data-admin-nav-link @if($active) aria-current="page" @endif>
                            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                @switch($item['icon'])
                                    @case('home')<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>@break
                                    @case('book')<path d="M3 5h8a3 3 0 0 1 3 3v12a3 3 0 0 0-3-3H3V5ZM21 5h-7a3 3 0 0 0-3 3v12a3 3 0 0 1 3-3h7V5Z"/>@break
                                    @case('chart')<path d="M4 20V10m6 10V4m6 16v-7m5 7H3"/>@break
                                    @case('grid')<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>@break
                                    @case('star')<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3l-5.6 2.9 1.1-6.2L3 9.6l6.2-.9L12 3Z"/>@break
                                    @case('cart')<circle cx="9" cy="20" r="1"/><circle cx="19" cy="20" r="1"/><path d="M2 3h2l3 13h13l2-10H5"/>@break
                                    @case('settings')<circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3M5 5l2 2m10 10 2 2M19 5l-2 2M7 17l-2 2"/>@break
                                    @case('shield')<path d="M12 3 4 6v6c0 5.5 3.5 8 8 9 4.5-1 8-3.5 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>@break
                                    @case('chat')<path d="M4 4h16v12H8l-4 4V4Z"/><path d="M8 9h8m-8 4h5"/>@break
                                    @case('users')<circle cx="9" cy="8" r="3"/><path d="M3 20v-1c0-3 2-5 6-5s6 2 6 5v1M16 5a3 3 0 0 1 0 6m2 4c2 1 3 2 3 5"/>@break
                                    @case('plus')<path d="M12 4v16M4 12h16"/>@break
                                @endswitch
                            </svg>
                            <span>{{ $item['label'] }}</span>
                            @if($active)<span class="admin-active-indicator" aria-hidden="true"></span>@endif
                        </a>
                    @endforeach
                </nav>
            </section>
        @endforeach
    </div>
    <div class="admin-sidebar-bottom"><a class="admin-view-site" href="{{ route('home') }}"><span>View public website</span><span aria-hidden="true">↗</span></a></div>
</aside>
<div class="admin-sidebar-backdrop" data-admin-backdrop hidden></div>
