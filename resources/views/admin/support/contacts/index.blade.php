@extends('layouts.panel')
@section('title', 'Contact inbox')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">SUPPORT & RELATIONSHIPS</span><h1>Contact inbox</h1><p>Review public inquiries securely.</p></div></div>
<section class="panel-card">
    @if($contacts->isEmpty())<p>No messages received yet.</p>@else
        <div class="table-scroll"><table><thead><tr><th>From</th><th>Subject</th><th>Received</th><th>Status</th><th>Open</th></tr></thead>
        <tbody>@foreach($contacts as $contact)<tr>
            <td>{{ $contact->name }}</td><td>{{ $contact->subject ?? 'General inquiry' }}</td>
            <td>{{ $contact->created_at?->format('M d, Y') }}</td>
            <td>{{ ucfirst($contact->status) }}</td>
            <td><a href="{{ route('admin.contacts.show', $contact) }}">View</a></td>
        </tr>@endforeach</tbody></table></div>
        {{ $contacts->links() }}
    @endif
</section>
@endsection
