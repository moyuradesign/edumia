@extends('layouts.panel')
@section('title', 'Notification preferences')
@section('content')
<section class="admin-settings-workspace" aria-labelledby="notification-preferences-heading">
    <div class="admin-welcome"><div><span class="admin-eyebrow">ACCOUNT PREFERENCES</span>
        <h1 id="notification-preferences-heading">Notification preferences</h1>
        <p>Choose how you receive operational updates. Security alerts remain visible in your account.</p>
    </div><div class="admin-welcome-actions">
        <a class="admin-action-secondary" href="{{ route('account.notifications.index') }}">Back to notifications</a>
    </div></div>
    <section class="admin-widget">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">PRIVATE DELIVERY</span>
            <h2>Channels by activity</h2>
            <p>In-app messages are available now. Transactional email delivery requires verified SMTP and a working queue.</p>
        </div></div>
        @unless($mailReady)
            <p class="admin-field-hint" role="status">Outbound operational email is disabled until the provider is verified. Email preferences are saved for future activation.</p>
        @endunless
        @if($errors->any())
            <div class="flash error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <form method="post" action="{{ route('account.notifications.preferences.update') }}" class="admin-settings-form">
            @csrf @method('PUT')
            <div class="table-scroll"><table class="admin-table">
                <thead><tr><th scope="col">Activity</th><th scope="col">In-app</th><th scope="col">Email</th></tr></thead>
                <tbody>
                    @foreach($events as $event => $label)
                        <tr>
                            <th scope="row">{{ $label }}</th>
                            @foreach(['database', 'mail'] as $channel)
                                <td>
                                    <label>
                                        <input type="hidden" name="preferences[{{ $event }}][{{ $channel }}]" value="0">
                                        <input type="checkbox" name="preferences[{{ $event }}][{{ $channel }}]" value="1"
                                            @checked($enabled[$event][$channel])
                                            @disabled($event === 'account_security' && $channel === 'database')>
                                        <span class="sr-only">{{ $label }} via {{ $channel }}</span>
                                        {{ $event === 'account_security' && $channel === 'database' ? 'Required' : 'Enabled' }}
                                    </label>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <div class="admin-settings-save"><p>Changes apply only to your own account. Existing notifications are not removed.</p>
                <button class="admin-action-primary" type="submit">Save preferences</button>
            </div>
        </form>
    </section>
</section>
@endsection
