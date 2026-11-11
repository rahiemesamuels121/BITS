@extends('layouts.admin')
@section('title', 'Edit Client — BITS')
@section('content')
    <a class="fm-text-link" href="{{ route('clients.index') }}">← Back to clients</a>
    <div class="fm-create-card">
        <p class="admin-eyebrow">CLIENT ACCOUNTS</p><h1>Edit client</h1>
        <p>Update account details. Leave the password fields blank to keep the current password.</p>
        <form method="POST" action="{{ route('clients.update', $client) }}" class="fm-stack">
            @csrf @method('PUT')
            <label>Client name<input name="name" value="{{ old('name', $client->name) }}" required maxlength="255" autocomplete="name"></label>
            <label>Email address<input type="email" name="email" value="{{ old('email', $client->email) }}" required maxlength="254" autocomplete="off"></label>
            <label>New password (optional)<input type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password"><span class="fm-muted">Use 12–72 characters. Changing email or password signs the client out.</span></label>
            <label>Confirm new password<input type="password" name="password_confirmation" minlength="12" maxlength="72" autocomplete="new-password"></label>
            <div class="fm-create-actions"><a class="admin-button" href="{{ route('clients.index') }}">Cancel</a><button class="admin-button admin-button-primary" type="submit">Save changes</button></div>
        </form>
    </div>
@endsection
