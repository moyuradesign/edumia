@extends('layouts.panel')
@section('title', 'Custom staff roles')
@section('content')
<section class="admin-roles-workspace" aria-labelledby="roles-heading">
    <div class="admin-welcome">
        <div><span class="admin-eyebrow">ACCOUNT GOVERNANCE</span>
            <h1 id="roles-heading">Roles & permissions</h1>
            <p>Delegate approved operational permissions without granting full administrator access.</p>
        </div>
        <div class="admin-welcome-actions">
            <a class="admin-action-secondary" href="{{ route('admin.users.index') }}">Member directory</a>
            <a class="admin-action-primary" href="#admin-create-role">Create role</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="admin-widget" role="alert"><strong>Role update needs attention</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="admin-roles-summary">
        <div class="admin-widget"><span class="admin-widget-kicker">CUSTOM ROLES</span><strong>{{ number_format($roles->count()) }}</strong><p>Active role definitions</p></div>
        <div class="admin-widget"><span class="admin-widget-kicker">DELEGATABLE PERMISSIONS</span><strong>{{ number_format(count($permissions)) }}</strong><p>Explicitly permitted capabilities</p></div>
        <div class="admin-widget"><span class="admin-widget-kicker">ROLE ASSIGNMENTS</span><strong>{{ number_format($roles->sum('users_count')) }}</strong><p>Memberships across custom roles</p></div>
    </div>

    <section class="admin-widget" aria-labelledby="role-permission-matrix">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">LEAST PRIVILEGE</span>
            <h2 id="role-permission-matrix">Permission matrix</h2>
            <p>Only these three delegated capabilities are available; none grants access to administrator settings or role management.</p>
        </div></div>
        <div class="table-scroll"><table class="admin-table">
            <thead><tr><th scope="col">Capability</th>
                @foreach($roles as $matrixRole)<th scope="col">{{ $matrixRole->name }}</th>@endforeach
            </tr></thead>
            <tbody>
                @foreach($permissions as $permissionKey => $permissionLabel)
                    <tr><th scope="row">{{ $permissionLabel }}</th>
                        @foreach($roles as $matrixRole)
                            <td>{{ $matrixRole->permissions->contains('name', $permissionKey) ? 'Granted' : 'Not granted' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table></div>
    </section>

    <section class="admin-widget" aria-labelledby="staff-member-search-heading">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">MEMBER LOOKUP</span>
            <h2 id="staff-member-search-heading">Find an eligible staff member</h2>
            <p>Search active non-administrator accounts. Member IDs stay compatible with the existing assignment forms.</p>
        </div></div>
        <form method="get" action="{{ route('admin.roles.index') }}" class="admin-role-form">
            <label for="role-member-query">Name or email
                <input id="role-member-query" name="member_q" value="{{ $memberSearch }}" maxlength="100"
                       placeholder="Search member directory">
            </label>
            <button class="admin-action-secondary" type="submit">Find accounts</button>
        </form>
        <datalist id="role-eligible-members">
            @foreach($memberOptions as $memberOption)
                <option value="{{ $memberOption->id }}" label="{{ $memberOption->name }} — {{ $memberOption->email }}"></option>
            @endforeach
        </datalist>
        <p class="admin-field-hint">{{ $memberOptions->count() }} matching accounts shown (maximum 25). Only active non-administrators can receive delegated roles.</p>
    </section>

    <div class="admin-roles-layout">
        <section class="admin-widget admin-role-directory" aria-labelledby="roles-directory-title">
            <div class="admin-widget-heading"><div><span class="admin-widget-kicker">ACCESS POLICIES</span>
                <h2 id="roles-directory-title">Existing custom roles</h2>
                <p>Permissions are limited to the capabilities authorized by Edumia.</p></div></div>
            <div class="admin-role-cards">
                @forelse($roles as $role)
                    <article class="admin-role-card">
                        <div class="admin-role-header">
                            <div><h3>{{ $role->name }}</h3><code>{{ $role->slug }}</code></div>
                            <span class="admin-status">{{ number_format($role->users_count) }} members</span>
                        </div>
                        <p>{{ $role->description ?: 'No description provided.' }}</p>
                        <div class="admin-role-permissions">
                            <strong>Granted permissions</strong>
                            @forelse($role->permissions as $permission)
                                <span class="admin-role-permission">{{ $permissions[$permission->name] ?? $permission->name }}</span>
                            @empty
                                <span class="admin-muted">No delegated capabilities</span>
                            @endforelse
                        </div>
                        <div class="admin-role-controls">
                            <details>
                                <summary>Edit role and permissions</summary>
                                <form class="admin-role-form" method="post" action="{{ route('admin.roles.update', $role) }}">
                                    @csrf @method('PUT')
                                    <label>Name <input name="name" maxlength="150" required value="{{ $role->name }}"></label>
                                    <label>Slug <input name="slug" maxlength="120" pattern="[a-zA-Z0-9_-]+" required value="{{ $role->slug }}"></label>
                                    <label>Description <textarea name="description" maxlength="500" rows="3">{{ $role->description }}</textarea></label>
                                    <fieldset class="admin-role-checkboxes"><legend>Delegatable permissions</legend>
                                        @foreach($permissions as $key => $description)
                                            <label><input name="permissions[]" type="checkbox" value="{{ $key }}"
                                                @checked($role->permissions->contains('name', $key))> {{ $description }}</label>
                                        @endforeach
                                    </fieldset>
                                    <button class="admin-action-primary" type="submit">Save role</button>
                                </form>
                            </details>
                            <details>
                                <summary>Assign or revoke staff role</summary>
                                <form class="admin-role-form" method="post" action="{{ route('admin.roles.members', $role) }}">
                                    @csrf
                                    <label>Member account ID
                                        <input name="user_id" type="number" min="1" step="1" required
                                               list="role-eligible-members" placeholder="Select or enter account ID">
                                    </label>
                                    <label>Action
                                        <select name="grant" required>
                                            <option value="1">Grant custom role</option>
                                            <option value="0">Revoke custom role</option>
                                        </select>
                                    </label>
                                    <p class="admin-field-hint">Find member IDs in the <a href="{{ route('admin.users.index') }}">member directory</a>. Primary user roles do not change.</p>
                                    <button class="admin-action-primary" type="submit">Update membership</button>
                                </form>
                            </details>
                        </div>
                    </article>
                @empty
                    <div class="admin-empty"><span aria-hidden="true">♧</span><strong>No custom roles yet</strong>
                        <p>Create the first role using the form alongside.</p></div>
                @endforelse
            </div>
        </section>

        <section id="admin-create-role" class="admin-widget admin-role-create" aria-labelledby="role-create-title">
            <div class="admin-widget-heading"><div><span class="admin-widget-kicker">NEW DELEGATION</span>
                <h2 id="role-create-title">Create staff role</h2><p>Define a scoped role that can be assigned to active members.</p></div></div>
            <form class="admin-role-form" action="{{ route('admin.roles.store') }}" method="post">
                @csrf
                <label>Role name <input name="name" maxlength="150" required value="{{ old('name') }}" placeholder="Help Center Editor"></label>
                <label>Slug <input name="slug" maxlength="120" pattern="[a-zA-Z0-9_-]+" required value="{{ old('slug') }}" placeholder="help-center-editor"></label>
                <label>Description <textarea name="description" maxlength="500" rows="3" placeholder="Responsibilities and limits">{{ old('description') }}</textarea></label>
                <fieldset class="admin-role-checkboxes">
                    <legend>Delegatable permissions</legend>
                    @foreach($permissions as $key => $description)
                        <label><input type="checkbox" name="permissions[]" value="{{ $key }}"
                            @checked(in_array($key, old('permissions', []), true))> {{ $description }}</label>
                    @endforeach
                </fieldset>
                <p class="admin-field-hint">Only existing allowlisted permissions can be granted. This form cannot create new system permissions or administrator access.</p>
                <button class="admin-action-primary" type="submit">Create staff role</button>
            </form>
        </section>
    </div>

    <section class="admin-widget" aria-labelledby="role-audit-title">
        <div class="admin-widget-heading"><div><span class="admin-widget-kicker">ACCOUNTABILITY</span>
            <h2 id="role-audit-title">Role governance history</h2>
            <p>Changes to role definitions and memberships are recorded with the acting administrator.</p>
        </div></div>
        <div class="table-scroll"><table class="admin-table">
            <thead><tr><th scope="col">When</th><th scope="col">Activity</th>
                <th scope="col">Role</th><th scope="col">Member / permissions</th><th scope="col">Actor</th>
            </tr></thead>
            <tbody>
                @forelse($roleHistory as $event)
                    <tr>
                        <td>{{ $event->created_at?->format('M j, Y · H:i') }}</td>
                        <td>{{ str_replace(['roles.', '_'], ['', ' '], $event->action) }}</td>
                        <td>{{ data_get($event->new_values, 'slug', data_get($event->new_values, 'role_slug', 'Role #'.$event->subject_id)) }}</td>
                        <td>
                            @if(in_array($event->action, ['roles.assigned', 'roles.revoked'], true))
                                Member #{{ $event->subject_id }} · {{ data_get($event->new_values, 'assigned') ? 'Granted' : 'Revoked' }}
                            @else
                                <small>Before: {{ implode(', ', (array) data_get($event->previous_values, 'permissions', [])) ?: 'None' }}</small>
                                <small>After: {{ implode(', ', (array) data_get($event->new_values, 'permissions', [])) ?: 'None' }}</small>
                            @endif
                        </td>
                        <td>{{ $event->actor?->name ?? 'Former administrator' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="admin-empty"><strong>No role changes recorded</strong></div></td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="admin-course-pagination">{{ $roleHistory->onEachSide(1)->links() }}</div>
    </section>
</section>
@endsection
