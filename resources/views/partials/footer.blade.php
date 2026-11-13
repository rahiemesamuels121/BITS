<footer class="site-footer">
    <div class="footer-identity">
    <a class="brand" href="{{ url('/') }}" aria-label="BITS home">
        @include('partials.brand-mark')
        <span class="brand-name">BITS<span class="brand-dot">.</span></span>
    </a>
    <p class="footer-company-name">Business Innovation &amp;<br>Technology Solutions</p>
    </div>
    <address class="footer-contact" aria-label="BITS contact details">
        <div>
            <span class="footer-contact-label">Phone</span>
            @if(config('company.phone'))
                <a href="tel:{{ preg_replace('/[^+0-9]/', '', config('company.phone')) }}">{{ config('company.phone') }}</a>
            @else
                <span>Phone number coming soon</span>
            @endif
        </div>
        <div>
            <span class="footer-contact-label">Email</span>
            @if(config('company.email'))
                <a href="mailto:{{ config('company.email') }}">{{ config('company.email') }}</a>
            @else
                <span>Email address coming soon</span>
            @endif
        </div>
        <div>
            <span class="footer-contact-label">Mailing address</span>
            <span class="footer-mailing-address">{{ config('company.mailing_address') ?: 'Mailing address coming soon' }}</span>
        </div>
    </address>
    <div class="footer-bottom">
    <small>&copy; {{ date('Y') }} BITS Limited. All rights reserved.</small>
    <a class="back-to-top" href="#main-content">Back to top <span aria-hidden="true">↑</span></a>
    </div>
</footer>
