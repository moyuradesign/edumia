@extends('layouts.panel')
@section('title', 'My courses')
@section('content')
<div class="member-dashboard">
    <div class="admin-welcome"><div><span class="admin-eyebrow"><span class="admin-eyebrow-dot" aria-hidden="true"></span> INSTRUCTOR STUDIO</span><h1>My courses</h1><p>Manage your own course drafts, reviews and publications.</p></div><div class="admin-welcome-actions"><a class="admin-action-primary" href="{{ route('instructor.courses.create') }}">Create course</a></div></div>
    <section class="admin-widget">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">TEACHING MANAGEMENT</span><h2>Course directory</h2></div></div>
        <form class="member-filter-form" method="get" action="{{ route('instructor.courses.index') }}" role="search">
            <label class="sr-only" for="instructor-course-search">Find a course</label>
            <input type="search" id="instructor-course-search" name="q" maxlength="80" value="{{ $search }}" placeholder="Find a course">
            <label class="sr-only" for="instructor-course-status">Publication status</label>
            <select id="instructor-course-status" name="status"><option value="">All statuses</option>
                @foreach(['draft' => 'Draft', 'pending' => 'Pending review', 'published' => 'Published', 'rejected' => 'Rejected'] as $key => $label)
                    <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="admin-action-primary" type="submit">Apply filters</button>
            @if($search !== '' || $status !== '')<a href="{{ route('instructor.courses.index') }}">Clear</a>@endif
        </form>
        @if($courses->isEmpty())
            <div class="admin-empty"><span aria-hidden="true">▣</span><strong>No matching courses</strong><p>Try a different filter or create a new draft.</p><a class="admin-action-secondary" href="{{ route('instructor.courses.create') }}">Create course</a></div>
        @else
            <div class="table-scroll"><table class="admin-table">
                <thead><tr><th>Course</th><th>Status</th><th>Lessons</th><th>Enrollments</th><th>Action</th></tr></thead>
                <tbody>@foreach($courses as $course)
                    <tr><td><strong>{{ $course->title }}</strong></td><td><span class="admin-status admin-status--{{ $course->status === 'published' ? 'success' : 'neutral' }}">{{ ucfirst($course->status) }}</span></td>
                        <td>{{ $course->lessons_count }}</td><td>{{ $course->enrollments_count }}</td>
                        <td><a class="admin-table-action" href="{{ route('instructor.courses.edit', $course) }}">Manage</a></td></tr>
                @endforeach</tbody>
            </table></div>
        @endif
        {{ $courses->links() }}
    </section>
</div>
@endsection
