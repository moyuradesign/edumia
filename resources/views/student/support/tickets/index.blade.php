@extends('layouts.panel')
@section('title', 'My support tickets')
@section('content')
<div class="panel-heading">
    <div><span class="section-kicker">SUPPORT</span><h1>My tickets</h1><p>Keep your support conversations organized.</p></div>
    <a class="btn btn-primary" href="{{ route('support.tickets.create') }}">New ticket</a>
</div>
<section class="panel-card">
    @if($tickets->isEmpty())<p>You have not created any tickets yet.</p>@else
        <div class="table-scroll"><table>
            <thead><tr><th>Subject</th><th>Status</th><th>Updated</th><th>Open</th></tr></thead>
            <tbody>@foreach($tickets as $ticket)<tr>
                <td>{{ $ticket->subject }}</td><td>{{ $ticket->status?->name ?? 'Open' }}</td>
                <td>{{ $ticket->updated_at?->format('M d, Y') }}</td>
                <td><a href="{{ route('support.tickets.show', $ticket) }}">Open</a></td>
            </tr>@endforeach</tbody>
        </table></div>{{ $tickets->links() }}
    @endif
</section>
@endsection
