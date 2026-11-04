@extends('layouts.panel')
@section('title', 'Team training packages')
@section('content')
<div class="admin-dashboard">
    <div class="admin-welcome">
        <div><span class="admin-eyebrow">ADMIN / TEAM TRAINING</span>
            <h1>Team training packages</h1>
            <p>Publish free team seats linked to approved courses. Paid packages are unavailable until provider acceptance.</p>
        </div>
        <div class="admin-welcome-actions"><a class="admin-action-secondary" href="{{ route('teams.index') }}">Public catalogue</a></div>
    </div>
    <section class="admin-widget">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">PACKAGE BUILDER</span><h2>Create a free team package</h2>
            <p>Only free, published, public courses can be assigned.</p></div></div>
        @if($courses->isEmpty())
            <div class="admin-empty"><strong>No eligible courses</strong><p>Publish a free course before creating team access packages.</p>
                <a class="admin-action-primary" href="{{ route('admin.courses.index') }}">Manage courses</a></div>
        @else
            <form method="post" action="{{ route('admin.team-training.store') }}" class="stack-form">
                @csrf
                <div class="form-two">
                    <label>Package title<input name="name" value="{{ old('name') }}" minlength="3" maxlength="190" required></label>
                    <label>Included course<select name="course_id" required>
                        <option value="">Choose a published course</option>
                        @foreach($courses as $course)<option value="{{ $course->id }}" @selected(old('course_id')==$course->id)>{{ $course->title }}</option>@endforeach
                    </select></label>
                </div>
                <div class="form-two">
                    <label>Seats per team<input type="number" name="seats" value="{{ old('seats',10) }}" min="1" max="200" required></label>
                    <label>Package details<textarea name="description" maxlength="5000" rows="3">{{ old('description') }}</textarea></label>
                </div>
                <button class="btn btn-primary">Publish free package</button>
            </form>
        @endif
    </section>
    <section class="admin-widget">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">CURRENT PACKAGES</span><h2>Team catalogue</h2>
            <p>Existing team workspaces keep their access when a listing is disabled.</p></div></div>
        @if($packages->isEmpty())
            <div class="admin-empty"><strong>No team packages</strong><p>Publish a package to accept real team training registrations.</p></div>
        @else
            <div class="table-scroll"><table class="admin-table">
                <thead><tr><th>Package</th><th>Course</th><th>Seats</th><th>Teams</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>@foreach($packages as $package)
                    <tr>
                        <td><strong>{{ $package->name }}</strong></td>
                        <td>{{ $package->course?->title ?? 'Course removed' }}</td>
                        <td>{{ $package->seats }}</td>
                        <td>{{ $package->purchases_count }}</td>
                        <td><span class="admin-status admin-status--{{ $package->status==='active'?'success':'neutral' }}">{{ ucfirst($package->status) }}</span></td>
                        <td><form method="post" action="{{ route('admin.team-training.status',$package) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $package->status==='active'?'inactive':'active' }}">
                            <button class="admin-table-action">{{ $package->status==='active'?'Stop new registrations':'Enable listing' }}</button>
                        </form></td>
                    </tr>
                @endforeach</tbody>
            </table></div>
        @endif
        {{ $packages->links() }}
    </section>
</div>
@endsection
