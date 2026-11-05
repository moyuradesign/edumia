@extends('layouts.panel')
@section('title', 'General settings')
@section('content')
<section class="admin-settings-workspace" aria-labelledby="settings-heading">
    <div class="admin-welcome">
        <div>
            <span class="admin-eyebrow">PLATFORM SYSTEM</span>
            <h1 id="settings-heading">General settings</h1>
            <p>Manage platform identity, regional defaults, support contact details, and student registration.</p>
        </div>
    </div>

    <form class="admin-settings-form" method="post" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')
        <div class="admin-settings-grid">
            <section class="admin-widget admin-settings-card" aria-labelledby="settings-identity-title">
                <div class="admin-widget-heading"><div><span class="admin-widget-kicker">BRANDING</span>
                    <h2 id="settings-identity-title">Platform identity</h2><p>Details shown across the learner experience.</p></div></div>
                <div class="admin-form-fields">
                    <label for="settings-site-name">Platform name
                        <input id="settings-site-name" name="site_name" maxlength="120" required
                               value="{{ old('site_name', $settings['site_name']) }}">
                    </label>
                    <label for="settings-homepage-notice">Homepage notice
                        <textarea id="settings-homepage-notice" name="homepage_notice" rows="4"
                                  maxlength="500" placeholder="Optional site-wide notice">{{ old('homepage_notice', $settings['homepage_notice']) }}</textarea>
                    </label>
                    <p class="admin-field-hint">Your notice is displayed as text. Existing HTML escaping remains active.</p>
                </div>
            </section>

            <section class="admin-widget admin-settings-card" aria-labelledby="settings-region-title">
                <div class="admin-widget-heading"><div><span class="admin-widget-kicker">GLOBAL EXPERIENCE</span>
                    <h2 id="settings-region-title">Language & timezone</h2><p>Regional display preferences for international visitors.</p></div></div>
                <div class="admin-form-fields">
                    <label for="settings-locale">Default language
                        <select id="settings-locale" name="default_locale" required>
                            @foreach (\App\Helpers\Localization::LANGUAGES as $code => $name)
                                <option value="{{ $code }}" @selected(old('default_locale', $settings['default_locale']) === $code)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <p class="admin-field-hint">Visitors can select from {{ count(\App\Helpers\Localization::LANGUAGES) }} supported interface languages. Untranslated course content remains in its original language.</p>
                    <label for="settings-timezone">Display timezone (IANA)
                        <input id="settings-timezone" name="site_timezone" list="edumia-timezone-suggestions"
                               maxlength="80" value="{{ old('site_timezone', $settings['site_timezone']) }}" required
                               placeholder="UTC">
                    </label>
                    <datalist id="edumia-timezone-suggestions">
                        @foreach (['UTC', 'Asia/Phnom_Penh', 'Asia/Bangkok', 'Asia/Tokyo', 'Asia/Shanghai', 'Asia/Kolkata', 'Europe/London', 'Europe/Paris', 'America/New_York', 'America/Los_Angeles', 'Australia/Sydney'] as $zone)
                            <option value="{{ $zone }}"></option>
                        @endforeach
                    </datalist>
                    <p class="admin-field-hint">Stored payment timestamps remain unchanged; prices keep their original currencies.</p>
                </div>
            </section>

            <section class="admin-widget admin-settings-card" aria-labelledby="settings-contact-title">
                <div class="admin-widget-heading"><div><span class="admin-widget-kicker">OPERATIONS</span>
                    <h2 id="settings-contact-title">Support & registration</h2><p>Contact routing and account signup availability.</p></div></div>
                <div class="admin-form-fields">
                    <label for="settings-support-email">Support email
                        <input id="settings-support-email" name="support_email" type="email" maxlength="190"
                               value="{{ old('support_email', $settings['support_email']) }}" placeholder="help@example.com">
                    </label>
                    <label class="admin-settings-switch" for="settings-registration">
                        <input id="settings-registration" type="checkbox" name="allow_registration" value="1"
                               @checked(old('allow_registration', $settings['allow_registration']) == '1')>
                        <span><strong>Allow new student registrations</strong><small>Disable to close new signups without affecting existing accounts.</small></span>
                    </label>
                    <p class="admin-field-hint">The existing account registration guard enforces this setting on both the signup form and form submission.</p>
                </div>
            </section>
        </div>

        <div class="admin-settings-save">
            <div><strong>Ready to apply changes?</strong><p>Updates use administrator validation and Edumia's existing audited settings service.</p></div>
            <button class="admin-action-primary" type="submit">Save general settings</button>
        </div>
    </form>
</section>
@endsection
