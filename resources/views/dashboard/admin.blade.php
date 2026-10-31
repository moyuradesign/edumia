@extends('layouts.panel')
@section('title', 'Overview')
@section('content')
<div class="admin-dashboard" data-admin-dashboard>
    <div class="admin-welcome">
        <div>
            <span class="admin-eyebrow"><span class="admin-eyebrow-dot" aria-hidden="true"></span> ADMIN OVERVIEW</span>
            <h1>Welcome back, {{ auth()->user()->name }}</h1>
            <p>Manage the learning platform, track progress, and review items needing attention.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('admin.reports.learning') }}">View reports</a>
            <a class="admin-action-primary" href="{{ route('admin.categories.create') }}">+ New category</a>
        </div>
    </div>

    <div class="admin-stats" aria-label="Platform statistics">
        <a class="admin-stat" href="#admin-members">
            <span class="admin-stat-top"><span>Members</span><span class="admin-stat-icon admin-stat-icon--blue" aria-hidden="true">♙</span></span>
            <strong>{{ number_format($users) }}</strong><small>{{ number_format($instructors) }} instructors</small>
        </a>
        <a class="admin-stat" href="{{ route('admin.courses.index') }}">
            <span class="admin-stat-top"><span>Published courses</span><span class="admin-stat-icon admin-stat-icon--orange" aria-hidden="true">▣</span></span>
            <strong>{{ number_format($publishedCourses) }}</strong><small>{{ number_format($courses) }} total courses</small>
        </a>
        <a class="admin-stat" href="{{ route('admin.reports.learning') }}">
            <span class="admin-stat-top"><span>Enrolled students</span><span class="admin-stat-icon admin-stat-icon--mint" aria-hidden="true">◉</span></span>
            <strong>{{ number_format($students) }}</strong><small>{{ number_format($totalEnrollments) }} total enrollments</small>
        </a>
        <a class="admin-stat" href="{{ route('admin.commerce.orders.index') }}">
            <span class="admin-stat-top"><span>Verified paid orders</span><span class="admin-stat-icon admin-stat-icon--violet" aria-hidden="true">✓</span></span>
            <strong>{{ number_format($paidOrders) }}</strong><small>Paid records only · no estimated revenue</small>
        </a>
    </div>

    <div class="admin-overview-grid">
        <section class="admin-widget admin-chart-widget" aria-labelledby="admin-chart-title">
            <div class="admin-widget-heading">
                <div><span class="admin-widget-kicker">LAST 7 DAYS</span><h2 id="admin-chart-title">Enrollment activity</h2><p>Daily enrollments recorded by the platform</p></div>
                <a href="{{ route('admin.reports.learning') }}">Full report ↗</a>
            </div>
            <div class="admin-enrollment-chart" role="img" aria-label="Daily enrollments over the last seven days">
                @foreach($enrollmentTrend as $day)
                    <div class="admin-chart-column">
                        <span class="admin-chart-value">{{ $day['total'] }}</span>
                        <div class="admin-chart-bar-track">
                            <span class="admin-chart-bar" style="height: {{ $day['total'] ? max(7, round($day['total'] / $maxEnrollments * 100)) : 0 }}%"></span>
                        </div>
                        <time datetime="{{ $day['date'] }}">{{ $day['day'] }}</time>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="admin-widget admin-attention-widget" aria-labelledby="admin-attention-title">
            <div class="admin-widget-heading">
                <div><span class="admin-widget-kicker">WORK QUEUE</span><h2 id="admin-attention-title">Needs attention</h2><p>Open management queues</p></div>
            </div>
            <div class="admin-queue-links">
                <a href="{{ route('admin.courses.index', ['status' => 'pending']) }}"><span class="admin-queue-icon">▣</span><span>Course approvals</span><strong>{{ $pendingCoursesCount }}</strong></a>
                <a href="{{ route('admin.reviews.index') }}"><span class="admin-queue-icon">☆</span><span>Course reviews</span><strong>{{ $pendingReviewsCount }}</strong></a>
                <a href="{{ route('admin.instructor-applications.index') }}"><span class="admin-queue-icon">♙</span><span>Instructor applications</span><strong>{{ $pendingApplicationsCount }}</strong></a>
                <a href="{{ route('admin.commerce.refunds.index') }}"><span class="admin-queue-icon">↩</span><span>Refund requests</span><strong>{{ $pendingRefundsCount }}</strong></a>
                <a href="{{ route('admin.tickets.index') }}"><span class="admin-queue-icon">☏</span><span>Open support tickets</span><strong>{{ $openTicketsCount }}</strong></a>
                <a href="{{ route('admin.contacts.index') }}"><span class="admin-queue-icon">✉</span><span>New contacts</span><strong>{{ $newContactsCount }}</strong></a>
            </div>
        </section>
    </div>

    <div class="admin-section-title">
        <div><h2>Quick management</h2><p>Open the tools you use every day.</p></div>
    </div>
    <div class="admin-shortcut-grid">
        <a href="{{ route('admin.categories.index') }}"><span aria-hidden="true">▦</span><strong>Categories</strong><small>Organize learning</small></a>
        <a href="{{ route('admin.instructor-applications.index') }}"><span aria-hidden="true">♙</span><strong>Instructors</strong><small>Review applications</small></a>
        <a href="{{ route('admin.commerce.orders.index') }}"><span aria-hidden="true">▤</span><strong>Orders</strong><small>Track purchases</small></a>
        <a href="{{ route('admin.media.index') }}"><span aria-hidden="true">▣</span><strong>Media library</strong><small>Manage assets</small></a>
        <a href="{{ route('admin.settings.edit') }}"><span aria-hidden="true">⚙</span><strong>Settings</strong><small>Platform preferences</small></a>
    </div>

    <div class="admin-data-grid">
        <section class="admin-widget" id="admin-pending" aria-labelledby="admin-pending-title">
            <div class="admin-widget-heading"><div><span class="admin-widget-kicker">COURSE MANAGEMENT</span><h2 id="admin-pending-title">Course approval queue</h2></div><a href="{{ route('admin.courses.index', ['status' => 'pending']) }}">All pending courses ↗</a></div>
            @if($pending->isEmpty())
                <div class="admin-empty"><span aria-hidden="true">✓</span><strong>You're all caught up</strong><p>No courses awaiting publication review.</p></div>
            @else
                <div class="table-scroll"><table class="admin-table">
                    <thead><tr><th>Course</th><th>Instructor</th><th>Action</th></tr></thead>
                    <tbody>@foreach($pending as $course)
                        <tr><td><strong>{{ $course->title }}</strong><small>{{ ucfirst($course->level ?: 'All levels') }}</small></td>
                            <td>{{ $course->instructor?->name ?? 'Unknown' }}</td>
                            <td><form action="{{ route('admin.courses.publish', $course) }}" method="post" data-confirm="Publish this course?">
                                @csrf <button class="admin-table-action" type="submit">Publish</button></form></td></tr>
                    @endforeach</tbody>
                </table></div>
            @endif
        </section>
        <section class="admin-widget" aria-labelledby="admin-orders-title">
            <div class="admin-widget-heading"><div><span class="admin-widget-kicker">COMMERCE</span><h2 id="admin-orders-title">Recent orders</h2></div><a href="{{ route('admin.commerce.orders.index') }}">All orders ↗</a></div>
            @if($recentOrders->isEmpty())
                <div class="admin-empty"><span aria-hidden="true">▤</span><strong>No orders yet</strong><p>Orders will appear when customers begin checkout.</p></div>
            @else
                <div class="table-scroll"><table class="admin-table">
                    <thead><tr><th>Customer</th><th>Status</th><th>Amount</th></tr></thead>
                    <tbody>@foreach($recentOrders as $order)<tr>
                        <td><strong>{{ $order->user?->name ?? 'Removed account' }}</strong><small>{{ $order->created_at->format('M j, Y') }}</small></td>
                        <td><span class="admin-status admin-status--{{ $order->paid_at ? 'success' : 'neutral' }}">{{ $order->paid_at ? 'Paid' : ucfirst($order->status) }}</span></td>
                        <td><strong>{{ $order->currency_code }} {{ number_format((float) $order->total, 2) }}</strong></td>
                    </tr>@endforeach</tbody>
                </table></div>
            @endif
        </section>
    </div>

    <section class="admin-widget admin-members-widget" id="admin-members" aria-labelledby="admin-members-title">
        <div class="admin-widget-heading">
            <div><span class="admin-widget-kicker">PEOPLE & ACCESS</span><h2 id="admin-members-title">Member management</h2><p>Review and update access using existing role and account safeguards.</p></div>
            <a href="{{ route('admin.roles.index') }}">Staff roles ↗</a>
        </div>
        <form class="admin-member-search" method="get" action="{{ route('dashboard') }}">
            <label class="sr-only" for="admin-member-q">Find member by name or email</label>
            <input id="admin-member-q" name="member_q" type="search" maxlength="80"
                placeholder="Find member by name or email" value="{{ $memberSearch }}">
            <button type="submit">Search members</button>
            @if($memberSearch !== '')<a href="{{ route('dashboard') }}#admin-members">Clear</a>@endif
        </form>
        <div class="table-scroll"><table class="admin-table">
            <thead><tr><th>Member</th><th>Email</th><th>Access</th></tr></thead>
            <tbody>@forelse($members as $member)
                <tr>
                    <td><div class="admin-member-cell"><span class="admin-member-initial" aria-hidden="true">{{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}</span><strong>{{ $member->name }}</strong></div></td>
                    <td>{{ $member->email }}</td>
                    <td>
                        @if($member->id === auth()->id())
                            <span class="admin-status admin-status--success">Your administrator account</span>
                        @else
                            <form class="admin-member-edit" action="{{ route('admin.users.update', $member) }}" method="post" data-confirm="Apply these access changes to {{ $member->name }}?">
                                @csrf @method('PUT')
                                <label class="sr-only" for="member-role-{{ $member->id }}">Role for {{ $member->name }}</label>
                                <select id="member-role-{{ $member->id }}" name="role">
                                    <option value="student" @selected($member->role === 'student')>Student</option>
                                    <option value="instructor" @selected($member->role === 'instructor')>Instructor</option>
                                    <option value="admin" @selected($member->role === 'admin')>Administrator</option>
                                </select>
                                <label class="sr-only" for="member-status-{{ $member->id }}">Status for {{ $member->name }}</label>
                                <select id="member-status-{{ $member->id }}" name="status">
                                    <option value="active" @selected($member->status === 'active')>Active</option>
                                    <option value="suspended" @selected($member->status === 'suspended')>Suspended</option>
                                </select>
                                <button type="submit">Update</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="3">No matching members were found.</td></tr>
            @endforelse</tbody>
        </table></div>
        @if($memberSearch === '')<p class="admin-widget-footnote">Showing the 10 most recently registered members. Search to find other accounts.</p>@endif
    </section>
    <section class="admin-widget admin-recent-courses" aria-labelledby="admin-recent-courses-title">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">LEARNING</span><h2 id="admin-recent-courses-title">Recent courses</h2></div><a href="{{ route('admin.courses.index') }}">Manage courses ↗</a></div>
        <div class="table-scroll"><table class="admin-table">
            <thead><tr><th>Course</th><th>Instructor</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>@forelse($recentCourses as $course)
                <tr>
                    <td><strong>{{ $course->title }}</strong></td><td>{{ $course->instructor?->name ?? 'Unknown' }}</td>
                    <td><span class="admin-status admin-status--{{ $course->status === 'published' ? 'success' : 'neutral' }}">{{ ucfirst($course->status) }}</span></td>
                    <td>
                        @if($course->status === 'published')
                            <form method="post" action="{{ route('admin.courses.unpublish', $course) }}" data-confirm="Unpublish this course?">
                                @csrf <button class="admin-table-action admin-table-action--quiet" type="submit">Unpublish</button>
                            </form>
                        @elseif($course->status === 'pending')
                            <form method="post" action="{{ route('admin.courses.publish', $course) }}" data-confirm="Publish this course?">
                                @csrf <button class="admin-table-action" type="submit">Publish</button>
                            </form>
                        @else
                            <span class="admin-muted">—</span>
                        @endif
                    </td>
                </tr>
            @empty<tr><td colspan="4">No courses have been created.</td></tr>@endforelse</tbody>
        </table></div>
    </section>
</div>
@endsection
