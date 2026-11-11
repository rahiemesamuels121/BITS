<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="@yield('description', 'Access your BITS client account.')">
        <meta name="theme-color" content="#02AEB9">
        <title>@yield('title', 'Client Login — BITS')</title>
        @fonts('instrument-sans')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/bits-logo.svg') }}">
    </head>
    <body>
        <a class="skip-link" href="#main-content">Skip to content</a>
        <div class="ambient-background" aria-hidden="true"></div>

        @include('partials.header')

        <main class="auth-shell" id="main-content">
            @yield('content')
        </main>

        <footer class="auth-footer">
            <a class="text-link" href="{{ url('/') }}">
                <span aria-hidden="true">←</span>
                Back to home
            </a>
        </footer>

        @stack('dialogs')
    </body>
</html>
