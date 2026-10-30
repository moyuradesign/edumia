@extends('layouts.panel')
@section('title','Administrator')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">ADMINISTRATOR WORKSPACE</span><h1>Overview</h1><p>Review the Edumia learning platform, approvals, and members.</p></div></div>
<div class="metric-grid"><article class="metric"><span>Members</span><strong>{{ $users }}</strong></article><article class="metric"><span>Instructors</span><strong>{{ $instructors }}</strong></article><article class="metric"><span>Courses</span><strong>{{ $courses }}</strong></article><article class="metric"><span>Enrolled students</span><strong>{{ $students }}</strong></article></div>
<section class="panel-card"><h2>Course approval queue</h2>
@if($pending->isEmpty())<p>No courses awaiting review.</p>@else
<div class="table-scroll"><table><thead><tr><th>Course</th><th>Author</th><th>Actions</th></tr></thead><tbody>
@foreach($pending as $course)<tr><td>{{ $course->title }}</td><td>{{ $course->instructor->name ?? 'Unknown' }}</td><td>
<a href="{{ route('instructor.courses.edit',$course) }}">Review</a>
<form method="post" action="{{ route('admin.courses.publish',$course) }}" class="inline-form">@csrf<button class="btn btn-small btn-primary">Publish</button></form>
</td></tr>@endforeach</tbody></table></div>@endif</section>
<section class="panel-card"><h2>Recent courses</h2><div class="table-scroll"><table><thead><tr><th>Title</th><th>Instructor</th><th>Status</th><th>Manage</th></tr></thead><tbody>
@foreach($recentCourses as $course)<tr><td>{{ $course->title }}</td><td>{{ $course->instructor->name ?? 'Unknown' }}</td><td>{{ ucfirst($course->status) }}</td><td>
<a href="{{ route('instructor.courses.edit',$course) }}">Edit</a>
@if($course->status === 'published')<form action="{{ route('admin.courses.unpublish',$course) }}" method="post" class="inline-form">@csrf<button class="btn btn-small">Unpublish</button></form>@endif
</td></tr>@endforeach</tbody></table></div></section>
<section class="panel-card"><h2>Member roles and access</h2><p>New accounts always start as students. Assign instructor rights only to trusted educators.</p>
<div class="table-scroll"><table><thead><tr><th>Name</th><th>Email</th><th>Role / status</th><th>Save</th></tr></thead><tbody>
@foreach($members as $member)<tr><td>{{ $member->name }}</td><td>{{ $member->email }}</td><td colspan="2">
@if($member->id === auth()->id())<strong>Your administrator account</strong>@else
<form method="post" action="{{ route('admin.users.update',$member) }}" class="inline-form">@csrf @method('PUT')
<select name="role" aria-label="Role for {{ $member->name }}"><option @selected($member->role==='student') value="student">Student</option><option @selected($member->role==='instructor') value="instructor">Instructor</option><option @selected($member->role==='admin') value="admin">Administrator</option></select>
<select name="status" aria-label="Status for {{ $member->name }}"><option @selected($member->status==='active') value="active">Active</option><option @selected($member->status==='suspended') value="suspended">Suspended</option></select>
<button class="btn btn-small">Update</button></form>@endif</td></tr>@endforeach</tbody></table></div></section>
@endsection
