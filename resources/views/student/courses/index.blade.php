@extends('layouts.panel')
@section('title', 'My courses')
@section('content')
<div class="member-dashboard">
    <div class="admin-welcome">
        <div><span class="admin-eyebrow"><span class="admin-eyebrow-dot" aria-hidden="true"></span> MY LEARNING</span>
            <h1>My courses</h1><p>Search your enrolled courses and continue where you left off.</p></div>
        <div class="admin-welcome-actions"><a class="admin-action-primary" href="{{ route('courses.index') }}">Explore courses</a></div>
    </div>
    <section class="admin-widget" aria-labelledby="member-course-list-title">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">ENROLLMENTS</span><h2 id="member-course-list-title">Course library</h2></div></div>
        <form class="member-filter-form" method="get" action="{{ route('account.courses') }}" role="search">
            <label class="sr-only" for="student-course-search">Search my courses</label>
            <input type="search" id="student-course-search" name="q" maxlength="80" value="{{ $search }}" placeholder="Search my courses">
            <label class="sr-only" for="student-course-status">Progress status</label>
            <select id="student-course-status" name="status">
                <option value="">All courses</option>
                <option value="active" @selected($filter === 'active')>In progress</option>
                <option value="completed" @selected($filter === 'completed')>Completed</option>
            </select>
            <button class="admin-action-primary" type="submit">Apply filters</button>
            @if($search !== '' || $filter !== '')<a href="{{ route('account.courses') }}">Clear</a>@endif
        </form>
        @forelse($enrollments as $enrollment)
            <article class="member-learning-item">
                <span class="member-course-symbol" aria-hidden="true">▣</span>
                <div class="member-learning-detail">
                    <h3>{{ $enrollment->course->title }}</h3>
                    <div class="member-learning-meta"><span>{{ $enrollment->progress_percent }}% complete</span><span>{{ ucfirst(str_replace('_', ' ', $enrollment->status)) }}</span></div>
                    <progress value="{{ min(100, max(0, $enrollment->progress_percent)) }}" max="100" aria-label="Progress for {{ $enrollment->course->title }}"></progress>
                </div>
                <a class="admin-table-action" href="{{ route('learning.course', $enrollment->course) }}">Open course</a>
            </article>
        @empty
            <div class="admin-empty"><span aria-hidden="true">▣</span><strong>No matching courses</strong><p>Try another filter or browse the course catalog.</p><a class="admin-action-secondary" href="{{ route('courses.index') }}">Browse courses</a></div>
        @endforelse
        {{ $enrollments->links() }}
    </section>
</div>
@endsection
