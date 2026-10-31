@extends('layouts.panel')
@section('title', 'Instructor learning reports')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">INSTRUCTOR INSIGHTS</span><h1>My learning reports</h1>
<p>Enrollment and completion numbers for your own courses only.</p></div></div>
<section class="panel-card">
<p>Quiz submissions across your courses: <strong>{{ number_format($quizAttempts) }}</strong></p>
<div class="table-scroll"><table>
<thead><tr><th>Course</th><th>Enrolled</th><th>Completed</th><th>Completion ratio</th></tr></thead>
<tbody>@forelse($courses as $course)
<tr><td>{{ $course->title }}</td><td>{{ $course->enrollments_count }}</td>
<td>{{ $course->completed_count }}</td>
<td>{{ $course->enrollments_count ? round(100 * $course->completed_count / $course->enrollments_count, 1).'%' : '—' }}</td></tr>
@empty<tr><td colspan="4">No course data yet.</td></tr>@endforelse
</tbody></table></div>
{{ $courses->links() }}
</section>
@endsection
