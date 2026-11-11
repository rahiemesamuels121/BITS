<div class="utility-bar">
    <span>Better flow. Better service.</span>
    <a href="{{ url('/#what-we-do') }}">Explore BITS <span aria-hidden="true">→</span></a>
</div>
<header class="top-nav">
    <a class="brand" href="{{ url('/') }}" aria-label="BITS home">
        @include('partials.brand-mark')
        <span class="brand-name">Business Innovation & Technology Solutions<span class="brand-dot">.</span></span>
    </a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-navigation" data-menu-toggle>
        <span class="sr-only">Toggle navigation</span>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" /></svg>
    </button>
    <nav class="main-nav" id="site-navigation" aria-label="Main navigation" data-open="false">
        <a class="nav-item {{ request()->is('/') ? 'active' : '' }}" href="{{ url('/') }}" @if(request()->is('/')) aria-current="page" @endif>Home</a>
        <a class="nav-item" href="{{ url('/#about') }}">About Us</a>
        <a class="nav-item" href="{{ url('/#what-we-do') }}">What We Do</a>
    </nav>
    <a class="cta-button header-cta" href="{{ route('login') }}">Client Login <span aria-hidden="true">↗</span></a>
</header>
