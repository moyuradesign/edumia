@php
    $teaching = $memberAudience === 'instructor';
    $homeRoute = $teaching ? 'instructor.dashboard' : 'account.home';
    $memberGroups = $teaching ? [
        ['label' => 'Workspace', 'items' => [
            ['label' => 'Overview', 'route' => 'instructor.dashboard', 'match' => 'instructor.dashboard', 'icon' => 'teacher-overview'],
            ['label' => 'Learning reports', 'route' => 'instructor.reports.index', 'match' => 'instructor.reports.*', 'icon' => 'teacher-reports'],
        ]],
        ['label' => 'Teaching management', 'items' => [
            ['label' => 'My courses', 'route' => 'instructor.courses.index', 'match' => 'instructor.courses.*', 'icon' => 'teacher-courses'],
            ['label' => 'My students', 'route' => 'instructor.students.index', 'match' => 'instructor.students.*', 'icon' => 'teacher-students'],
            ['label' => 'Create a course', 'route' => 'instructor.courses.create', 'match' => 'instructor.courses.create', 'icon' => 'teacher-create-course'],
            ['label' => 'Manage ebooks', 'route' => 'instructor.ebooks.index', 'match' => 'instructor.ebooks.*', 'icon' => 'teacher-ebooks'],
            ['label' => 'Manage bootcamps', 'route' => 'instructor.bootcamps.index', 'match' => 'instructor.bootcamps.*', 'icon' => 'teacher-bootcamps'],
            ['label' => 'Tutoring studio', 'route' => 'instructor.tutors.index', 'match' => 'instructor.tutors.*', 'icon' => 'teacher-tutors'],
            ['label' => 'Team training', 'route' => 'team.workspaces.index', 'match' => 'team.workspaces.*', 'icon' => 'team-workspaces'],
        ]],
        ['label' => 'Account & support', 'items' => [
            ['label' => 'My earnings', 'route' => 'instructor.earnings.index', 'match' => 'instructor.earnings.*', 'icon' => 'teacher-earnings'],
            ['label' => 'My learning', 'route' => 'account.home', 'match' => 'account.home', 'icon' => 'teacher-learning'],
            ['label' => 'Notifications', 'route' => 'account.notifications.index', 'match' => 'account.notifications.*', 'icon' => 'account-notifications'],
            ['label' => 'Account preferences', 'route' => 'account.preferences.edit', 'match' => 'account.preferences.*', 'icon' => 'member-preferences'],
            ['label' => 'Password & security', 'route' => 'password.edit', 'match' => 'password.*', 'icon' => 'member-security'],
            ['label' => 'Support tickets', 'route' => 'support.tickets.index', 'match' => 'support.tickets.*', 'icon' => 'member-support'],
        ]],
    ] : [
        ['label' => 'Workspace', 'items' => [
            ['label' => 'Overview', 'route' => 'account.home', 'match' => 'account.home', 'icon' => 'student-overview'],
            ['label' => 'My courses', 'route' => 'account.courses', 'match' => 'account.courses', 'icon' => 'student-courses'],
            ['label' => 'Certificates', 'route' => 'account.certificates', 'match' => 'account.certificates', 'icon' => 'student-certificates'],
        ]],
        ['label' => 'My learning', 'items' => [
            ['label' => 'Saved courses', 'route' => 'saved-courses.index', 'match' => 'saved-courses.*', 'icon' => 'student-saved'],
            ['label' => 'My ebooks', 'route' => 'ebooks.library', 'match' => 'ebooks.library', 'icon' => 'student-ebooks'],
            ['label' => 'My bootcamps', 'route' => 'bootcamps.my', 'match' => 'bootcamps.my', 'icon' => 'student-bootcamps'],
            ['label' => 'Tutor appointments', 'route' => 'tutor.bookings.index', 'match' => 'tutor.bookings.*', 'icon' => 'student-tutors'],
            ['label' => 'Team training', 'route' => 'team.workspaces.index', 'match' => 'team.workspaces.*', 'icon' => 'team-workspaces'],
            ['label' => 'Purchase history', 'route' => 'checkout.orders.index', 'match' => 'checkout.orders.*', 'icon' => 'student-orders'],
        ]],
        ['label' => 'Account & support', 'items' => [
            ['label' => 'Notifications', 'route' => 'account.notifications.index', 'match' => 'account.notifications.*', 'icon' => 'account-notifications'],
            ['label' => 'Account preferences', 'route' => 'account.preferences.edit', 'match' => 'account.preferences.*', 'icon' => 'member-preferences'],
            ['label' => 'Password & security', 'route' => 'password.edit', 'match' => 'password.*', 'icon' => 'member-security'],
            ['label' => 'Support tickets', 'route' => 'support.tickets.index', 'match' => 'support.tickets.*', 'icon' => 'member-support'],
            ['label' => 'Become an instructor', 'route' => 'instructor-application.index', 'match' => 'instructor-application.*', 'icon' => 'student-instructor-application'],
        ]],
    ];
    // In a student view, never offer the instructor-application action to existing teachers or admins.
    if (!$teaching && auth()->user()->canTeach()) {
        $memberGroups[2]['items'] = array_values(array_filter(
            $memberGroups[2]['items'],
            fn ($item) => $item['route'] !== 'instructor-application.index'
        ));
    }
    // A learner/instructor may also hold explicitly delegated staff capabilities.
    // Show only the authorized destinations; no administrator-only links are exposed.
    $staffTools = [];
    if (auth()->user()->hasPermission('media.manage')) {
        $staffTools[] = ['label' => 'Media library', 'route' => 'admin.media.index', 'match' => 'admin.media.*', 'icon' => 'staff-media'];
    }
    if (auth()->user()->hasPermission('support.tickets.manage')) {
        $staffTools[] = ['label' => 'Support inbox', 'route' => 'admin.tickets.index', 'match' => 'admin.tickets.*', 'icon' => 'staff-support'];
    }
    if (auth()->user()->hasPermission('knowledge.manage')) {
        $staffTools[] = ['label' => 'Help center', 'route' => 'admin.knowledge-base.index', 'match' => 'admin.knowledge-base.*', 'icon' => 'staff-help'];
    }
    if ($staffTools !== []) {
        $memberGroups[] = ['label' => 'Staff tools', 'items' => $staffTools];
    }
@endphp
<aside class="admin-sidebar" id="panel-navigation" data-admin-sidebar aria-label="{{ $staffWorkspace ? 'Staff tools' : ($teaching ? 'Instructor' : 'Student') }} navigation">
    <div class="admin-sidebar-brand">
        <a class="admin-sidebar-logo" href="{{ route($homeRoute) }}" aria-label="{{ $siteName }} workspace">
            <span class="admin-brand-text">
                @include('partials.brand-logo')
                <small>{{ $staffWorkspace ? 'STAFF TOOLS' : ($teaching ? 'INSTRUCTOR STUDIO' : 'STUDENT ACCOUNT') }}</small>
            </span>
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
                            @include('partials.sidebar-icon', ['icon' => $item['icon']])
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
