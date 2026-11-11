@extends('layouts.app')

@section('title', 'BITS — About us and what we do')
@section('description', 'Learn about BITS and how we help service teams deliver a clearer, more connected support experience.')

@section('content')
    <section class="home-image-section" aria-labelledby="home-image-title">
        <img
            class="home-image"
            src="{{ asset('images/bits-service-team.png') }}"
            alt="The BITS service team working together"
            fetchpriority="high"
        >
        <div class="home-image-overlay">
            <div class="home-image-copy">
                <p class="eyebrow">BETTER FLOW. BETTER SERVICE.</p>
                <h1 id="home-image-title">People first.<br>Service that moves.</h1>
            </div>
        </div>
    </section>

    <section class="about-section section-padding" id="about" aria-labelledby="about-title">
        <div class="about-symbol" aria-hidden="true">
            @include('partials.brand-mark')
            <span class="orbit orbit-one"></span>
            <span class="orbit orbit-two"></span>
            <span class="orbit-dot"></span>
        </div>
        <div class="about-copy">
            <p class="eyebrow">ABOUT US</p>
            <h1 id="about-title">Behind every ticket,<br>there’s a person.</h1>
            <p>A teammate trying to get started. A customer who needs a hand. An engineer making it happen.</p>
            <p>BITS is a service desk concept shaped around those moments. Because better workflows should mean better days for the people using them.</p>
        </div>
    </section>

    <section class="vision-mission-section section-padding" aria-labelledby="vision-mission-title">
        <div class="vision-mission-heading">
            <p class="eyebrow">OUR PURPOSE</p>
            <h2 id="vision-mission-title">Our vision and mission</h2>
        </div>
        <div class="vision-mission-grid">
            <article class="purpose-card" aria-labelledby="vision-title">
                <p class="purpose-label">VISION</p>
                <h3 id="vision-title">Our vision</h3>
                <p>Your vision statement goes here.</p>
            </article>
            <article class="purpose-card purpose-card-mission" aria-labelledby="mission-title">
                <p class="purpose-label">MISSION</p>
                <h3 id="mission-title">Our mission</h3>
                <p>Your mission statement goes here.</p>
            </article>
        </div>
    </section>

    <section class="solutions-section section-padding" id="what-we-do" aria-labelledby="what-we-do-title">
        <div class="section-heading">
            <div>
                <p class="eyebrow">WHAT WE DO</p>
                <h2 id="what-we-do-title">We make service work flow.</h2>
            </div>
            <p>We bring requests, conversations, and next steps into one clearer picture for your service team.</p>
        </div>
        <div class="feature-grid">
            <article class="feature-card">
                <span class="feature-number">01 / CAPTURE</span>
                <div class="feature-illustration capture-illustration" aria-hidden="true">
                    <span class="channel-bubble">@</span>
                    <span class="channel-bubble">☏</span>
                    <span class="channel-bubble">···</span>
                    <span class="channel-line"></span>
                    <span class="capture-destination">↘ &nbsp; One clear starting point</span>
                </div>
                <h3>Start with the full picture.</h3>
                <p>Give every request a place to land. Keep the context close, so the next conversation starts a step ahead.</p>
            </article>
            <article class="feature-card">
                <span class="feature-number">02 / CONNECT</span>
                <div class="feature-illustration connect-illustration" aria-hidden="true">
                    <span class="route-node">Request</span>
                    <span class="route-connector">→</span>
                    <span class="route-node route-node-active">Right person <span>✓</span></span>
                    <span class="route-caption">A clear owner. A clear next step.</span>
                </div>
                <h3>Keep the work moving.</h3>
                <p>Make ownership easy to see. Help the right people pick up the right work, without another round of back-and-forth.</p>
            </article>
            <article class="feature-card">
                <span class="feature-number">03 / RESOLVE</span>
                <div class="feature-illustration resolve-illustration" aria-hidden="true">
                    <span class="resolve-ring">✓</span>
                    <span class="resolve-caption">All the way to done.</span>
                    <span class="resolve-trail"></span>
                </div>
                <h3>Close the loop, beautifully.</h3>
                <p>Bring the work, updates, and outcome together so everyone knows when a request is resolved.</p>
            </article>
        </div>
    </section>
@endsection
