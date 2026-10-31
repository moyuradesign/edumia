@extends('layouts.panel')
@section('title', 'Assignment gradebook')
@section('content')
<div class="panel-heading">
    <div><span class="section-kicker">INSTRUCTOR · ASSESSMENT</span>
        <h1>{{ $assignment->title }} — Gradebook</h1>
        <p>{{ $course->title }} · Maximum {{ $assignment->max_score }} points</p></div>
    <a class="btn btn-quiet" href="{{ route('instructor.courses.edit', $course) }}">Back to course</a>
</div>
<section class="panel-card">
    <h2>Student submissions</h2>
    @if($submissions->isEmpty())
        <p>No learner submissions yet.</p>
    @else
    <div class="table-scroll"><table>
        <thead><tr><th>Student</th><th>Submitted</th><th>Grade</th><th>Review and feedback</th></tr></thead>
        <tbody>
        @foreach($submissions as $submission)
            <tr>
                <td><strong>{{ $submission->student?->name ?? 'Unknown student' }}</strong></td>
                <td>{{ $submission->submitted_at?->format('M d, Y H:i') }}</td>
                <td>{{ $submission->graded_at ? $submission->score.' / '.$assignment->max_score : 'Pending' }}</td>
                <td>
                    <details>
                        <summary>Review response</summary>
                        <div class="lesson-content">{!! nl2br(e($submission->response)) !!}</div>
                        <form class="stack-form compact-form" method="post" action="{{ route('instructor.assignments.grade', [$course, $assignment, $submission]) }}">
                            @csrf @method('PUT')
                            <label>Score <input type="number" required step="0.01" min="0" max="{{ $assignment->max_score }}" name="score" value="{{ old('score', $submission->score) }}"></label>
                            <label>Feedback <textarea name="feedback" rows="4" maxlength="10000">{{ old('feedback', $submission->feedback) }}</textarea></label>
                            <button class="btn btn-primary" type="submit">{{ $submission->graded_at ? 'Update grade' : 'Save grade' }}</button>
                        </form>
                    </details>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    {{ $submissions->links() }}
    @endif
</section>
@endsection
