<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="@yield('description', 'A calmer approach to service delivery. Explore the Rebound service desk concept.')">
        <meta name="theme-color" content="#2b2876">
        <title>@yield('title', 'Rebound')</title>
        @fonts('instrument-sans')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <a class="skip-link" href="#main-content">Skip to content</a>
        <div class="ambient-background" aria-hidden="true"></div>
        @include('partials.header')
        <div class="page-shell">
            <main class="content-shell" id="main-content">
                @if (session('status'))
                    <div class="status-banner" role="status">{{ session('status') }}</div>
                @endif
                @yield('content')
            </main>
            @include('partials.footer')
        </div>
        @stack('dialogs')
    </body>
</html>
