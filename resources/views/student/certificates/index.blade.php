@extends('layouts.panel')
@section('title', 'Certificates')
@section('content')
<div class="member-dashboard">
    <div class="admin-welcome member-hero">
        <div><span class="admin-eyebrow"><span class="admin-eyebrow-dot" aria-hidden="true"></span> STUDENT / ACHIEVEMENTS</span>
            <h1>My certificates</h1>
            <p>View certificates awarded for your completed courses.</p>
        </div>
    </div>

    <section class="admin-widget" aria-labelledby="certificate-list-heading">
        <div class="admin-widget-heading">
            <div><span class="admin-widget-kicker">MY ACHIEVEMENTS</span><h2 id="certificate-list-heading">Issued certificates</h2>
                <p>Only valid certificates issued to your account appear here.</p></div>
            <span class="admin-status admin-status--neutral">{{ $certificates->total() }} issued</span>
        </div>
        @if($certificates->isEmpty())
            <div class="admin-empty"><span aria-hidden="true">✧</span>
                <strong>No certificates yet</strong><p>Your issued certificates will appear here after you complete eligible courses.</p>
                <a class="admin-action-primary" href="{{ route('account.courses') }}">My courses</a>
            </div>
        @else
            <div class="table-scroll"><table class="admin-table">
                <thead><tr><th>Course</th><th>Certificate number</th><th>Issued</th><th>Action</th></tr></thead>
                <tbody>
                @foreach($certificates as $certificate)
                    <tr>
                        <td><strong>{{ $certificate->course->title }}</strong></td>
                        <td>{{ $certificate->certificate_number }}</td>
                        <td>{{ $certificate->issued_at?->format('M j, Y') ?? '—' }}</td>
                        <td><a class="admin-table-action" href="{{ route('certificate.show', $certificate) }}">View certificate</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
        {{ $certificates->links() }}
    </section>
</div>
@endsection
