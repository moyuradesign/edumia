@extends('layouts.panel')
@section('title', 'Tutoring studio')
@section('content')
<div class="member-dashboard">
    <div class="admin-welcome member-hero">
        <div><span class="admin-eyebrow">INSTRUCTOR / TUTORING</span>
            <h1>Tutoring studio</h1>
            <p>Publish free tutoring subjects, manage availability and confirm real appointments.</p></div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('tutors.index') }}">Public tutor directory</a>
        </div>
    </div>
    <div class="admin-data-grid">
        <section class="admin-widget">
            <div class="admin-widget-heading"><div><span class="admin-widget-kicker">SUBJECTS</span><h2>What you teach</h2>
                <p>Paid tutoring stays unavailable until verified payment support is approved.</p></div></div>
            <form method="post" action="{{ route('instructor.tutors.offer') }}" class="stack-form">
                @csrf
                <label>Subject name<input name="subject_name" value="{{ old('subject_name') }}" minlength="3" maxlength="100" required></label>
                <button class="btn btn-primary">Add free subject</button>
            </form>
            <div class="member-record-list">
                @forelse($offerings as $offering)
                    <p><strong>{{ $offering->subject->name }}</strong> <span class="admin-status admin-status--success">Free appointment</span></p>
                @empty
                    <p class="subtle">No subjects published. Add one to make your tutoring profile available.</p>
                @endforelse
            </div>
        </section>
        <section class="admin-widget">
            <div class="admin-widget-heading"><div><span class="admin-widget-kicker">WEEKLY HOURS</span><h2>Availability</h2>
                <p>All time fields below use the time zone you select. Appointments last one hour.</p></div></div>
            <form method="post" action="{{ route('instructor.tutors.schedule') }}" class="stack-form">
                @csrf
                <div class="form-two">
                    <label>Day<select name="weekday" required>
                        @foreach([1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'] as $day=>$name)
                            <option value="{{ $day }}" @selected(old('weekday',1)==$day)>{{ $name }}</option>
                        @endforeach
                    </select></label>
                    <label>Time zone<select name="timezone" required>
                        @foreach(['Asia/Phnom_Penh','Asia/Bangkok','Asia/Singapore','Asia/Tokyo','Europe/London','America/New_York','UTC'] as $timezone)
                            <option value="{{ $timezone }}" @selected(old('timezone',$schedules->first()?->timezone ?? 'Asia/Phnom_Penh')===$timezone)>{{ $timezone }}</option>
                        @endforeach
                    </select></label>
                </div>
                <div class="form-two">
                    <label>Start time<input type="time" name="start_time" value="{{ old('start_time','09:00') }}" required></label>
                    <label>End time<input type="time" name="end_time" value="{{ old('end_time','17:00') }}" required></label>
                </div>
                <button class="btn btn-secondary">Publish available hours</button>
            </form>
            @forelse($schedules->where('active',true) as $schedule)
                <div class="member-record-summary">
                    <strong>{{ [1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'][$schedule->weekday] }}</strong>
                    {{ substr($schedule->start_time,0,5) }}–{{ substr($schedule->end_time,0,5) }} ({{ $schedule->timezone }})
                    <form method="post" action="{{ route('instructor.tutors.schedule.remove',$schedule) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-small btn-quiet">Stop future availability</button>
                    </form>
                </div>
            @empty
                <p class="subtle">No active hours. Publish availability before learners can book.</p>
            @endforelse
        </section>
    </div>
    <section class="admin-widget">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">APPOINTMENTS</span><h2>Tutor bookings</h2>
            <p>Only bookings made for your own subjects appear here.</p></div></div>
        @if($bookings->isEmpty())
            <div class="admin-empty"><strong>No bookings yet</strong><p>When learners request an available time, their appointments will appear here.</p></div>
        @else
            <div class="table-scroll"><table class="admin-table">
                <thead><tr><th>Learner</th><th>Subject</th><th>Scheduled time</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>@foreach($bookings as $booking)
                    <tr>
                        <td>{{ $booking->learner?->name ?? 'Unavailable account' }}</td>
                        <td>{{ $booking->subject?->name ?? 'Subject removed' }}</td>
                        <td>{{ $booking->scheduled_at->format('M j, Y H:i') }} UTC</td>
                        <td><span class="admin-status admin-status--{{ $booking->status === 'confirmed' ? 'success' : 'neutral' }}">{{ ucfirst($booking->status) }}</span></td>
                        <td>
                            @if($booking->status === 'pending')
                                <form method="post" action="{{ route('instructor.tutors.booking.update',$booking) }}" class="stack-form compact-form">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="confirmed">
                                    <label>Secure meeting URL (optional)<input type="url" name="meeting_url" maxlength="255" placeholder="https://"></label>
                                    <button class="btn btn-small btn-primary">Confirm</button>
                                </form>
                            @endif
                            @if(in_array($booking->status,['pending','confirmed'],true))
                                @if($booking->status==='confirmed' && $booking->scheduled_at->addHour()->isPast())
                                    <form method="post" action="{{ route('instructor.tutors.booking.update',$booking) }}">
                                        @csrf @method('PATCH') <input type="hidden" name="status" value="completed">
                                        <button class="btn btn-small btn-secondary">Mark completed</button>
                                    </form>
                                @endif
                                <form method="post" action="{{ route('instructor.tutors.booking.update',$booking) }}">
                                    @csrf @method('PATCH') <input type="hidden" name="status" value="cancelled">
                                    <button class="btn btn-small btn-quiet">Cancel</button>
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
