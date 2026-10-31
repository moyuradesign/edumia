@extends('layouts.panel')
@section('title', 'Custom staff roles')
@section('content')
<div class="panel-heading"><div><span class="section-kicker">ACCOUNT GOVERNANCE</span><h1>Staff roles</h1>
<p>Delegate specific operational permissions without granting administrator access.</p></div></div>
<section class="panel-card"><h2>Create role</h2>
<form class="stack-form" action="{{ route('admin.roles.store') }}" method="post">
@csrf
<label>Role name <input name="name" maxlength="150" required></label>
<label>Slug <input name="slug" pattern="[a-zA-Z0-9_-]+" maxlength="120" required></label>
<label>Description <textarea name="description" maxlength="500" rows="3"></textarea></label>
<p>Delegatable permissions</p>
@foreach($permissions as $key=>$description)
<label class="checkline"><input type="checkbox" name="permissions[]" value="{{ $key }}"> {{ $description }}</label>
@endforeach
<button class="btn btn-primary" type="submit">Create staff role</button>
</form></section>
<section class="panel-card"><h2>Existing custom roles</h2>
@forelse($roles as $role)
<article class="builder-section"><h3>{{ $role->name }} ({{ $role->slug }})</h3>
<p>{{ $role->description }} · {{ $role->users_count }} members</p>
<details><summary>Edit permission grants</summary>
<form class="stack-form compact-form" method="post" action="{{ route('admin.roles.update', $role) }}">
@csrf @method('PUT')
<label>Name <input name="name" maxlength="150" required value="{{ $role->name }}"></label>
<label>Slug <input name="slug" maxlength="120" required value="{{ $role->slug }}"></label>
<label>Description <textarea name="description" rows="3" maxlength="500">{{ $role->description }}</textarea></label>
@foreach($permissions as $key=>$description)
<label class="checkline"><input type="checkbox" name="permissions[]" value="{{ $key }}"
@checked($role->permissions->contains('name', $key))> {{ $description }}</label>
@endforeach
<button class="btn btn-secondary" type="submit">Save role</button>
</form></details>
<details><summary>Assign or revoke a staff role</summary>
<form class="stack-form compact-form" method="post" action="{{ route('admin.roles.members', $role) }}">
@csrf
<label>User ID <input name="user_id" type="number" required min="1"></label>
<label>Action <select name="grant"><option value="1">Grant role</option><option value="0">Revoke role</option></select></label>
<p class="subtle">Find the account ID in User Management. This does not change the user's primary role.</p>
<button class="btn btn-secondary" type="submit">Update member</button>
</form></details></article>
@empty<p>No custom roles created yet.</p>@endforelse
</section>
@endsection
