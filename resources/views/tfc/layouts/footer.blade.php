@php
    $footerCol1 = \App\Models\Tfc\TfcMenu::active()->where('location', 'footer_col1')->get();
    $footerCol2 = \App\Models\Tfc\TfcMenu::active()->where('location', 'footer_col2')->get();
    
    // Fetch published legal pages if any
    $publishedPageSlugs = \App\Models\Tfc\TfcPage::where('status', 'published')->pluck('slug')->toArray();
    $footerLegal = \App\Models\Tfc\TfcMenu::active()
        ->where('location', 'footer_legal')
        ->get()
        ->filter(function($item) use ($publishedPageSlugs) {
            $slug = trim(str_replace('/policy/', '', $item->url), '/');
            return in_array($slug, $publishedPageSlugs);
        });

    $contactEmail = tfc_setting('contact_email', 'contact@tfcsalesmarketing.com');
    $supportPhone = tfc_setting('support_phone');
    // Hide placeholder dummy phone numbers
    $isPhoneValid = $supportPhone && !str_contains($supportPhone, '98765') && !str_contains($supportPhone, '123456');
@endphp

<footer class="tfc-footer">
    <div class="container">
        <div class="row g-4 g-lg-5 mb-4">
            {{-- Col 1: Corporate Brand & Profile --}}
            <div class="col-12 col-md-6 col-lg-4">
                <a href="{{ tfc_route('home') }}" class="d-inline-block mb-3">
                    <img src="{{ tfc_logo_url() }}" alt="{{ tfc_setting('company_name', 'TFC Sales & Marketing') }}" style="max-height: 52px; background: #FFFFFF; padding: 4px 10px; border-radius: 6px;">
                </a>
                <p class="text-warning small fw-semibold text-uppercase mb-2" style="letter-spacing: 0.5px;">
                    {{ tfc_setting('company_tagline', 'CONNECTING PRODUCTS. CREATING POSSIBILITIES.') }}
                </p>
                <p class="small text-white-50 mb-3" style="line-height: 1.6;">
                    TFC Sales &amp; Marketing is a premier direct-selling enterprise dedicated to championing wellness, entrepreneurial freedom, and holistic personal care. We deliver science-backed botanical formulations directly to households through an empowered network of independent business leaders.
                </p>

                <div class="small text-white-50 mb-3">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-envelope text-warning"></i>
                        <a href="mailto:{{ $contactEmail }}" class="text-white-50 text-decoration-none hover-gold">{{ $contactEmail }}</a>
                    </div>
                    @if ($isPhoneValid)
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-telephone text-warning"></i>
                            <span class="text-white-50">{{ $supportPhone }}</span>
                        </div>
                    @endif
                </div>

                <div class="d-flex align-items-center gap-3 pt-1">
                    @if (tfc_setting('facebook_url') && !str_contains(tfc_setting('facebook_url'), 'facebook.com#'))
                        <a href="{{ tfc_setting('facebook_url') }}" target="_blank" rel="noopener" class="text-white-50 hover-gold fs-5" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    @endif
                    @if (tfc_setting('instagram_url') && !str_contains(tfc_setting('instagram_url'), 'instagram.com#'))
                        <a href="{{ tfc_setting('instagram_url') }}" target="_blank" rel="noopener" class="text-white-50 hover-gold fs-5" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    @endif
                    @if (tfc_setting('linkedin_url') && !str_contains(tfc_setting('linkedin_url'), 'linkedin.com#'))
                        <a href="{{ tfc_setting('linkedin_url') }}" target="_blank" rel="noopener" class="text-white-50 hover-gold fs-5" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    @endif
                    @if (tfc_setting('youtube_url') && !str_contains(tfc_setting('youtube_url'), 'youtube.com#'))
                        <a href="{{ tfc_setting('youtube_url') }}" target="_blank" rel="noopener" class="text-white-50 hover-gold fs-5" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    @endif
                </div>
            </div>

            {{-- Col 2: Navigation Links --}}
            <div class="col-6 col-md-3 col-lg-2">
                <h6 class="text-white fw-bold mb-3 pb-2 border-bottom border-white border-opacity-10">TFC Enterprise</h6>
                <ul class="tfc-footer-links">
                    <li><a href="{{ tfc_route('home') }}">Home</a></li>
                    <li><a href="{{ tfc_route('about') }}">About TFC</a></li>
                    <li><a href="{{ tfc_route('products') }}">Product Showcase</a></li>
                    <li><a href="{{ tfc_route('opportunity') }}">Opportunity</a></li>
                    <li><a href="{{ tfc_route('incentive-program') }}">Incentive Plan</a></li>
                    <li><a href="{{ tfc_route('brand') }}">Product Standards</a></li>
                    <li><a href="{{ tfc_route('become-a-seller') }}">Join As Partner</a></li>
                </ul>
            </div>

            {{-- Col 3: Support & Grievance --}}
            <div class="col-6 col-md-3 col-lg-3">
                <h6 class="text-white fw-bold mb-3 pb-2 border-bottom border-white border-opacity-10">Member &amp; Support</h6>
                <ul class="tfc-footer-links">
                    <li><a href="{{ route('tfc.preview.member.login') }}"><i class="bi bi-person-circle text-warning me-1"></i>Distributor Portal</a></li>
                    <li><a href="{{ tfc_route('how-it-works') }}">How It Works</a></li>
                    <li><a href="{{ tfc_route('faqs') }}">Frequently Asked Questions</a></li>
                    <li><a href="{{ tfc_route('contact') }}">Contact &amp; Inquiries</a></li>
                    <li><a href="{{ tfc_route('grievance') }}">Customer Care &amp; Grievance</a></li>
                    <li><a href="{{ tfc_route('grievance.track') }}">Track Support Ticket</a></li>
                </ul>
            </div>

            {{-- Col 4: Corporate Governance & Policies --}}
            <div class="col-12 col-md-12 col-lg-3">
                <h6 class="text-white fw-bold mb-3 pb-2 border-bottom border-white border-opacity-10">Corporate Policies</h6>
                @if ($footerLegal->isNotEmpty())
                    <ul class="tfc-footer-links mb-3">
                        @foreach ($footerLegal as $item)
                            @php
                                $targetUrl = str_starts_with($item->url, 'http') ? $item->url : tfc_route(ltrim($item->url, '/'));
                            @endphp
                            <li>
                                <a href="{{ $targetUrl }}" {{ $item->open_new_tab ? 'target="_blank" rel="noopener"' : '' }}>
                                    {{ $item->title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <ul class="tfc-footer-links mb-3">
                        <li><a href="{{ tfc_route('policy', 'terms-of-service') }}">Terms of Service</a></li>
                        <li><a href="{{ tfc_route('policy', 'privacy-policy') }}">Privacy Policy</a></li>
                        <li><a href="{{ tfc_route('policy', 'code-of-ethics') }}">Distributor Code of Ethics</a></li>
                        <li><a href="{{ tfc_route('policy', 'returns-and-refunds') }}">Return &amp; Refund Policy</a></li>
                        <li><a href="{{ tfc_route('policy', 'compensation-plan') }}">Incentive Guidelines</a></li>
                    </ul>
                @endif

                <div class="p-3 rounded bg-black bg-opacity-25 border border-white border-opacity-10 text-white-50" style="font-size: 0.74rem;">
                    <span class="text-warning fw-semibold d-block mb-1"><i class="bi bi-patch-check-fill me-1"></i>Direct-Selling Excellence</span>
                    Transparent retail margins, pure botanical formulations, and zero joining fees for independent distributors.
                </div>
            </div>
        </div>

        {{-- Footer Bottom Copyright --}}
        <div class="tfc-footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 pt-3 border-top border-white border-opacity-10">
            <div class="text-white-50 small">
                &copy; {{ date('Y') }} {{ tfc_setting('company_name', 'TFC Sales & Marketing') }}. All rights reserved.
            </div>
            <div class="text-white-50 small d-flex align-items-center gap-2">
                <i class="bi bi-shield-lock text-warning"></i>
                <span>Certified Formulations &bull; Pan-India Logistics &bull; Empowered Commerce</span>
            </div>
        </div>
    </div>
</footer>
