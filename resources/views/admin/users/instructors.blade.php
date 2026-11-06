@extends('layouts.panel')
@section('title', 'Instructor directory')
@section('content')
<section class="admin-instructor-workspace" aria-labelledby="faculty-heading">
    <header class="admin-welcome">
        <div>
            <span class="admin-eyebrow">PEOPLE & ACCESS</span>
            <h1 id="faculty-heading">Instructor directory</h1>
            <p>Manage active teaching accounts, review course assignments, and record accountable access decisions.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('admin.instructor-applications.index', ['status' => 'pending']) }}">Pending applications</a>
            <a class="admin-action-secondary" href="{{ route('admin.users.index', ['role' => 'instructor']) }}">Manage accounts</a>
        </div>
    </header>
    <div class="admin-stats" aria-label="Instructor statistics">
        <div class="admin-stat"><span class="admin-stat-top"><span>Instructors</span></span><strong>{{ number_format($allInstructors) }}</strong><small>Teaching accounts</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>Active instructors</span></span><strong>{{ number_format($activeInstructors) }}</strong><small>Can access teaching tools</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>Suspended</span></span><strong>{{ number_format($suspendedInstructors) }}</strong><small>Teaching access restricted</small></div>
    </div>
    <section class="admin-widget" aria-labelledby="faculty-list-heading">
        <div class="admin-widget-heading">
            <div><span class="admin-widget-kicker">TEACHING DIRECTORY</span>
                <h2 id="faculty-list-heading">Instructor accounts and assignments</h2>
                <p>Showing {{ $instructors->firstItem() ?? 0 }}–{{ $instructors->lastItem() ?? 0 }} of {{ number_format($instructors->total()) }} matching instructors.</p>
            </div>
        </div>
        <form class="admin-course-filters" method="get" action="{{ route('admin.instructors.index') }}">
            <div><label class="sr-only" for="faculty-search">Search instructors</label>
                <input id="faculty-search" name="q" maxlength="100" type="search" value="{{ $search }}" placeholder="Search by name or email">
            </div>
            <div><label class="sr-only" for="faculty-status">Account status</label>
                <select id="faculty-status" name="status">
                    <option value="all" @selected($status === 'all')>All statuses</option>
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="suspended" @selected($status === 'suspended')>Suspended</option>
                </select>
            </div>
            <div><label class="sr-only" for="faculty-activity">Course assignments</label>
                <select id="faculty-activity" name="activity">
                    <option value="all" @selected($activity === 'all')>All instructors</option>
                    <option value="with_courses" @selected($activity === 'with_courses')>Assigned courses</option>
                    <option value="without_courses" @selected($activity === 'without_courses')>No courses</option>
                </select>
            </div>
            <div><label class="sr-only" for="faculty-sort">Sort instructors</label>
                <select id="faculty-sort" name="sort">
                    <option value="name" @selected($sort === 'name')>Name A–Z</option>
                    <option value="newest" @selected($sort === 'newest')>Newest accounts</option>
                    <option value="most_courses" @selected($sort === 'most_courses')>Most courses</option>
                </select>
            </div>
            <button class="admin-action-primary" type="submit">Filter instructors</button>
            @if($search !== '' || $status !== 'all' || $activity !== 'all' || $sort !== 'name')
                <a class="admin-action-secondary" href="{{ route('admin.instructors.index') }}">Clear</a>
            @endif
        </form>
        <div class="table-scroll">
            <table class="admin-table">
                <thead><tr><th scope="col">Instructor</th><th scope="col">Status</th><th scope="col">Courses</th><th scope="col">Published</th><th scope="col">Pending</th><th scope="col">Assignments</th><th scope="col">Account access</th></tr></thead>
                <tbody>
                    @forelse($instructors as $teacher)
                        <tr>
                            <td><strong>{{ $teacher->name }}</strong><small>{{ $teacher->email }}</small></td>
                            <td><span class="admin-status admin-status--{{ $teacher->status === 'active' ? 'success' : 'neutral' }}">{{ ucfirst($teacher->status) }}</span></td>
                            <td>{{ number_format($teacher->courses_count) }}</td>
                            <td>{{ number_format($teacher->published_courses_count) }}</td>
                            <td>{{ number_format($teacher->pending_courses_count) }}</td>
                            <td>
                                <a class="admin-table-action" href="{{ route('admin.instructors.assignments', $teacher) }}">Manage assignments</a>
                                <a class="admin-table-action admin-table-action--quiet" href="{{ route('admin.courses.index', ['instructor' => $teacher->id]) }}">View courses</a>
                            </td>
                            <td>
                                <form class="admin-instructor-decision" method="post"
                                      action="{{ route('admin.instructors.status', $teacher) }}"
                                      data-confirm="Confirm changing teaching access for {{ $teacher->name }}?">
                                    @csrf @method('PATCH')
                                    <div class="admin-instructor-decision-fields">
                                        <label for="faculty-access-reason-{{ $teacher->id }}">Reason for access change
                                            <textarea id="faculty-access-reason-{{ $teacher->id }}" name="reason" rows="2"
                                                      required minlength="10" maxlength="1000" placeholder="Required reason (10–1000 characters)"></textarea>
                                        </label>
                                    </div>
                                    <input type="hidden" name="status" value="{{ $teacher->status === 'active' ? 'suspended' : 'active' }}">
                                    <button class="admin-action-secondary" type="submit">{{ $teacher->status === 'active' ? 'Suspend access' : 'Restore access' }}</button>
                                </form>
                                @if($recentStatusChanges->has($teacher->id))
                                    @php($change = $recentStatusChanges->get($teacher->id))
                                    <small>Last changed by {{ $change->actor?->name ?? 'Former administrator' }}
                                        <time datetime="{{ $change->created_at?->toIso8601String() }}">{{ $change->created_at?->format('M j, Y') }}</time>
                                    </small>
                                    <small>{{ $change->new_values['reason'] ?? '' }}</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="admin-empty"><strong>No matching instructors</strong><p>Adjust your search or filters.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-course-pagination">{{ $instructors->onEachSide(1)->links() }}</div>
        <p class="admin-widget-footnote">Status changes require a reason, are audited, and never change administrator privileges. Course ownership moves require separate eligibility checks.</p>
    </section>
</section>
@endsection
