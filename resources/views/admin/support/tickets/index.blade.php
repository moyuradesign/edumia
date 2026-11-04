@extends('layouts.panel')
@section('title', 'Support ticket inbox')
@section('content')
<section class="admin-ticket-workspace" aria-labelledby="admin-ticket-heading">
    <div class="admin-welcome">
        <div>
            <span class="admin-eyebrow">SUPPORT MANAGEMENT</span>
            <h1 id="admin-ticket-heading">Support ticket inbox</h1>
            <p>Review learner requests, follow up on open cases, and keep resolutions organized.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('admin.contacts.index') }}">Contact inbox</a>
            <a class="admin-action-primary" href="{{ route('dashboard') }}">Admin overview</a>
        </div>
    </div>

    <div class="admin-stats" aria-label="Support ticket statistics">
        <div class="admin-stat"><span class="admin-stat-top"><span>All tickets</span><span class="admin-stat-icon admin-stat-icon--blue" aria-hidden="true">▤</span></span>
            <strong>{{ number_format($allTickets) }}</strong><small>Registered support requests</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>Open tickets</span><span class="admin-stat-icon admin-stat-icon--orange" aria-hidden="true">◷</span></span>
            <strong>{{ number_format($openTickets) }}</strong><small>Awaiting attention or resolution</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>Closed tickets</span><span class="admin-stat-icon admin-stat-icon--mint" aria-hidden="true">✓</span></span>
            <strong>{{ number_format($closedTickets) }}</strong><small>Marked resolved or closed</small></div>
        <div class="admin-stat"><span class="admin-stat-top"><span>New this week</span><span class="admin-stat-icon admin-stat-icon--violet" aria-hidden="true">✉</span></span>
            <strong>{{ number_format($recentTickets) }}</strong><small>Created within the last seven days</small></div>
    </div>

    <section class="admin-widget admin-ticket-list" aria-labelledby="admin-ticket-list-title">
        <div class="admin-widget-heading">
            <div><span class="admin-widget-kicker">SUPPORT REQUESTS</span><h2 id="admin-ticket-list-title">Ticket directory</h2>
                <p>Showing {{ $tickets->firstItem() ?? 0 }}–{{ $tickets->lastItem() ?? 0 }} of {{ number_format($tickets->total()) }} matching requests.</p></div>
        </div>

        <form class="admin-course-filters admin-ticket-filters" method="get" action="{{ route('admin.tickets.index') }}">
            <div>
                <label class="sr-only" for="admin-ticket-query">Search subject or requester</label>
                <input id="admin-ticket-query" type="search" name="q" maxlength="100" value="{{ $search }}"
                       placeholder="Ticket subject, member name or email">
            </div>
            <div>
                <label class="sr-only" for="admin-ticket-status">Filter support status</label>
                <select id="admin-ticket-status" name="status">
                    <option value="all" @selected($status === 'all')>All statuses</option>
                    <option value="open" @selected($status === 'open')>Open</option>
                    <option value="closed" @selected($status === 'closed')>Closed</option>
                </select>
            </div>
            <button class="admin-action-primary" type="submit">Apply filters</button>
            @if($search !== '' || $status !== 'all')<a class="admin-action-secondary" href="{{ route('admin.tickets.index') }}">Clear</a>@endif
        </form>

        <div class="table-scroll">
            <table class="admin-table admin-ticket-table">
                <thead><tr><th scope="col">Subject</th><th scope="col">Requester</th><th scope="col">Status</th><th scope="col">Last activity</th><th scope="col">Review</th></tr></thead>
                <tbody>
                @forelse($tickets as $ticket)
                    <tr>
                        <td><strong>{{ $ticket->subject }}</strong><small>Ticket #{{ $ticket->id }}</small></td>
                        <td><strong>{{ $ticket->requester?->name ?? 'Removed account' }}</strong>
                            <small>{{ $ticket->requester?->email ?? 'Requester unavailable' }}</small></td>
                        <td><span class="admin-status admin-status--{{ $ticket->status?->is_closed ? 'success' : 'neutral' }}">{{ $ticket->status?->name ?? 'Open' }}</span></td>
                        <td><time datetime="{{ $ticket->updated_at?->toAtomString() }}">{{ $ticket->updated_at?->format('M j, Y') ?? '—' }}</time></td>
                        <td><a class="admin-table-action admin-table-action--quiet" href="{{ route('admin.tickets.show', $ticket) }}">Review ticket</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="admin-empty"><span aria-hidden="true">✓</span><strong>No matching requests</strong>
                        <p>There are no tickets with these filters.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-course-pagination">{{ $tickets->onEachSide(1)->links() }}</div>
        <p class="admin-widget-footnote">Ticket conversations remain private. Resolution updates continue through the existing audited ticket-review flow.</p>
    </section>
</section>
@endsection
