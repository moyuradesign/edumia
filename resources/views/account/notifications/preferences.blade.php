@extends('layouts.panel')
@section('title', 'Notification preferences')
@section('content')
<section class="admin-settings-workspace edumia-notifications" aria-labelledby="notification-preferences-heading">
    <div class="admin-welcome">
        <div>
            <span class="admin-eyebrow">ACCOUNT PREFERENCES</span>
            <h1 id="notification-preferences-heading">Notification preferences</h1>
            <p>Control which activity appears in your inbox and which updates can be emailed.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('account.notifications.index') }}">Back to notifications</a>
        </div>
    </div>
    <section class="admin-widget" aria-labelledby="notification-channels-title">
        <div class="admin-widget-heading">
            <div>
                <span class="admin-widget-kicker">YOUR DELIVERY CHANNELS</span>
                <h2 id="notification-channels-title">Choose what you receive</h2>
                <p>Settings apply only to your account. Previously received messages are retained.</p>
            </div>
        </div>
        @unless($mailReady)
            <p class="edumia-notification-advisory" role="status">Outbound operational email is disabled until the provider is verified. Email preferences are saved for future activation.</p>
        @endunless
        @if($errors->any())
            <div class="flash error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <form method="post" action="{{ route('account.notifications.preferences.update') }}" class="admin-settings-form">
            @csrf @method('PUT')
            <div class="edumia-notification-preferences">
                @foreach($events as $event => $label)
                    <fieldset class="edumia-notification-preference">
                        <legend>{{ $label }}</legend>
                        <p class="edumia-notification-event-key">{{ $event }}</p>
                        <div class="edumia-notification-choice-grid">
                            @foreach(['database' => 'In-app inbox', 'mail' => 'Email'] as $channel => $channelName)
                                @php($required = $event === 'account_security' && $channel === 'database')
                                <label class="edumia-notification-choice">
                                    <input type="hidden" name="preferences[{{ $event }}][{{ $channel }}]" value="0">
                                    <input type="checkbox" name="preferences[{{ $event }}][{{ $channel }}]" value="1"
                                        @checked($required || $enabled[$event][$channel])
                                        @disabled($required)>
                                    <span>
                                        <strong>{{ $channelName }}</strong>
                                        <small>{{ $required ? 'Required for account safety' : ($channel === 'mail' && !$mailReady ? 'Saved for future email activation' : 'Receive these updates') }}</small>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            </div>
            <div class="admin-settings-save">
                <p>Important security alerts always remain enabled in the account inbox.</p>
                <button class="admin-action-primary" type="submit">Save preferences</button>
            </div>
        </form>
    </section>
</section>
@endsection
