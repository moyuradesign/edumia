@extends('layouts.panel')
@section('title', 'Instructor course assignments')
@section('content')
<section class="admin-instructor-workspace" aria-labelledby="instructor-assignment-heading">
    <header class="admin-welcome">
        <div>
            <span class="admin-eyebrow">PEOPLE & ACCESS / COURSE OWNERSHIP</span>
            <h1 id="instructor-assignment-heading">Course assignments</h1>
            <p>{{ $instructor->name }} · {{ $instructor->email }}</p>
            <p>Transfer only uncommitted draft or pending courses. Published, enrolled and ordered courses remain locked to their instructor.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('admin.instructors.index') }}">Instructor directory</a>
            <a class="admin-action-secondary" href="{{ route('admin.courses.index', ['instructor' => $instructor->id]) }}">All courses</a>
        </div>
    </header>
    <section class="admin-widget" aria-labelledby="assignment-list-heading">
        <div class="admin-widget-heading">
            <div><span class="admin-widget-kicker">ASSIGNED COURSE RECORDS</span>
                <h2 id="assignment-list-heading">{{ $instructor->name }}'s courses</h2>
                <p>Showing {{ $courses->firstItem() ?? 0 }}–{{ $courses->lastItem() ?? 0 }} of {{ number_format($courses->total()) }} assigned courses.</p>
            </div>
        </div>
        <div class="table-scroll">
            <table class="admin-table">
                <thead><tr><th scope="col">Course</th><th scope="col">Status</th><th scope="col">Learners</th><th scope="col">Assignment decision</th></tr></thead>
                <tbody>
                    @forelse($courses as $course)
                        @php($eligible = in_array($course->status, ['draft', 'pending'], true)
                            && $course->published_at === null && $course->enrollments_count === 0)
                        <tr>
                            <td><strong>{{ $course->title }}</strong><small>Course #{{ $course->id }}</small></td>
                            <td><span class="admin-status admin-status--{{ $course->status === 'published' ? 'success' : 'neutral' }}">{{ ucfirst($course->status) }}</span></td>
                            <td>{{ number_format($course->enrollments_count) }}</td>
                            <td>
                                @if($eligible && $targets->isNotEmpty())
                                    <form class="admin-instructor-decision" method="post"
                                          action="{{ route('admin.instructors.assignments.reassign', [$instructor, $course]) }}"
                                          data-confirm="Transfer ownership of {{ $course->title }}? This is audited.">
                                        @csrf
                                        <div class="admin-instructor-decision-fields">
                                            <label for="assignment-target-{{ $course->id }}">Assign to active instructor
                                                <select id="assignment-target-{{ $course->id }}" name="new_instructor_id" required>
                                                    <option value="">Select instructor</option>
                                                    @foreach($targets as $target)
                                                        <option value="{{ $target->id }}">{{ $target->name }} ({{ $target->email }})</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label for="assignment-reason-{{ $course->id }}">Reason for reassignment
                                                <textarea id="assignment-reason-{{ $course->id }}" name="reason"
                                                          required minlength="10" maxlength="1000" rows="2"
                                                          placeholder="Required transfer reason (10–1000 characters)"></textarea>
                                            </label>
                                        </div>
                                        <button class="admin-action-primary" type="submit">Transfer assignment</button>
                                        <small>Transfer is blocked if an order exists, even when other criteria are satisfied.</small>
                                    </form>
                                @else
                                    <span class="admin-status admin-status--neutral">Transfer unavailable</span>
                                    <small>Publication history, learners or instructor availability may prevent transfer.</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="admin-empty"><strong>No assigned courses</strong><p>This instructor has no courses yet.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-course-pagination">{{ $courses->onEachSide(1)->links() }}</div>
        <p class="admin-widget-footnote">Each ownership change is validated again inside a database transaction and recorded in the audit log. Orders, enrollments and existing instructor earnings are never reassigned by this tool.</p>
    </section>
</section>
@endsection
