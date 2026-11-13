@extends('layouts.app')

@section('title', 'BITS — Caribbean Business Transformation, Research & Technology')
@section('description', 'BITS delivers research, strategy, innovation and technology solutions that create measurable business and organisational results across the Caribbean.')

@section('content')
    <section class="home-image-section" aria-labelledby="home-image-title">
        <img class="home-image" src="{{ asset('images/bits-service-team.png') }}" alt="The BITS team collaborating" fetchpriority="high">
        <div class="home-image-overlay"><div class="home-image-copy">
            <p class="eyebrow hero-kicker"><span class="hero-kicker-dot"></span> CARIBBEAN INSIGHT. PRACTICAL RESULTS.</p>
            <h1 id="home-image-title">Knowledge. Innovation.<br>Technology.</h1>
            <p class="home-image-lead">A Caribbean Business Transformation, Research and Technology Solutions Consultancy.</p>
            <div class="home-hero-actions">
                <a class="button button-primary" href="#what-we-do">Explore our expertise <span class="button-arrow" aria-hidden="true">↗</span></a>
                <a class="home-hero-link" href="#about">Discover BITS <span aria-hidden="true">↗</span></a>
            </div>
        </div></div>
        <a class="hero-insight-card" href="#about">
            <span>REGIONAL KNOWLEDGE. REAL RESULTS.</span>
            <strong>Built for Caribbean organisations.</strong>
            <b aria-hidden="true">↗</b>
        </a>
    </section>

    <nav class="home-quick-actions" aria-label="Explore BITS expertise">
        <div class="quick-actions-title"><span>HOW CAN WE</span><strong>MOVE YOU FORWARD?</strong></div>
        <a href="#what-we-do"><span class="quick-action-icon">@include('partials.admin.icon', ['name' => 'search'])</span><span>Research & insight</span><b aria-hidden="true">↗</b></a>
        <a href="#what-we-do"><span class="quick-action-icon">@include('partials.admin.icon', ['name' => 'activity'])</span><span>Strategy & growth</span><b aria-hidden="true">↗</b></a>
        <a href="#what-we-do"><span class="quick-action-icon">@include('partials.admin.icon', ['name' => 'cpu'])</span><span>Technology solutions</span><b aria-hidden="true">↗</b></a>
    </nav>

    <section class="about-section section-padding" id="about" aria-labelledby="about-title">
        <div class="about-visual">
            <div class="about-symbol" aria-hidden="true">@include('partials.brand-mark')<span class="orbit orbit-one"></span><span class="orbit orbit-two"></span><span class="orbit-dot"></span></div>
            <div class="about-proof" aria-label="More than 80 cumulative years of professional expertise">
                <strong>80<span>+</span></strong>
                <p>Cumulative years of<br>professional expertise</p>
            </div>
            <div class="about-region"><span class="status-dot"></span> English-speaking Caribbean</div>
        </div>
        <div class="about-copy">
            <p class="eyebrow">WHO WE ARE</p>
            <h1 id="about-title">BUSINESS INNOVATION AND TECHNOLOGY SOLUTIONS (BITS) LIMITED</h1>
            <p class="about-intro">A multidisciplinary business development, research, innovation and technology solutions consultancy.</p>
            <p>We bring together professional knowledge, practical experience and specialised expertise from across the English-speaking Caribbean.</p>
            <p>We partner with businesses, government institutions and social organisations to strengthen organisational capacity, improve operational performance, develop competitive advantages and unlock new opportunities for growth.</p>
            <p>Our approach combines strategic thinking, evidence-based research, contemporary industry knowledge and technology-driven solutions to deliver practical, innovative and cost-effective results.</p>
            <p>We are particularly committed to helping start-ups and small and medium-sized enterprises build sustainable capacity, while providing larger organisations with agile research, strategic advisory and targeted project support.</p>
            <div class="about-audiences" aria-label="Organisations we support">
                <span>Businesses</span><span>Government</span><span>Social organisations</span>
            </div>
        </div>
    </section>

    <section class="solutions-section section-padding" id="what-we-do" aria-labelledby="what-we-do-title">
        <div class="section-heading"><div><p class="eyebrow">OUR AREAS OF EXPERTISE</p><h2 id="what-we-do-title">Integrated expertise.<br><span>Measurable impact.</span></h2></div><p>BITS provides integrated solutions across six principal service areas.</p></div>
        <div class="feature-grid expertise-grid">
            <article class="feature-card expertise-card"><span class="feature-number">01 / RESEARCH</span><div class="expertise-icon">@include('partials.admin.icon', ['name' => 'search'])</div><h3>Business Research and Market Intelligence</h3><ul><li>Focus group research and consumer insights</li><li>FMCG research</li><li>Omnibus research and market surveys</li><li>Customer behaviour and market opportunity assessments</li><li>Competitor analysis and business intelligence</li></ul></article>
            <article class="feature-card expertise-card"><span class="feature-number">02 / STRATEGY</span><div class="expertise-icon">@include('partials.admin.icon', ['name' => 'activity'])</div><h3>Business Strategy, Marketing and Brand Development</h3><ul><li>Business development and growth strategies</li><li>Brand positioning and marketing plans</li><li>Marketing campaign development and execution</li><li>Promotional strategies and market activation</li><li>Public relations and corporate communications</li></ul></article>
            <article class="feature-card expertise-card"><span class="feature-number">03 / EXPERIENCE</span><div class="expertise-icon">@include('partials.admin.icon', ['name' => 'users'])</div><h3>Customer Experience and Operational Efficiency</h3><ul><li>Customer service performance assessments</li><li>Customer experience improvement strategies</li><li>Service delivery reviews and process optimisation</li><li>Frontline employee development</li><li>Performance monitoring and quality improvement</li></ul></article>
            <article class="feature-card expertise-card"><span class="feature-number">04 / TECHNOLOGY</span><div class="expertise-icon">@include('partials.admin.icon', ['name' => 'cpu'])</div><h3>Technology and Digital Solutions</h3><ul><li>Technology needs assessments and development solutions</li><li>Digital transformation support</li><li>Technology-enabled business processes</li><li>Digital communication and engagement strategies</li><li>Innovative technologies that improve performance</li></ul></article>
            <article class="feature-card expertise-card"><span class="feature-number">05 / PEOPLE</span><div class="expertise-icon">@include('partials.admin.icon', ['name' => 'grid'])</div><h3>Human Resource Development and Organisational Capacity</h3><ul><li>Human resource needs and skills mapping</li><li>Staff development and training strategies</li><li>Competency assessments and performance improvement</li><li>Organisational development support</li><li>Workforce capacity building and professional development</li></ul></article>
            <article class="feature-card expertise-card"><span class="feature-number">06 / ENGAGEMENT</span><div class="expertise-icon">@include('partials.admin.icon', ['name' => 'server'])</div><h3>Development Support and Public Engagement</h3><ul><li>Development programme communications</li><li>Public education and awareness campaigns</li><li>Community mobilisation and engagement strategies</li><li>Stakeholder consultation and participation</li><li>Social and community development communication initiatives</li></ul></article>
        </div>
    </section>

    <section class="vision-mission-section section-padding" aria-labelledby="approach-title">
        <div class="vision-mission-heading"><p class="eyebrow">OUR APPROACH AND COMMITMENT</p><h2 id="approach-title">Turning insight into meaningful outcomes.</h2></div>
        <div class="vision-mission-grid approach-grid">
            <article class="purpose-card"><p class="purpose-label">HOW WE WORK</p><h3>Informed by evidence</h3><p>Effective solutions must be informed by sound research, guided by current professional knowledge and strengthened by the intelligent application of technology.</p></article>
            <article class="purpose-card purpose-card-mission"><p class="purpose-label">OUR COMMITMENT</p><h3>Focused on measurable difference</h3><p>We bring together the right expertise, analytical insight and practical resources to address challenges, identify opportunities and translate ideas into meaningful outcomes.</p></article>
        </div>
        <p class="approach-statement">Whether supporting an emerging enterprise, improving the performance of an established business, assisting a government department with public engagement, or helping a social organisation strengthen its development initiatives, our focus remains the same: delivering solutions that make a measurable difference.</p>
    </section>

    <section class="get-started-section" aria-labelledby="corporate-tagline">
        <p class="eyebrow"><span class="status-dot"></span> BUSINESS INNOVATION AND TECHNOLOGY SOLUTIONS</p>
        <h2 id="corporate-tagline">Turning knowledge, innovation and technology into <span>business and organisational results.</span></h2>
        <p>A strategic Caribbean partner for business, institutional and development challenges.</p>
        <a class="button button-primary" href="{{ route('login') }}">Enter the client portal <span class="button-arrow" aria-hidden="true">↗</span></a>
        <span class="cta-orbit" aria-hidden="true"></span>
    </section>
@endsection
