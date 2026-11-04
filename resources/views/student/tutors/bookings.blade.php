@extends('layouts.panel')
@section('title', 'My tutoring appointments')
@section('content')
<div class="member-dashboard">
    <div class="admin-welcome member-hero">
        <div><span class="admin-eyebrow">LEARNING / TUTORING</span>
            <h1>My tutoring appointments</h1>
            <p>Follow your requested, confirmed and completed tutor sessions.</p></div>
        <div class="admin-welcome-actions"><a class="admin-action-primary" href="{{ route('tutors.index') }}">Find a tutor</a></div>
    </div>
    <section class="admin-widget">
        @if($bookings->isEmpty())
            <div class="admin-empty"><strong>No appointments yet</strong><p>Your free tutor sessions will appear here when you book an available time.</p>
                <a class="admin-action-primary" href="{{ route('tutors.index') }}">Browse tutors</a></div>
        @else
            <div class="table-scroll"><table class="admin-table">
                <thead><tr><th>Tutor</th><th>Subject</th><th>Appointment (UTC)</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>@foreach($bookings as $booking)
                    <tr>
                        <td><strong>{{ $booking->tutor?->name ?? 'Unavailable tutor' }}</strong></td>
                        <td>{{ $booking->subject?->name ?? 'Subject removed' }}</td>
                        <td>{{ $booking->scheduled_at->format('M j, Y H:i') }} UTC</td>
                        <td><span class="admin-status admin-status--{{ $booking->status==='confirmed'?'success':'neutral' }}">{{ ucfirst($booking->status) }}</span></td>
                        <td>
                            @if($booking->status === 'confirmed' && $booking->meeting_url)
                                <a href="{{ $booking->meeting_url }}" rel="noopener noreferrer" target="_blank" class="admin-table-action">Join meeting</a>
                            @endif
                            @if(in_array($booking->status, ['pending','confirmed'],true))
                                <form method="post" action="{{ route('tutor.bookings.cancel',$booking) }}">
                                    @csrf @method('PATCH') <button class="btn btn-small btn-quiet">Cancel booking</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach</tbody>
            </table></div>
        @endif
        {{ $bookings->links() }}
    </section>
</div>
@endsection
