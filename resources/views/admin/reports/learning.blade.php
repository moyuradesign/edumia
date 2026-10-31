@extends('layouts.panel')
@section('title', 'Learning reports')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">PLATFORM INSIGHTS</span><h1>Learning reports</h1>
<p>Current enrollment and completion metrics from Edumia's database. These are not financial transaction reports.</p></div></div>
<section class="panel-card">
<div class="report-grid">
@foreach($totals as $metric=>$value)
<div class="builder-section"><p>{{ ucwords(str_replace('_', ' ', $metric)) }}</p>
<h2>{{ number_format($value) }}</h2></div>
@endforeach
</div>
</section>
<section class="panel-card"><h2>Course performance</h2>
<div class="table-scroll"><table>
<thead><tr><th>Course</th><th>Instructor</th><th>Status</th><th>Enrolled</th><th>Completed</th></tr></thead>
<tbody>@forelse($courses as $course)
<tr><td>{{ $course->title }}</td><td>{{ $course->instructor?->name ?? '—' }}</td>
<td>{{ ucfirst($course->status) }}</td><td>{{ $course->enrollments_count }}</td>
<td>{{ $course->completed_count }}</td></tr>
@empty<tr><td colspan="5">No courses yet.</td></tr>@endforelse
</tbody></table></div>{{ $courses->links() }}
</section>
@endsection
