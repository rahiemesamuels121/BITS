@extends('layouts.admin')
@section('title', 'My dashboard — BITS')
@section('content')
    <div class="admin-page-heading"><div><p class="admin-eyebrow">YOUR BITS ACCOUNT</p><h1>My dashboard</h1><p>Welcome, {{ $account->name }}. Choose a section to view your details.</p></div></div>
    <div class="client-overview-grid">
        <section class="admin-card client-overview-card client-overview-account"><span class="client-overview-icon" aria-hidden="true">@include('partials.admin.icon', ['name' => 'users'])</span><h2>Account details</h2><p>View your profile, account status, and membership information.</p><a class="admin-button" href="{{ route('clients.account') }}">View account details →</a></section>
        <section class="admin-card client-overview-card client-overview-contracts"><span class="client-overview-icon" aria-hidden="true">@include('partials.admin.icon', ['name' => 'ticket'])</span><h2>My contracts</h2><p>{{ number_format($contractCount) }} assigned {{ $contractCount === 1 ? 'contract' : 'contracts' }}</p><a class="admin-button" href="{{ route('clients.contracts') }}">View my contracts →</a></section>
        <section class="admin-card client-overview-card client-overview-responses"><span class="client-overview-icon" aria-hidden="true">@include('partials.admin.icon', ['name' => 'activity'])</span><h2>My responses</h2><p>{{ number_format($responseCount) }} {{ $responseCount === 1 ? 'response' : 'responses' }} linked to you</p><a class="admin-button" href="{{ route('clients.responses.index') }}">View my responses →</a></section>
    </div>
@endsection
