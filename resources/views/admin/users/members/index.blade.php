@extends('layouts.panel')
@section('title', 'Member directory')
@section('content')
<section class="admin-member-directory" aria-labelledby="admin-directory-heading">
    <div class="admin-welcome">
        <div>
            <span class="admin-eyebrow">PEOPLE & ACCESS</span>
            <h1 id="admin-directory-heading">Member directory</h1>
            <p>Search platform accounts, review enrollment activity, and manage access securely.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('admin.roles.index') }}">Staff roles</a>
            <a class="admin-action-secondary" href="{{ route('dashboard') }}#admin-members">Back to overview</a>
        </div>
    </div>

    <div class="admin-stats" aria-label="Member statistics">
        <div class="admin-stat"><span class="admin-stat-top"><span>All members</span></span><strong>{{ number_format($totalMembers) }}</strong><small>Registered accounts</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>Active</span></span><strong>{{ number_format($activeMembers) }}</strong><small>May sign in</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>Instructors</span></span><strong>{{ number_format($totalInstructors) }}</strong><small>Primary instructor role</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>Suspended</span></span><strong>{{ number_format($suspendedMembers) }}</strong><small>Access restricted</small></div>
    </div>

    <div class="admin-widget admin-member-directory-widget">
        <div class="admin-widget-heading">
            <div>
                <span class="admin-widget-kicker">ACCOUNT MANAGEMENT</span>
                <h2>All members</h2>
                <p>Showing {{ $members->firstItem() ?? 0 }}–{{ $members->lastItem() ?? 0 }} of {{ number_format($members->total()) }} matching accounts.</p>
            </div>
        </div>

        <form class="admin-course-filters admin-member-filters" method="get" action="{{ route('admin.users.index') }}">
            <div>
                <label class="sr-only" for="admin-member-directory-search">Search by name or email</label>
                <input id="admin-member-directory-search" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="Search name or email…">
            </div>
            <div>
                <label class="sr-only" for="admin-member-directory-role">Filter by role</label>
                <select id="admin-member-directory-role" name="role">
                    @foreach(['all' => 'All roles', 'student' => 'Students', 'instructor' => 'Instructors', 'admin' => 'Administrators'] as $value => $label)
                        <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="sr-only" for="admin-member-directory-status">Filter by account status</label>
                <select id="admin-member-directory-status" name="status">
                    @foreach(['all' => 'All statuses', 'active' => 'Active', 'suspended' => 'Suspended'] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="admin-action-primary" type="submit">Apply filters</button>
            @if($search !== '' || $role !== 'all' || $status !== 'all')
                <a class="admin-action-secondary" href="{{ route('admin.users.index') }}">Clear</a>
            @endif
        </form>

        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr><th>Member</th><th>Primary role</th><th>Status</th><th>Courses</th><th>Enrollments</th><th>Joined</th><th>Access controls</th></tr>
                </thead>
                <tbody>
                @forelse($members as $member)
                    <tr>
                        <td>
                            <div class="admin-member-cell">
                                <span class="admin-member-initial" aria-hidden="true">{{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}</span>
                                <div><strong>{{ $member->name }}</strong><small>{{ $member->email }}</small><small>Account #{{ $member->id }}</small></div>
                            </div>
                        </td>
                        <td>{{ ucfirst($member->role) }}</td>
                        <td><span class="admin-status admin-status--{{ $member->status === 'active' ? 'success' : 'neutral' }}">{{ ucfirst($member->status) }}</span></td>
                        <td>{{ number_format($member->courses_count) }}</td>
                        <td>{{ number_format($member->enrollments_count) }}</td>
                        <td><time datetime="{{ $member->created_at?->toDateString() }}">{{ $member->created_at?->format('M j, Y') ?? '—' }}</time></td>
                        <td>
                            @if($member->id === auth()->id())
                                <span class="admin-status admin-status--success">Your administrator account</span>
                            @else
                                <form class="admin-member-edit" action="{{ route('admin.users.update', $member) }}" method="post"
                                      data-confirm="Apply these access changes to {{ $member->name }}?">
                                    @csrf @method('PUT')
                                    <label class="sr-only" for="directory-role-{{ $member->id }}">Role for {{ $member->name }}</label>
                                    <select id="directory-role-{{ $member->id }}" name="role">
                                        @foreach(['student' => 'Student', 'instructor' => 'Instructor', 'admin' => 'Administrator'] as $value => $label)
                                            <option value="{{ $value }}" @selected($member->role === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <label class="sr-only" for="directory-status-{{ $member->id }}">Status for {{ $member->name }}</label>
                                    <select id="directory-status-{{ $member->id }}" name="status">
                                        <option value="active" @selected($member->status === 'active')>Active</option>
                                        <option value="suspended" @selected($member->status === 'suspended')>Suspended</option>
                                    </select>
                                    <button type="submit">Save</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="admin-empty"><strong>No matching members</strong><p>Try another name, role, or account status.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-course-pagination">{{ $members->onEachSide(1)->links() }}</div>
        <p class="admin-widget-footnote">Changes use existing administrator checks, protect the last active administrator, and record role or status changes in the audit log.</p>
    </div>
</section>
@endsection
