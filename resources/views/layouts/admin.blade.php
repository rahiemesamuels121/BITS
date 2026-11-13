@php($clientWorkspace = auth()->user()->usesClientDashboard())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ $clientWorkspace ? 'Your BITS account details and contract information.' : 'BITS administration workspace.' }}">
        <meta name="theme-color" content="#02AEB9">
        <title>@yield('title', 'Systems Admin — BITS')</title>
        @fonts('instrument-sans')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/bits-logo.svg') }}">
    </head>
    <body data-private-page class="admin-body forms-workspace {{ $clientWorkspace ? 'client-portal' : 'admin-portal' }}">
        <a class="skip-link" href="#admin-main">Skip to dashboard</a>
        <div class="admin-layout">
            <aside class="admin-sidebar" id="admin-sidebar" aria-label="Dashboard navigation">
                <a class="brand admin-brand" href="{{ route('home') }}">
                    @include('partials.brand-mark')
                    <span class="brand-name">BITS<span class="brand-dot">.</span></span>
                </a>
                <div class="admin-workspace"><span class="admin-workspace-mark">@include('partials.brand-mark')</span><div><strong>{{ $clientWorkspace ? 'My BITS account' : 'BITS workspace' }}</strong><span>{{ $clientWorkspace ? 'Account & contracts' : 'Systems administration' }}</span></div></div>
                <p class="admin-nav-caption">WORKSPACE</p>
                <nav class="admin-nav" aria-label="{{ $clientWorkspace ? 'My account' : 'Administration' }}">
                    @if($clientWorkspace)
                        <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) class="is-active" aria-current="page" @endif>@include('partials.admin.icon', ['name' => 'grid'])<span>My dashboard</span></a>
                        <a href="{{ route('clients.account') }}" @if(request()->routeIs('clients.account')) class="is-active" aria-current="page" @endif>@include('partials.admin.icon', ['name' => 'users'])<span>Account details</span></a>
                        <a href="{{ route('clients.contracts') }}" @if(request()->routeIs('clients.contracts')) class="is-active" aria-current="page" @endif>@include('partials.admin.icon', ['name' => 'ticket'])<span>My contracts</span></a>
                        <a href="{{ route('clients.responses.index') }}" @if(request()->routeIs('clients.responses.*')) class="is-active" aria-current="page" @endif>@include('partials.admin.icon', ['name' => 'activity'])<span>My responses</span></a>
                    @else
                    <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) class="is-active" aria-current="page" @endif>@include('partials.admin.icon', ['name' => 'activity'])<span>Overview</span></a>
                    <a href="{{ route('forms.index') }}" @if(request()->routeIs('forms.*')) class="is-active" aria-current="page" @endif>
                        @include('partials.admin.icon', ['name' => 'ticket'])<span>Form Manager</span>
                    </a>
                    @can('manage-clients')
                        <a href="{{ route('clients.index') }}" @if(request()->routeIs('clients.*')) class="is-active" aria-current="page" @endif>
                            @include('partials.admin.icon', ['name' => 'users'])<span>Clients</span>
                        </a>
                    @endcan
                    @can('manage-contracts')
                        <a href="{{ route('contracts.index') }}" @if(request()->routeIs('contracts.*')) class="is-active" aria-current="page" @endif>
                            @include('partials.admin.icon', ['name' => 'ticket'])<span>Contracts</span>
                        </a>
                    @endcan
                    @endif
                </nav>
                <div class="admin-sidebar-bottom">
                    @if($clientWorkspace)
                    <div class="admin-demo-card">@include('partials.admin.icon', ['name' => 'shield'])<strong>Your account</strong><p>Keep your account details and assigned agreements in view.</p><span>Personal account · Private information</span></div>
                    @elseif(request()->routeIs('contracts.*'))
                    <div class="admin-demo-card">@include('partials.admin.icon', ['name' => 'shield'])<strong>Your workspace</strong><p>Attach a form to each contract and manage client assignments.</p><span>Contract management</span></div>
                    @else
                    <div class="admin-demo-card">@include('partials.admin.icon', ['name' => 'shield'])<strong>Your workspace</strong><p>{{ request()->routeIs('clients.*') ? 'Create client accounts and manage their access to BITS.' : (request()->routeIs('forms.*') ? 'Create, publish, and collect responses with your private forms workspace.' : 'Manage clients, contracts, and published forms from one workspace.') }}</p><span>{{ request()->routeIs('clients.*') ? 'Client account management' : (request()->routeIs('forms.*') ? 'Your forms · Your collected data' : 'Live workspace totals') }}</span></div>
                    @endif
                    <a class="admin-website-link" href="{{ route('home') }}">@include('partials.admin.icon', ['name' => 'back']) Back to website</a>
                    <form class="admin-signout" action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="admin-website-link" type="submit">@include('partials.admin.icon', ['name' => 'logout']) Sign out</button>
                    </form>
                </div>
            </aside>
            <div class="admin-main-shell">
                <header class="admin-topbar">
                    <button class="admin-icon-button admin-mobile-toggle" type="button" aria-label="Toggle dashboard navigation" aria-expanded="false" aria-controls="admin-sidebar" data-admin-menu data-admin-js>@include('partials.admin.icon', ['name' => 'menu'])</button>
                    <div class="admin-breadcrumb">{{ $clientWorkspace ? 'My account' : 'Workspace' }} <span>/</span> <strong data-admin-breadcrumb>{{ request()->routeIs('clients.responses.*') ? (request()->routeIs('clients.responses.index') ? 'My responses' : 'Response details') : (request()->routeIs('clients.account') ? 'Account details' : (request()->routeIs('clients.contracts', 'contracts.*') ? 'Contracts' : (request()->routeIs('clients.*') ? 'Clients' : (request()->routeIs('forms.*') ? 'Form Management' : ($clientWorkspace ? 'My dashboard' : 'Overview'))))) }}</strong></div>
                    <div class="admin-profile"><span>{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}</span><div><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role?->name ?? 'Account' }}</small></div></div>
                </header>
                <main class="admin-main" id="admin-main">
                    @if (session('status'))
                        <div class="admin-login-status" role="status">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="fm-errors" role="alert"><strong>Please check the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif
                    @yield('content')
                </main>
                <footer class="admin-page-footer"><span>{{ $clientWorkspace ? 'BITS Client Portal' : 'BITS Systems Admin' }}</span><span>{{ $clientWorkspace ? 'Account details · My contracts' : (request()->routeIs('contracts.*') ? 'Contract Management · Private workspace' : (request()->routeIs('clients.*') ? 'Client Management · Private workspace' : (request()->routeIs('forms.*') ? 'Form Management · Private workspace' : 'Workspace overview · Live data'))) }}</span></footer>
            </div>
        </div>
        @stack('dialogs')
        @stack('dashboard-data')
        @if(request()->routeIs('forms.*', 'clients.*', 'contracts.*'))
            <dialog class="admin-dialog" id="fm-confirm"><h2>Confirm action</h2><p data-confirm-message></p><div class="admin-dialog-actions"><button class="admin-button" type="button" data-confirm-cancel>Cancel</button><button class="admin-button fm-danger" type="button" data-confirm-yes>Confirm</button></div></dialog>
        @endif
    </body>
</html>
