@extends('layouts.panel')
@section('title', 'My students')
@section('content')
<div class="member-dashboard">
    <div class="admin-welcome member-hero">
        <div><span class="admin-eyebrow"><span class="admin-eyebrow-dot" aria-hidden="true"></span> INSTRUCTOR / LEARNERS</span>
            <h1>My students</h1><p>View enrollment and progress records for courses you teach.</p>
        </div>
        <div class="admin-welcome-actions"><a class="admin-action-secondary" href="{{ route('instructor.reports.index') }}">Learning reports</a>
            <a class="admin-action-primary" href="{{ route('instructor.courses.index') }}">My courses</a></div>
    </div>
    <section class="admin-widget" aria-labelledby="my-students-list">
        <div class="admin-widget-heading">
            <div><span class="admin-widget-kicker">COURSE ENROLLMENTS</span><h2 id="my-students-list">Student directory</h2>
                <p>Only enrollment records for your own courses are shown.</p></div>
            <span class="admin-status admin-status--neutral">{{ $enrollments->total() }} enrollments</span>
        </div>

        <form method="get" class="member-filter-form" role="search" action="{{ route('instructor.students.index') }}">
            <label class="sr-only" for="my-students-query">Search students or courses</label>
            <input type="search" id="my-students-query" name="q" maxlength="80"
                   value="{{ $search }}" placeholder="Search by student or course">
            <label class="sr-only" for="my-students-course">Course filter</label>
            <select id="my-students-course" name="course">
                <option value="">All my courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->getKey() }}" @selected($selectedCourse === $course->getKey())>{{ $course->title }}</option>
                @endforeach
            </select>
            <button type="submit" class="admin-action-primary">Apply filters</button>
            @if($selectedCourse !== null || $search !== '')
                <a href="{{ route('instructor.students.index') }}">Clear</a>
            @endif
        </form>

        @if($enrollments->isEmpty())
            <div class="admin-empty"><span aria-hidden="true">◎</span>
                <strong>No matching enrollments</strong><p>Students enrolled in your courses will appear here.</p>
                <a class="admin-action-secondary" href="{{ route('instructor.courses.index') }}">View courses</a>
            </div>
        @else
            <div class="table-scroll"><table class="admin-table">
                <thead><tr><th>Student</th><th>Course</th><th>Progress</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                @foreach($enrollments as $enrollment)
                    <tr>
                        <td><div class="member-person">
                            <span class="member-person-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($enrollment->user->name,0,1)) }}</span>
                            <div><strong>{{ $enrollment->user->name }}</strong></div>
                        </div></td>
                        <td>{{ $enrollment->course->title }}</td>
                        <td>{{ min(100, max(0, (int) $enrollment->progress_percent)) }}%</td>
                        <td><span class="admin-status admin-status--{{ $enrollment->status === 'completed' ? 'success' : 'neutral' }}">{{ ucfirst(str_replace('_',' ', $enrollment->status)) }}</span></td>
                        <td><a class="admin-table-action" href="{{ route('instructor.courses.edit', $enrollment->course) }}">Manage course</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
        {{ $enrollments->links() }}
    </section>
</div>
@endsection
