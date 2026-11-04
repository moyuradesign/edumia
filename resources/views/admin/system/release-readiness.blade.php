@extends('layouts.panel')
@section('title', 'Release readiness')
@section('content')
<section class="admin-readiness-workspace" data-admin-readiness aria-labelledby="admin-readiness-heading">
    <div class="admin-welcome">
        <div>
            <span class="admin-eyebrow">OPERATIONS / ACCEPTANCE</span>
            <h1 id="admin-readiness-heading">Release readiness</h1>
            <p>Review local runtime signals and the independent evidence still required before a customer release.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('admin.settings.edit') }}">General settings</a>
            <a class="admin-action-secondary" href="{{ route('dashboard') }}">Back to overview</a>
        </div>
    </div>

    <div class="admin-readiness-overview" role="group" aria-label="Release acceptance status">
        <div class="admin-readiness-summary">
            <span class="admin-widget-kicker">LOCAL RUNTIME ONLY</span>
            <strong>{{ $localPassed }} / {{ count($localChecks) }}</strong>
            <span>Local checks passing</span>
            <small>These checks describe this installation, not the CI repository or independent release acceptance.</small>
        </div>
        <div class="admin-readiness-summary">
            <span class="admin-widget-kicker">EXTERNAL ACCEPTANCE</span>
            <strong>{{ count($externalGates) }}</strong>
            <span>Gates still open</span>
            <small>Real hosting, physical devices, business modules and provider acceptance need separate evidence.</small>
        </div>
        <div class="admin-readiness-summary">
            <span class="admin-widget-kicker">RELEASE DECISION</span>
            <strong class="admin-readiness-pending">Not approved</strong>
            <span>Customer release blocked</span>
            <small>No local check or simulated browser run can automatically approve a release.</small>
        </div>
    </div>

    <section class="admin-widget" aria-labelledby="admin-local-checks-heading">
        <div class="admin-widget-heading">
            <div>
                <span class="admin-widget-kicker">READ-ONLY DIAGNOSTICS</span>
                <h2 id="admin-local-checks-heading">Installation checks</h2>
                <p>No migrations, payment requests, configuration updates or database writes are performed here.</p>
            </div>
        </div>
        <div class="admin-readiness-list">
            @foreach($localChecks as $check)
                <div class="admin-readiness-check" data-readiness-check="{{ $check['id'] }}">
                    <div><strong>{{ $check['title'] }}</strong><p>{{ $check['detail'] }}</p></div>
                    <span class="admin-readiness-state {{ $check['state'] === 'PASS' ? 'is-pass' : 'is-pending' }}">{{ $check['state'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="admin-widget" aria-labelledby="admin-external-heading">
        <div class="admin-widget-heading">
            <div>
                <span class="admin-widget-kicker">INDEPENDENT VALIDATION</span>
                <h2 id="admin-external-heading">External release gates</h2>
                <p>These cannot be closed from the dashboard. Review matching evidence before sign-off.</p>
            </div>
        </div>
        <div class="admin-readiness-gate-grid">
            @foreach($externalGates as $gate)
                <article class="admin-readiness-gate" data-readiness-gate="{{ $gate['id'] }}">
                    <div class="admin-readiness-gate-head">
                        <strong>{{ $gate['title'] }}</strong>
                        <span class="admin-readiness-state is-pending">{{ $gate['state'] }}</span>
                    </div>
                    <p>{{ $gate['detail'] }}</p>
                    <small>Required evidence: {{ $gate['evidence'] }}</small>
                </article>
            @endforeach
        </div>
    </section>

    <section class="admin-widget" aria-labelledby="admin-workstreams-heading">
        <div class="admin-widget-heading">
            <div>
                <span class="admin-widget-kicker">SOURCE AUDIT SNAPSHOT</span>
                <h2 id="admin-workstreams-heading">Remaining module acceptance</h2>
                <p>Development workstreams reviewed on November 4, 2026, not live feature telemetry.</p>
            </div>
        </div>
        <div class="table-scroll">
            <table class="admin-table admin-readiness-table">
                <thead><tr><th>Workstream</th><th>Status</th><th>Still required</th><th>Workspace</th></tr></thead>
                <tbody>
                    @foreach($moduleWorkstreams as $module)
                        <tr>
                            <td><strong>{{ $module['title'] }}</strong></td>
                            <td><span class="admin-readiness-state is-pending">{{ $module['state'] }}</span></td>
                            <td>{{ $module['remaining'] }}</td>
                            <td>
                                @if($module['route'])
                                    <a class="admin-table-action admin-table-action--quiet" href="{{ route($module['route']) }}">Open module</a>
                                @else
                                    <span class="admin-muted">Not available</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <p class="admin-readiness-footnote">
        Use <code>docs/shared-host-acceptance.md</code>, <code>docs/device-acceptance.md</code>,
        <code>docs/feature-completion-audit.md</code> and <code>docs/release-readiness.md</code>
        from the matching source release. Keep real-money payments disabled until provider and legal acceptance is complete.
    </p>
</section>
@endsection
