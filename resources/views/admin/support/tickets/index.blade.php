@extends('layouts.panel')
@section('title', 'Support ticket inbox')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">SUPPORT MANAGEMENT</span><h1>Support ticket inbox</h1><p>Review learner questions and assign a resolution status.</p></div></div>
<section class="panel-card">
    @if($tickets->isEmpty())<p>No support tickets yet.</p>@else
        <div class="table-scroll"><table>
            <thead><tr><th>Ticket</th><th>Requester</th><th>Status</th><th>Updated</th><th>Open</th></tr></thead>
            <tbody>@foreach($tickets as $ticket)<tr>
                <td>{{ $ticket->subject }}</td><td>{{ $ticket->requester?->name }}</td>
                <td>{{ $ticket->status?->name ?? 'Open' }}</td>
                <td>{{ $ticket->updated_at?->format('M d, Y') }}</td>
                <td><a href="{{ route('admin.tickets.show', $ticket) }}">Review</a></td>
            </tr>@endforeach</tbody>
        </table></div>{{ $tickets->links() }}
    @endif
</section>
@endsection
