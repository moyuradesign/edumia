@extends('layouts.panel')
@section('title', 'Course review moderation')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">COURSE QUALITY</span><h1>Moderate reviews</h1>
<p>Only approved learner reviews appear on public course pages.</p></div></div>
<section class="panel-card">
    @if($reviews->isEmpty())
        <p>No course reviews to moderate.</p>
    @else
        <div class="table-scroll"><table>
            <thead><tr><th>Course / learner</th><th>Review</th><th>Rating</th><th>Status</th><th>Moderation</th></tr></thead>
            <tbody>@foreach($reviews as $review)
            <tr>
                <td><strong>{{ $review->course?->title ?? 'Deleted course' }}</strong>
                    <p>{{ $review->author?->name ?? 'Unknown learner' }}</p></td>
                <td><strong>{{ $review->title }}</strong>
                    <div class="lesson-content">{!! nl2br(e($review->body)) !!}</div></td>
                <td>{{ $review->rating }} / 5</td>
                <td>{{ ucfirst($review->status) }}</td>
                <td>
                    <form class="stack-form compact-form" method="post" action="{{ route('admin.reviews.update', $review) }}">
                        @csrf @method('PATCH')
                        <label>Status <select name="status">
                            <option value="pending" @selected($review->status === 'pending')>Pending</option>
                            <option value="approved" @selected($review->status === 'approved')>Approved</option>
                            <option value="rejected" @selected($review->status === 'rejected')>Rejected</option>
                        </select></label>
                        <button class="btn btn-small btn-secondary" type="submit">Save</button>
                    </form>
                </td>
            </tr>
            @endforeach</tbody>
        </table></div>
        {{ $reviews->links() }}
    @endif
</section>
@endsection
