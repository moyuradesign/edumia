@extends('layouts.panel')
@section('title', 'Instructor directory')
@section('content')
<section class="admin-instructor-workspace" aria-labelledby="faculty-heading">
    <header class="admin-welcome">
        <div>
            <span class="admin-eyebrow">PEOPLE & ACCESS</span>
            <h1 id="faculty-heading">Instructor directory</h1>
            <p>Find approved teaching accounts and review their real course activity.</p>
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
                <h2 id="faculty-list-heading">Instructor accounts</h2>
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
            <button class="admin-action-primary" type="submit">Filter instructors</button>
            @if($search !== '' || $status !== 'all')<a class="admin-action-secondary" href="{{ route('admin.instructors.index') }}">Clear</a>@endif
        </form>
        <div class="table-scroll">
            <table class="admin-table">
                <thead><tr><th scope="col">Instructor</th><th scope="col">Status</th><th scope="col">Courses</th><th scope="col">Published</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                    @forelse($instructors as $teacher)
                        <tr>
                            <td><strong>{{ $teacher->name }}</strong><small>{{ $teacher->email }}</small></td>
                            <td><span class="admin-status admin-status--{{ $teacher->status === 'active' ? 'success' : 'neutral' }}">{{ ucfirst($teacher->status) }}</span></td>
                            <td>{{ number_format($teacher->courses_count) }}</td>
                            <td>{{ number_format($teacher->published_courses_count) }}</td>
                            <td><a class="admin-table-action" href="{{ route('admin.courses.index', ['instructor' => $teacher->id]) }}">View courses</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="admin-empty"><strong>No matching instructors</strong><p>Adjust your search or filters.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-course-pagination">{{ $instructors->onEachSide(1)->links() }}</div>
    </section>
</section>
@endsection
