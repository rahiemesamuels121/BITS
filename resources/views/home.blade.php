@extends('layouts.app')

@section('title', 'Rebound — Better flow. Better service.')
@section('description', 'Discover a fresh approach to MSP service delivery. Explore a calmer, more connected service desk with Rebound.')

@section('content')
    <section class="hero" aria-labelledby="hero-title">
        <img class="hero-image" src="{{ asset('images/rebound-service-team.png') }}" alt="" fetchpriority="high">
        <p class="eyebrow hero-eyebrow">
            <span class="status-dot"></span>
            A NEW RHYTHM FOR YOUR SERVICE DESK
        </p>
        <h1 id="hero-title">Less busywork.<br><span>More moving forward.</span></h1>
        <p class="hero-description">Bring your requests, conversations, and next steps into one clearer picture. Give your team the space to do what they do best: deliver exceptional service.</p>
        <div class="hero-actions">
            <a class="button button-primary" href="#platform">
                Explore the platform <span class="button-arrow" aria-hidden="true">↗</span>
            </a>
            <button class="button button-secondary" type="button" data-open-tour>
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="m5 3 8 5-8 5V3Z" /></svg>
                See how it works
            </button>
        </div>
        <p class="hero-note">Built around people. Designed for momentum.</p>
    </section>

    <section class="quick-actions" aria-labelledby="quick-actions-title">
        <div class="quick-actions-heading">
            <h2 id="quick-actions-title">Your next step, made simple.</h2>
            <p>A clearer workflow is just around the corner.</p>
        </div>
        <div class="quick-action-links">
            <a href="#platform">Platform overview <span aria-hidden="true">↗</span></a>
            <a href="#solutions">Explore solutions <span aria-hidden="true">↗</span></a>
            <a href="{{ route('login') }}">Client Login <span aria-hidden="true">↗</span></a>
            <button class="button button-primary" type="button" data-open-tour>Take a quick tour <span aria-hidden="true">→</span></button>
        </div>
    </section>

    <section class="platform-section" id="platform" aria-labelledby="platform-title">
        <div class="platform-preview">
            <div class="preview-topline">
                <span><span class="status-dot"></span> THE REBOUND EXPERIENCE</span>
                <span class="preview-label">ILLUSTRATIVE WORKFLOW <span aria-hidden="true">↗</span></span>
            </div>
            <div class="preview-stage">
                <div class="preview-copy">
                    <div class="preview-brand">
                        @include('partials.brand-mark')
                        <span>Rebound<span class="brand-dot">.</span></span>
                    </div>
                    <h2 id="platform-title">A little less friction.<br>A lot more flow.</h2>
                    <p>Your next great service experience<br>starts with a clearer view.</p>
                    <button class="tour-trigger" type="button" data-open-tour>
                        <span class="play-circle" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><path d="m7 4 9 6-9 6V4Z" /></svg>
                        </span>
                        <span>Take a quick tour<span class="tour-subtitle">Three steps. One connected workflow.</span></span>
                    </button>
                </div>
                <div class="dashboard-visual" role="img" aria-label="Example service desk showing 12 open requests, 4 in progress, and 8 resolved today">
                    <div class="dashboard-toolbar">
                        <div class="window-dots"><i></i><i></i><i></i></div>
                        <span>Rebound / Workspace</span>
                        <span class="avatar">JD</span>
                    </div>
                    <div class="dashboard-body">
                        <div class="dashboard-sidebar" aria-hidden="true">
                            <span class="sidebar-icon selected">▦</span>
                            <span class="sidebar-icon">☷</span>
                            <span class="sidebar-icon">◷</span>
                            <span class="sidebar-icon">⚙</span>
                        </div>
                        <div class="dashboard-content">
                            <div class="dashboard-heading">
                                <div>
                                    <span class="mini-label">YOUR WORKSPACE</span>
                                    <h3>Good morning, Jamie <span aria-hidden="true">☀</span></h3>
                                </div>
                                <span class="workspace-status"><span class="status-dot"></span> All in view</span>
                            </div>
                            <div class="dashboard-metrics">
                                <div><span>Open requests</span><strong>12 <small>in your queue</small></strong></div>
                                <div><span>In progress</span><strong>04 <small>moving forward</small></strong></div>
                                <div><span>Resolved today</span><strong class="green-text">08 <small>nicely done</small></strong></div>
                            </div>
                            <div class="queue-heading">
                                <span>Service queue</span>
                                <span>All requests <span aria-hidden="true">⌄</span></span>
                            </div>
                            <div class="ticket-row">
                                <span class="ticket-icon ticket-purple">↗</span>
                                <div><strong>New employee onboarding</strong><span>People &amp; access · #RB-2048</span></div>
                                <span class="ticket-badge">In progress</span>
                                <span class="ticket-avatar">AL</span>
                            </div>
                            <div class="ticket-row">
                                <span class="ticket-icon ticket-blue">⌁</span>
                                <div><strong>Connect to the office network</strong><span>Connectivity · #RB-2047</span></div>
                                <span class="ticket-badge badge-new">New</span>
                                <span class="ticket-avatar">JD</span>
                            </div>
                            <div class="ticket-row">
                                <span class="ticket-icon ticket-green">✓</span>
                                <div><strong>Workspace access restored</strong><span>Account support · #RB-2046</span></div>
                                <span class="ticket-badge badge-done">Resolved</span>
                                <span class="ticket-avatar">MK</span>
                            </div>
                        </div>
                    </div>
                    <div class="activity-toast">
                        <span class="toast-check" aria-hidden="true">✓</span>
                        <div><strong>One less thing on your list.</strong><span>Request resolved. Everyone in the loop.</span></div>
                        <span class="toast-time">Just now</span>
                    </div>
                </div>
            </div>
            <div class="preview-bottomline">
                <span><span class="tiny-spark" aria-hidden="true">✳</span> Less noise. More clarity.</span>
                <span>Make room for the work that matters.</span>
            </div>
        </div>
        <div class="principles-strip" aria-label="Platform principles">
            <span>One connected workflow</span><i aria-hidden="true"></i>
            <span>People at the center</span><i aria-hidden="true"></i>
            <span>Clarity at every step</span><i aria-hidden="true"></i>
            <span>Room to grow</span>
        </div>
    </section>

    <section class="solutions-section section-padding" id="solutions" aria-labelledby="solutions-title">
        <div class="section-heading">
            <div>
                <p class="eyebrow">LESS FRICTION. AT EVERY STEP.</p>
                <h2 id="solutions-title">Great service is a team effort.<br><span>Give it a better flow.</span></h2>
            </div>
            <p>From the first request to the final follow-up, bring a little more intention to every interaction.</p>
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
                <p>A resolution is better when everyone knows. Bring the work, the updates, and the outcome together.</p>
            </article>
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
            <p class="eyebrow">A LITTLE MORE HUMAN</p>
            <h2 id="about-title">Behind every ticket,<br>there’s a person.</h2>
            <p>A teammate trying to get started. A customer who needs a hand. An engineer making it happen.</p>
            <p>Rebound is a service desk concept shaped around those moments. Because better workflows should mean better days for the people using them.</p>
            <a class="text-link" href="#get-started">Find your next step <span aria-hidden="true">↗</span></a>
        </div>
    </section>

    <section class="get-started-section" id="get-started" aria-labelledby="get-started-title">
        <div class="cta-orbit" aria-hidden="true"></div>
        <p class="eyebrow"><span class="status-dot"></span> YOUR NEXT CHAPTER</p>
        <h2 id="get-started-title">Make space for<br><span>better service.</span></h2>
        <p>Take a closer look at what a calmer service desk could feel like.</p>
        <button class="button button-primary" type="button" data-open-tour>
            Explore the Rebound experience <span class="button-arrow" aria-hidden="true">↗</span>
        </button>
    </section>
@endsection

@push('dialogs')
    <dialog class="tour-dialog" id="platform-tour" aria-labelledby="tour-title">
        <div class="dialog-heading">
            <span class="eyebrow">THE REBOUND EXPERIENCE</span>
            <button class="dialog-close" type="button" data-close-dialog aria-label="Close product tour">×</button>
        </div>
        <h2 id="tour-title">From request to resolution.</h2>
        <p class="dialog-intro">An illustrative look at a more connected service workflow.</p>
        <div class="tour-tabs" role="tablist" aria-label="Workflow steps">
            <button id="tour-tab-0" type="button" role="tab" aria-selected="true" aria-controls="tour-panel-0" data-tour-step="0">01 <span>Capture</span></button>
            <button id="tour-tab-1" type="button" role="tab" aria-selected="false" aria-controls="tour-panel-1" tabindex="-1" data-tour-step="1">02 <span>Connect</span></button>
            <button id="tour-tab-2" type="button" role="tab" aria-selected="false" aria-controls="tour-panel-2" tabindex="-1" data-tour-step="2">03 <span>Resolve</span></button>
        </div>
        <div class="tour-panel" id="tour-panel-0" role="tabpanel" aria-labelledby="tour-tab-0" tabindex="0" data-tour-panel="0">
            <span class="tour-panel-icon" aria-hidden="true">↘</span>
            <h3>Every request starts with context.</h3>
            <p>Jamie needs access to a new workspace. Their request brings the details together in one place, ready for the service team.</p>
            <div class="sample-request">
                <span class="mini-label">EXAMPLE REQUEST · #RB-2048</span>
                <strong>Access to the design workspace</strong>
                <p>“I’m joining the project on Monday. Could you help me get set up?”</p>
                <span class="ticket-badge badge-new">New request</span>
            </div>
        </div>
        <div class="tour-panel" id="tour-panel-1" role="tabpanel" aria-labelledby="tour-tab-1" tabindex="0" data-tour-panel="1" hidden>
            <span class="tour-panel-icon" aria-hidden="true">⇄</span>
            <h3>A clear owner. A clear next step.</h3>
            <p>Alex picks up the request with the context already attached. Jamie can see who’s helping and what happens next.</p>
            <div class="sample-request">
                <span class="mini-label">EXAMPLE ASSIGNMENT · #RB-2048</span>
                <strong><span class="ticket-avatar">AL</span> Alex is on it.</strong>
                <p>Review workspace permissions → Confirm access → Update Jamie</p>
                <span class="ticket-badge">In progress</span>
            </div>
        </div>
        <div class="tour-panel" id="tour-panel-2" role="tabpanel" aria-labelledby="tour-tab-2" tabindex="0" data-tour-panel="2" hidden>
            <span class="tour-panel-icon" aria-hidden="true">✓</span>
            <h3>Close the ticket. Keep the connection.</h3>
            <p>Access is ready, Jamie gets an update, and the resolution stays with the request. A small task, with every loose end tied up.</p>
            <div class="sample-request">
                <span class="mini-label">EXAMPLE RESOLUTION · #RB-2048</span>
                <strong>You’re ready for Monday.</strong>
                <p>“Your workspace access is set up. You’ll find the invitation in your inbox.”</p>
                <span class="ticket-badge badge-done">Resolved</span>
            </div>
        </div>
        <div class="dialog-footer">
            <span>Example workflow · No live data</span>
            <button class="button button-primary" type="button" data-tour-next>Next step <span aria-hidden="true">→</span></button>
        </div>
    </dialog>
@endpush
