@extends('layouts.panel')
@section('title', 'Course management')
@section('content')
<section class="admin-course-management" aria-labelledby="admin-course-heading">
    <div class="admin-welcome">
        <div>
            <span class="admin-eyebrow">LEARNING MANAGEMENT</span>
            <h1 id="admin-course-heading">Course management</h1>
            <p>Review public, pending, and draft courses across all instructors.</p>
        </div>
    </div>

    <nav class="admin-course-summary" aria-label="Course status filters">
        @foreach(['all' => 'All courses', 'pending' => 'Pending review', 'published' => 'Published', 'draft' => 'Drafts'] as $key => $label)
            <a class="admin-course-summary-link {{ $status === $key ? 'is-selected' : '' }}"
                href="{{ route('admin.courses.index', array_filter(['status' => $key, 'q' => $search, 'instructor' => $instructor, 'sort' => $sort !== 'newest' ? $sort : null], fn ($value) => $value !== null && $value !== '')) }}"
                @if($status === $key) aria-current="page" @endif>
                <span>{{ $label }}</span><strong>{{ number_format($courseCounts[$key]) }}</strong>
            </a>
        @endforeach
    </nav>

    <div class="admin-widget admin-course-index-widget">
        <form class="admin-course-filters" action="{{ route('admin.courses.index') }}" method="get">
            <div>
                <label class="sr-only" for="admin-course-query">Search courses</label>
                <input id="admin-course-query" type="search" name="q" value="{{ $search }}" maxlength="100"
                       placeholder="Search by course title or slug">
            </div>
            <div>
                <label class="sr-only" for="admin-course-status">Course status</label>
                <select id="admin-course-status" name="status">
                    @foreach(['all' => 'All statuses', 'published' => 'Published', 'pending' => 'Pending review', 'draft' => 'Drafts'] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="sr-only" for="admin-course-instructor">Filter by instructor</label>
                <select name="instructor" id="admin-course-instructor">
                    <option value="">All instructors</option>
                    @foreach($instructors as $teacher)
                        <option value="{{ $teacher->id }}" @selected($instructor === $teacher->id)>{{ $teacher->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="sr-only" for="admin-course-sort">Sort courses</label>
                <select name="sort" id="admin-course-sort">
                    <option value="newest" @selected($sort === 'newest')>Newest first</option>
                    <option value="oldest" @selected($sort === 'oldest')>Oldest first</option>
                    <option value="title" @selected($sort === 'title')>Course title</option>
                </select>
            </div>
            <button class="admin-action-primary" type="submit">Filter courses</button>
            @if($search !== '' || $status !== 'all' || $instructor !== null || $sort !== 'newest')<a class="admin-action-secondary" href="{{ route('admin.courses.index') }}">Clear</a>@endif
        </form>

        <p class="admin-course-results">Showing {{ $courses->firstItem() ?? 0 }}–{{ $courses->lastItem() ?? 0 }} of {{ number_format($courses->total()) }} matching courses</p>
        <div class="table-scroll">
            <table class="admin-table">
                <thead><tr><th>Course</th><th>Instructor</th><th>Learning</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($courses as $course)
                    <tr>
                        <td><strong>{{ $course->title }}</strong><small>{{ $course->category?->name ?? 'Uncategorized' }}</small></td>
                        <td><a class="admin-course-instructor-link" href="{{ route('admin.courses.index', ['instructor' => $course->instructor_id]) }}">{{ $course->instructor?->name ?? 'Unknown' }}</a></td>
                        <td>{{ $course->lessons_count }} lessons<small>{{ number_format($course->enrollments_count) }} enrollments</small></td>
                        <td><span class="admin-status admin-status--{{ $course->status === 'published' ? 'success' : 'neutral' }}">{{ ucfirst($course->status) }}</span></td>
                        <td><div class="admin-course-actions">
                            <a class="admin-table-action admin-table-action--quiet" href="{{ route('instructor.courses.edit', $course) }}">Edit</a>
                            @if($course->status === 'published')
                                <form method="post" action="{{ route('admin.courses.unpublish', $course) }}" data-confirm="Unpublish this course?">
                                    @csrf<button type="submit" class="admin-table-action admin-table-action--quiet">Unpublish</button>
                                </form>
                            @elseif($course->status === 'pending')
                                <form method="post" action="{{ route('admin.courses.publish', $course) }}" data-confirm="Publish this course?">
                                    @csrf<button type="submit" class="admin-table-action">Publish</button>
                                </form>
                            @endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5">No courses match these filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-course-pagination">{{ $courses->onEachSide(1)->links() }}</div>
    </div>
</section>
@endsection
