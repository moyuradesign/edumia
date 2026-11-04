@extends('layouts.panel')
@section('title', 'My team training')
@section('content')
<div class="member-dashboard">
    <div class="admin-welcome member-hero">
        <div><span class="admin-eyebrow">LEARNING / TEAMS</span>
            <h1>My team training</h1>
            <p>Manage your free training seats and respond to invitations assigned to your account.</p></div>
        <div class="admin-welcome-actions"><a class="admin-action-primary" href="{{ route('teams.index') }}">Explore team packages</a></div>
    </div>
    <section class="admin-widget">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">INVITATIONS</span><h2>Awaiting your response</h2>
            <p>Accepting a team invitation enrolls you in its free published course.</p></div></div>
        @forelse($invitations as $invitation)
            <div class="member-record-summary">
                <div><strong>{{ $invitation->purchase->organization_name }}</strong>
                    <p>{{ $invitation->purchase->package?->course?->title ?? 'Course unavailable' }}
                        · Invited by {{ $invitation->purchase->purchaser?->name ?? 'Team owner' }}</p></div>
                <form method="post" action="{{ route('team.invitations.respond',$invitation) }}">
                    @csrf @method('PATCH') <input type="hidden" name="decision" value="accept">
                    <button class="btn btn-primary btn-small">Accept seat</button>
                </form>
                <form method="post" action="{{ route('team.invitations.respond',$invitation) }}">
                    @csrf @method('PATCH') <input type="hidden" name="decision" value="decline">
                    <button class="btn btn-secondary btn-small">Decline</button>
                </form>
            </div>
        @empty
            <p class="subtle">You have no pending invitations.</p>
        @endforelse
    </section>
    <section class="admin-widget">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">OWNED WORKSPACES</span><h2>Teams you manage</h2>
            <p>Invite existing Edumia accounts by their exact email address.</p></div></div>
        @forelse($ownedTeams as $team)
            <div class="builder-section">
                <h3>{{ $team->organization_name }}</h3>
                <p>{{ $team->package?->name ?? 'Unavailable package' }} · {{ $team->package?->course?->title ?? 'Course unavailable' }}</p>
                <p><strong>{{ $team->members->whereIn('status',['active','pending'])->count() }} / {{ $team->seats_purchased }}</strong> seats reserved (accepted and pending)</p>
                <form method="post" action="{{ route('team.members.invite',$team) }}" class="form-row">
                    @csrf <label class="grow">Member email<input type="email" name="email" maxlength="190" required></label>
                    <button class="btn btn-secondary">Invite member</button>
                </form>
                <div class="table-scroll"><table class="admin-table">
                    <thead><tr><th>Member</th><th>Invitation status</th></tr></thead>
                    <tbody>@foreach($team->members as $member)
                        <tr><td>{{ $member->user?->name ?? 'Unavailable account' }}</td>
                            <td>{{ ucfirst($member->status) }}</td></tr>
                    @endforeach</tbody>
                </table></div>
            </div>
        @empty
            <div class="admin-empty"><strong>You do not manage a team yet</strong><p>Choose a free team package to create your organization workspace.</p>
                <a class="admin-action-primary" href="{{ route('teams.index') }}">Browse packages</a></div>
        @endforelse
        {{ $ownedTeams->links() }}
    </section>
    <section class="admin-widget">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">MY MEMBERSHIPS</span><h2>Teams I have joined</h2></div></div>
        @forelse($joinedTeams as $member)
            <p><strong>{{ $member->purchase?->organization_name }}</strong> ·
                {{ $member->purchase?->package?->course?->title ?? 'Course unavailable' }}
                <a href="{{ route('account.courses') }}">My enrolled courses</a></p>
        @empty
            <p class="subtle">Your accepted team invitations will appear here.</p>
        @endforelse
    </section>
</div>
@endsection
