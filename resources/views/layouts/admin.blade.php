<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="BITS systems administration demo workspace.">
        <meta name="theme-color" content="#2b2876">
        <title>@yield('title', 'Systems Admin — BITS')</title>
        @fonts('instrument-sans')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/bits-logo.svg') }}">
    </head>
    <body class="admin-body {{ request()->routeIs('forms.*', 'clients.*', 'contracts.*') ? 'forms-workspace' : '' }}">
        <a class="skip-link" href="#admin-main">Skip to dashboard</a>
        <div class="admin-layout" data-admin-dashboard>
            <aside class="admin-sidebar" id="admin-sidebar" aria-label="Dashboard navigation">
                <a class="brand admin-brand" href="{{ route('home') }}">
                    @include('partials.brand-mark')
                    <span class="brand-name">BITS<span class="brand-dot">.</span></span>
                </a>
                <div class="admin-workspace"><span class="admin-workspace-mark">@include('partials.brand-mark')</span><div><strong>BITS workspace</strong><span>Systems administration</span></div></div>
                <p class="admin-nav-caption">WORKSPACE</p>
                <nav class="admin-nav" aria-label="Administration">
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
                </nav>
                <div class="admin-sidebar-bottom">
                    @if(request()->routeIs('contracts.*'))
                    <div class="admin-demo-card">@include('partials.admin.icon', ['name' => 'shield'])<strong>Your workspace</strong><p>Attach a form to each contract and manage client assignments.</p><span>Contract management</span></div>
                    @else
                    <div class="admin-demo-card">@include('partials.admin.icon', ['name' => 'shield'])<strong>Your workspace</strong><p>{{ request()->routeIs('clients.*') ? 'Create client accounts and manage their access to BITS.' : (request()->routeIs('forms.*') ? 'Create, publish, and collect responses with your private forms workspace.' : 'Explore with sample systems and activity. No live infrastructure is connected.') }}</p><span>{{ request()->routeIs('clients.*') ? 'Client account management' : (request()->routeIs('forms.*') ? 'Your forms · Your collected data' : 'Signed-in workspace · Sample infrastructure') }}</span></div>
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
                    <div class="admin-breadcrumb">Workspace <span>/</span> <strong data-admin-breadcrumb>{{ request()->routeIs('contracts.*') ? 'Contracts' : (request()->routeIs('clients.*') ? 'Clients' : (request()->routeIs('forms.*') ? 'Form Management' : 'Overview')) }}</strong></div>
                    @unless(request()->routeIs('forms.*', 'clients.*', 'contracts.*'))
                    <label class="admin-search" data-admin-js>@include('partials.admin.icon', ['name' => 'search'])<input type="search" placeholder="Search this view…" aria-label="Search current dashboard view" data-admin-search></label>
                    <span class="admin-demo-pill"><i></i> Demo data</span>
                    @endunless
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
                <footer class="admin-page-footer"><span>BITS Systems Admin</span><span>{{ request()->routeIs('contracts.*') ? 'Contract Management · Private workspace' : (request()->routeIs('clients.*') ? 'Client Management · Private workspace' : (request()->routeIs('forms.*') ? 'Form Management · Private workspace' : 'Sample data · Additions reset on refresh')) }}</span></footer>
            </div>
        </div>
        <div class="admin-toast" role="status" data-admin-toast hidden></div>
        @stack('dialogs')
        @stack('dashboard-data')
        @if(request()->routeIs('forms.*', 'clients.*', 'contracts.*'))
            <dialog class="admin-dialog" id="fm-confirm"><h2>Confirm action</h2><p data-confirm-message></p><div class="admin-dialog-actions"><button class="admin-button" type="button" data-confirm-cancel>Cancel</button><button class="admin-button fm-danger" type="button" data-confirm-yes>Confirm</button></div></dialog>
        @endif
    </body>
</html>
