@php
    $footerCol1 = \App\Models\Tfc\TfcMenu::active()->where('location', 'footer_col1')->get();
    $footerCol2 = \App\Models\Tfc\TfcMenu::active()->where('location', 'footer_col2')->get();
    
    // Only fetch published legal pages to ensure draft policies are not displayed in the public footer
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
        <div class="row g-4 g-lg-5 mb-5">
            {{-- Col 1: Corporate Brand & Profile --}}
            <div class="col-12 col-md-6 col-lg-4">
                <a href="{{ tfc_route('home') }}" class="d-inline-block mb-3">
                    <img src="{{ tfc_logo_url() }}" alt="{{ tfc_setting('company_name', 'TFC Sales & Marketing') }}" style="max-height: 52px; background: #FFFFFF; padding: 4px 10px; border-radius: 6px;">
                </a>
                <p class="text-warning small fw-semibold text-uppercase mb-2" style="letter-spacing: 0.5px;">
                    {{ tfc_setting('company_tagline', 'CONNECTING PRODUCTS. CREATING POSSIBILITIES.') }}
                </p>
                <p class="small text-white-50 mb-3" style="line-height: 1.6;">
                    TFC Sales &amp; Marketing is an Indian direct-selling enterprise operating in official partnership with <strong>VANRITI</strong>. We are committed to ethical commerce, genuine botanical formulations, transparent retail margins, and consumer protection.
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

                <div class="d-flex align-items-center gap-3 pt-2">
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
                <h6 class="text-white fw-bold mb-3 pb-2 border-bottom border-white border-opacity-10">TFC Navigation</h6>
                <ul class="tfc-footer-links">
                    <li><a href="{{ tfc_route('home') }}">Home</a></li>
                    <li><a href="{{ tfc_route('about') }}">About TFC</a></li>
                    <li><a href="{{ tfc_route('brand') }}">VANRITI Brand</a></li>
                    <li><a href="{{ tfc_route('products') }}">Products Catalogue</a></li>
                    <li><a href="{{ tfc_route('how-it-works') }}">How It Works</a></li>
                    <li><a href="{{ tfc_route('become-a-seller') }}">Become a Direct Seller</a></li>
                </ul>
            </div>

            {{-- Col 3: Support & Grievance --}}
            <div class="col-6 col-md-3 col-lg-3">
                <h6 class="text-white fw-bold mb-3 pb-2 border-bottom border-white border-opacity-10">Consumer Support</h6>
                <ul class="tfc-footer-links">
                    <li><a href="{{ tfc_route('grievance') }}"><i class="bi bi-shield-check text-warning me-1"></i>Lodge a Grievance</a></li>
                    <li><a href="{{ tfc_route('grievance.track') }}"><i class="bi bi-search text-warning me-1"></i>Track Grievance Ticket</a></li>
                    <li><a href="{{ tfc_route('contact') }}">Contact &amp; Inquiries</a></li>
                    <li><a href="{{ tfc_route('faqs') }}">Frequently Asked Questions</a></li>
                    <li><a href="{{ tfc_route('opportunity') }}">Opportunity Overview</a></li>
                </ul>
                <div class="mt-3 p-2 rounded bg-black bg-opacity-25 border border-white border-opacity-10">
                    <small class="text-warning d-block fw-semibold" style="font-size: 0.76rem;">
                        <i class="bi bi-clock-history me-1"></i>Rule 7 Statutory Timelines
                    </small>
                    <small class="text-white-50 d-block" style="font-size: 0.72rem; line-height: 1.4;">
                        48-Hour Acknowledgment &bull; 30-Day Transparent Resolution
                    </small>
                </div>
            </div>

            {{-- Col 4: Statutory Disclosures & Legal --}}
            <div class="col-12 col-md-12 col-lg-3">
                <h6 class="text-white fw-bold mb-3 pb-2 border-bottom border-white border-opacity-10">Statutory &amp; Policies</h6>
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
                    <div class="p-3 rounded bg-white bg-opacity-10 border border-white border-opacity-10 mb-3">
                        <small class="text-white-50 d-block" style="font-size: 0.75rem; line-height: 1.5;">
                            <strong class="text-warning d-block mb-1"><i class="bi bi-journal-medical me-1"></i>Statutory Policies in Review</strong>
                            Statutory disclosures, cooling-off terms, and direct-selling codes of conduct are undergoing corporate verification and will be published upon regulatory confirmation.
                        </small>
                    </div>
                @endif

                <div class="p-2 rounded bg-black bg-opacity-25 border border-white border-opacity-10 text-white-50" style="font-size: 0.72rem;">
                    <span class="text-warning fw-semibold d-block mb-1">Direct-Selling Compliance</span>
                    Consumer Protection (Direct Selling) Rules, 2021 &amp; 2023 Amendment. Zero recruitment fees.
                </div>
            </div>
        </div>

        {{-- Mandatory Statutory Declaration Box --}}
        <div class="p-3 rounded bg-black bg-opacity-30 border border-white border-opacity-10 mb-4">
            <div class="row align-items-center">
                <div class="col-12 col-md-9 mb-2 mb-md-0">
                    <small class="text-white-50 d-block" style="font-size: 0.8rem; line-height: 1.5;">
                        <strong class="text-warning">Statutory Notice:</strong> Direct selling involves the sale of genuine consumer products. TFC Sales &amp; Marketing strictly prohibits pyramid or money circulation schemes, does not charge any registration, enrollment, or training fees, and does not promise guaranteed income. Earnings are strictly derived from legitimate sales to consumers.
                    </small>
                </div>
                <div class="col-12 col-md-3 text-md-end">
                    <a href="{{ tfc_route('grievance') }}" class="btn btn-sm btn-outline-warning text-nowrap">
                        <i class="bi bi-shield-check me-1"></i>Grievance Redressal
                    </a>
                </div>
            </div>
        </div>

        {{-- Footer Bottom Copyright & Partnership --}}
        <div class="tfc-footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <div>
                &copy; {{ date('Y') }} {{ tfc_setting('company_name', 'TFC Sales & Marketing') }}. All rights reserved.
            </div>
            <div class="text-white-50 small">
                In Official Direct-Selling Partnership with <a href="{{ config('tfc.vanriti_store_url', 'https://vanriti.com') }}" target="_blank" rel="noopener" class="text-warning text-decoration-none fw-semibold">VANRITI</a>
            </div>
        </div>
    </div>
</footer>
