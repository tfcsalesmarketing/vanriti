@php
    $contactEmail = tfc_setting('contact_email', 'contact@tfcsalesmarketing.com');
    $supportPhone = tfc_setting('support_phone', '+91 98765 43210');
    $isPhoneValid = $supportPhone && !str_contains($supportPhone, '123456');
@endphp

<footer id="contact" class="footer-section">
    {{-- Ambient Glowing Background Orbs & Subtle Grid Pattern --}}
    <div class="footer-bg" aria-hidden="true">
        <span class="footer-orb footer-orb--1"></span>
        <span class="footer-orb footer-orb--2"></span>
        <span class="footer-orb footer-orb--3"></span>
        <span class="footer-grid-pattern"></span>
    </div>
    <div class="footer-overlay"></div>

    <div class="footer-container">
        <div class="footer-grid">
            {{-- Col 1: Company --}}
            <div class="footer-column">
                <h3>Company</h3>
                <a href="{{ tfc_route('home') }}">Home</a>
                <a href="{{ tfc_route('about') }}">About Us</a>
                <a href="{{ tfc_route('direct-selling') }}">Direct Selling Model</a>
                <a href="{{ tfc_route('business-plan') }}">Business Plan</a>
                <a href="{{ tfc_route('downloads') }}">Downloads</a>
                <a href="{{ tfc_route('faqs') }}">FAQs</a>
                <a href="{{ tfc_route('contact') }}">Contact Us</a>
            </div>

            {{-- Col 2: Products --}}
            <div class="footer-column">
                <h3>Product Lines</h3>
                <a href="{{ tfc_route('products') }}">All Formulations</a>
                <a href="{{ tfc_route('products') }}?category=wellness">Wellness Essentials</a>
                <a href="{{ tfc_route('products') }}?category=personal-care">Personal Care</a>
                <a href="{{ tfc_route('products') }}?category=lifestyle">Lifestyle Products</a>
                <a href="{{ tfc_route('products') }}">Quality Standards</a>
            </div>

            {{-- Col 3: Direct Seller --}}
            <div class="footer-column">
                <h3>Direct Seller</h3>
                <a href="{{ route('tfc.preview.member.login') }}">Member Portal Login</a>
                <a href="{{ route('tfc.preview.member.register') }}">Become A Direct Seller</a>
                <a href="{{ tfc_route('how-it-works') }}">How It Works</a>
                <a href="{{ tfc_route('grievance') }}">Consumer Grievance</a>
                <a href="{{ tfc_route('grievance.track') }}">Track Grievance Ticket</a>
            </div>

            {{-- Col 4: Policies --}}
            <div class="footer-column">
                <h3>Policies</h3>
                <a href="{{ tfc_route('policy', 'terms-of-service') }}">Terms &amp; Conditions</a>
                <a href="{{ tfc_route('policy', 'privacy-policy') }}">Privacy Policy</a>
                <a href="{{ tfc_route('policy', 'return-and-refund-policy') }}">Return &amp; Refund Policy</a>
                <a href="{{ tfc_route('policy', 'direct-seller-code-of-ethics') }}">Code of Ethics</a>
                <a href="{{ tfc_route('policy', 'disclaimer') }}">Disclaimer</a>
            </div>

            {{-- Col 5: Corporate Contact Info --}}
            <div class="footer-column footer-contact">
                <h3 class="footer-brand">{{ tfc_setting('company_name', 'TFC Sales & Marketing') }}</h3>
                <p class="footer-tagline-text">
                    {{ tfc_setting('company_tagline', 'Connecting Products. Creating Possibilities.') }}
                </p>
                <p>
                    {{ tfc_setting('office_address', 'Corporate Business Center, New Delhi, India') }}
                </p>
                @if ($isPhoneValid)
                    <p>
                        <strong>Phone:</strong> <a href="tel:{{ $supportPhone }}">{{ $supportPhone }}</a>
                    </p>
                @endif
                <p>
                    <strong>Email:</strong> <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
                </p>
                <div class="footer-socials">
                    <a href="{{ tfc_setting('facebook_url', '#') }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                        <i class="ri-facebook-fill" aria-hidden="true"></i>
                    </a>
                    <a href="{{ tfc_setting('instagram_url', '#') }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                        <i class="ri-instagram-line" aria-hidden="true"></i>
                    </a>
                    <a href="{{ tfc_setting('linkedin_url', '#') }}" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                        <i class="ri-linkedin-fill" aria-hidden="true"></i>
                    </a>
                    <a href="{{ tfc_setting('youtube_url', '#') }}" target="_blank" rel="noopener noreferrer" aria-label="YouTube">
                        <i class="ri-youtube-fill" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>
                &copy; {{ date('Y') }} <strong>{{ tfc_setting('company_name', 'TFC Sales & Marketing') }}</strong>. All Rights Reserved.
            </p>
            <p>
                India's Trusted Direct Selling Enterprise &bull; Quality Consumer Formulations &bull; Pan-India Distribution
            </p>
        </div>
    </div>
</footer>
