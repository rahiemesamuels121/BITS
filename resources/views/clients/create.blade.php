@extends('layouts.admin')
@section('title', 'Create Client — BITS')
@section('content')
    <a class="fm-text-link" href="{{ route('clients.index') }}">← Back to clients</a>
    <div class="fm-create-card">
        <p class="admin-eyebrow">CLIENT ACCOUNTS</p><h1>Create a client</h1>
        <p>Set up a client account and choose whether it can sign in.</p>
        <form method="POST" action="{{ route('clients.store') }}" class="fm-stack">
            @csrf
            <label>Client name<input name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name"></label>
            <label>Email address<input type="email" name="email" value="{{ old('email') }}" required maxlength="254" autocomplete="off"></label>
            <label>Password<input type="password" name="password" required minlength="12" maxlength="72" autocomplete="new-password"><span class="fm-muted">Use 12–72 characters.</span></label>
            <label>Confirm password<input type="password" name="password_confirmation" required minlength="12" maxlength="72" autocomplete="new-password"></label>
            <label>Account status<select name="is_active"><option value="1" @selected(old('is_active', '1') == '1')>Active — can sign in</option><option value="0" @selected(old('is_active', '1') == '0')>Inactive — cannot sign in</option></select></label>
            <div class="fm-create-actions"><a class="admin-button" href="{{ route('clients.index') }}">Cancel</a><button class="admin-button admin-button-primary" type="submit">Create client</button></div>
        </form>
    </div>
@endsection
