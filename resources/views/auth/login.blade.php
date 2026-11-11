@extends('layouts.guest')

@section('title', 'Client Login — BITS')
@section('description', 'Sign in to your BITS client account.')

@section('content')
    <section class="auth-card" aria-labelledby="login-title">
        <div class="auth-brand" aria-hidden="true">
            @include('partials.brand-mark')
        </div>

        @auth
            <p class="eyebrow">YOUR BITS ACCOUNT</p>
            <h1 id="login-title">You’re signed in.</h1>
            <p class="auth-description">Welcome back, {{ auth()->user()->name }}.</p>

            <div class="auth-account">
                <span class="status-dot" aria-hidden="true"></span>
                <span>{{ auth()->user()->email }}</span>
            </div>

            <a class="button button-primary auth-submit" href="{{ route('home') }}">Return to home <span aria-hidden="true">↗</span></a>
            <form action="{{ route('logout') }}" method="POST" class="auth-signout">
                @csrf
                <button class="auth-text-button" type="submit">Sign out</button>
            </form>
        @else
            <p class="eyebrow">WELCOME BACK</p>
            <h1 id="login-title">Client Login<span class="brand-dot">.</span></h1>
            <p class="auth-description">Sign in to your BITS account.</p>

            @if ($errors->any())
                <div class="auth-error-summary" role="alert">
                    Please check your details and try again.
                </div>
            @endif

            <form class="auth-form" action="{{ route('login.store') }}" method="POST">
                @csrf

                <div class="auth-field">
                    <label for="email">Email address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        placeholder="you@company.com"
                        autocomplete="username"
                        inputmode="email"
                        autocapitalize="none"
                        spellcheck="false"
                        maxlength="254"
                        required
                        autofocus
                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                    >
                    @error('email')
                        <p class="auth-field-error" id="email-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="auth-field">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                    >
                    @error('password')
                        <p class="auth-field-error" id="password-error">{{ $message }}</p>
                    @enderror
                </div>

                <label class="auth-remember" for="remember">
                    <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
                    <span>Keep me signed in</span>
                </label>

                <button class="button button-primary auth-submit" type="submit">
                    Sign in <span class="button-arrow" aria-hidden="true">→</span>
                </button>
            </form>

            <p class="auth-help">Need access? Contact your account administrator.</p>
        @endauth
    </section>
@endsection
