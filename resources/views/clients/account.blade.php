@extends('layouts.admin')
@section('title', 'Account details — BITS')
@section('content')
    <div class="admin-page-heading"><div><p class="admin-eyebrow">YOUR BITS ACCOUNT</p><h1>Account details</h1></div></div>
        <section class="admin-card client-account-card" id="account-details" aria-labelledby="client-account-title">
            <div class="client-account-profile">
                <span class="client-account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($account->name, 0, 2)) }}</span>
                <div><p class="admin-eyebrow">MY ACCOUNT</p><h2>{{ $account->name }}</h2><span class="admin-status {{ $account->is_active ? 'status-active' : '' }}">{{ $account->is_active ? 'Active' : 'Inactive' }}</span></div>
            </div>
            <div class="client-account-details">
                <h2 id="client-account-title">Account details</h2>
                <dl>
                    <div><dt>Name</dt><dd>{{ $account->name }}</dd></div>
                    <div><dt>Email address</dt><dd>{{ $account->email }}</dd></div>
                    <div><dt>Role</dt><dd>{{ $account->role?->name ?? 'Account' }}</dd></div>
                    <div><dt>Account status</dt><dd>{{ $account->is_active ? 'Active' : 'Inactive' }}</dd></div>
                    <div><dt>Member since</dt><dd>{{ $account->created_at?->format('M j, Y') ?? '—' }}</dd></div>
                    <div><dt>Email verification</dt><dd>{{ $account->email_verified_at ? 'Verified' : 'Not yet verified' }}</dd></div>
                </dl>
            </div>
        </section>

@endsection
