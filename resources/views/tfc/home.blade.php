@extends('tfc.layouts.app')

@section('title', tfc_setting('company_name', 'TFC Sales & Marketing') . ' | India\'s Premier Direct Selling Company')
@section('meta_description', 'TFC Sales & Marketing is a premier direct-selling enterprise in India offering high-performance wellness and personal care formulations with an equitable, rewarding business model.')

@section('content')
<main id="main-content">

{{-- 1. Premium Light Hero Banner --}}
<section class="tfc-hero-light py-5 position-relative overflow-hidden" style="background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 60%, #F1F5F9 100%); border-bottom: 1px solid rgba(0,0,0,0.05);">
    <div class="position-absolute top-0 end-0 translate-middle-y rounded-circle pointer-events-none" style="width: 500px; height: 500px; background: radial-gradient(circle, rgba(37,99,235,0.06) 0%, rgba(255,255,255,0) 70%); z-index: 0;"></div>
    <div class="position-absolute bottom-0 start-0 translate-middle rounded-circle pointer-events-none" style="width: 600px; height: 600px; background: radial-gradient(circle, rgba(212,155,36,0.06) 0%, rgba(255,255,255,0) 70%); z-index: 0;"></div>

    <div class="container py-4 py-lg-5 position-relative" style="z-index: 1;">
        <div class="row align-items-center g-5">
            <div class="col-12 col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill mb-3" style="background: rgba(30, 58, 138, 0.08); border: 1px solid rgba(30, 58, 138, 0.15);">
                    <i class="ri-sparkling-fill" style="color: #D49B24;"></i>
                    <span class="small fw-bold" style="color: #0A1D37; letter-spacing: 0.5px; font-size: 0.8rem;">INDIA'S PREMIER DIRECT SELLING ENTERPRISE</span>
                </div>

                <h1 class="display-5 fw-bold mb-3" style="color: #0A1D37; letter-spacing: -0.02em; line-height: 1.2;">
                    Connecting Products. <br>
                    <span style="background: linear-gradient(135deg, #1E3A8A 0%, #2563EB 50%, #D49B24 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Creating Possibilities.</span>
                </h1>

                <p class="lead text-muted mb-4" style="font-size: 1.1rem; line-height: 1.8; max-width: 580px;">
                    Experience high-performance wellness formulations and build an independent direct selling business backed by transparent incentives, ethical standards, and comprehensive mentorship.
                </p>

                <div class="d-flex flex-wrap gap-3 mb-4">
                    <a href="{{ route('tfc.preview.member.register') }}" class="btn btn-tfc-primary px-4 py-3 fw-bold rounded-3 shadow-sm d-inline-flex align-items-center gap-2">
                        <span>Become A Direct Seller</span>
                        <i class="ri-arrow-right-line"></i>
                    </a>
                    <a href="{{ tfc_route('business-plan') }}" class="btn btn-outline-dark px-4 py-3 fw-semibold rounded-3 d-inline-flex align-items-center gap-2" style="background: #FFFFFF;">
                        <i class="ri-line-chart-line text-warning"></i>
                        <span>Explore Business Plan</span>
                    </a>
                </div>

                {{-- Trust Pillars --}}
                <div class="row g-3 pt-3 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                    <div class="col-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ri-checkbox-circle-fill fs-5 text-success"></i>
                            <div>
                                <div class="fw-bold small text-dark">Zero Fee</div>
                                <div class="text-muted" style="font-size: 0.72rem;">Free Registration</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ri-shield-star-fill fs-5 text-primary"></i>
                            <div>
                                <div class="fw-bold small text-dark">Quality Assured</div>
                                <div class="text-muted" style="font-size: 0.72rem;">Certified Products</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ri-truck-fill fs-5 text-warning"></i>
                            <div>
                                <div class="fw-bold small text-dark">Pan-India</div>
                                <div class="text-muted" style="font-size: 0.72rem;">Doorstep Logistics</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Hero Visual Card --}}
            <div class="col-12 col-lg-5">
                <div class="position-relative">
                    <div class="p-4 p-md-5 rounded-4 shadow-sm" style="background: #FFFFFF; border: 1px solid rgba(0,0,0,0.08); border-radius: 20px;">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background: rgba(212, 155, 36, 0.15); width: 44px; height: 44px;">
                                    <i class="ri-trophy-line fs-5" style="color: #D49B24;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small text-dark">Direct Selling Community</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">Empowering Independent Entrepreneurs</div>
                                </div>
                            </div>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2.5 py-1">Active</span>
                        </div>

                        {{-- Metric Highlights --}}
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <div class="p-3 rounded-3" style="background: #F8FAFC; border: 1px solid rgba(0,0,0,0.04);">
                                    <div class="text-muted small">Dual-Leg Team</div>
                                    <div class="fs-4 fw-bold text-dark mt-1">Orange &amp; Blue</div>
                                    <div class="text-primary small" style="font-size: 0.75rem;">Balanced Synergy</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded-3" style="background: #F8FAFC; border: 1px solid rgba(0,0,0,0.04);">
                                    <div class="text-muted small">Retail Benefit</div>
                                    <div class="fs-4 fw-bold text-success mt-1">Up to 30%</div>
                                    <div class="text-muted small" style="font-size: 0.75rem;">Direct Margin</div>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 rounded-3 mb-4" style="background: linear-gradient(135deg, rgba(30, 58, 138, 0.05) 0%, rgba(212, 155, 36, 0.08) 100%); border: 1px solid rgba(212, 155, 36, 0.2);">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-bold text-dark small">Independent Distributor Portal</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Live binary tree, referral tracking &amp; KYC</div>
                                </div>
                                <a href="{{ route('tfc.preview.member.login') }}" class="btn btn-sm btn-outline-dark fw-semibold" style="font-size: 0.78rem;">
                                    Sign In &rarr;
                                </a>
                            </div>
                        </div>

                        <div class="text-center">
                            <a href="{{ route('tfc.preview.member.register') }}" class="btn btn-warning text-dark fw-bold w-100 py-2.5 shadow-sm">
                                Register as Direct Seller Today
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 2. About Section (Signature Two-Column Split Layout with Stat Badges) --}}
<section id="about" class="about-section py-5" style="background-color: #FFFFFF;">
    <div class="about-container">
        {{-- Visual with curved container, decorative organic blob, and floating stat badges --}}
        <div class="about-visual">
            <span class="about-blob" aria-hidden="true"></span>
            <div class="about-image-wrap shadow-sm">
                <img src="https://eduse-uploadedfiles.s3.ap-south-1.amazonaws.com/edusemarketing/static/kfjghjdshfiuehdsvnmd.webp" alt="TFC Team and Business Opportunity" width="1200" height="800" loading="lazy" decoding="async">
            </div>
            <div class="about-stat-card about-stat-card--1 shadow-sm">
                <strong>100%</strong>
                <span>Quality Tested</span>
            </div>
            <div class="about-stat-card about-stat-card--2 shadow-sm">
                <strong>Pan-India</strong>
                <span>Logistics Network</span>
            </div>
        </div>

        {{-- About Content --}}
        <div class="about-content">
            <span class="section-tag" style="background: rgba(30, 58, 138, 0.08); color: #1E3A8A;">ABOUT TFC</span>
            <h2>India's Fast Growing Direct Selling Opportunity</h2>
            <p class="about-lead">
                {{ tfc_setting('company_name', 'TFC Sales & Marketing') }} is creating sustainable business opportunities for entrepreneurs, professionals, and dreamers across India through an ethical, product-centered direct selling model.
            </p>
            <ul class="about-highlights">
                <li><i class="ri-checkbox-circle-fill text-warning" aria-hidden="true"></i> Transparent, trusted direct-selling business model</li>
                <li><i class="ri-checkbox-circle-fill text-warning" aria-hidden="true"></i> Premium botanical wellness &amp; daily care formulations</li>
                <li><i class="ri-checkbox-circle-fill text-warning" aria-hidden="true"></i> Leadership mentorship, digital tools &amp; live webinars</li>
            </ul>
            <div class="about-features">
                <div class="about-feature-item">
                    <span class="about-feature-icon"><i class="ri-team-fill" aria-hidden="true"></i></span>
                    <div>
                        <h4>Strong Distributor Community</h4>
                        <p>Nationwide community of motivated direct sellers and leaders</p>
                    </div>
                </div>
                <div class="about-feature-item">
                    <span class="about-feature-icon"><i class="ri-money-rupee-circle-fill" aria-hidden="true"></i></span>
                    <div>
                        <h4>High-Yield Dual Team Rewards</h4>
                        <p>Balanced 2-leg compensation plan designed for long-term prosperity</p>
                    </div>
                </div>
                <div class="about-feature-item">
                    <span class="about-feature-icon"><i class="ri-line-chart-fill" aria-hidden="true"></i></span>
                    <div>
                        <h4>Executive Growth &amp; Mentorship</h4>
                        <p>Digital member portal, real-time tree structure, and team-building support</p>
                    </div>
                </div>
            </div>
            <a href="{{ tfc_route('about') }}" class="about-cta shadow-sm">
                Why Choose TFC <i class="ri-arrow-right-line" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</section>

{{-- 3. Product Categories Section --}}
@if ($categories->isNotEmpty())
<section id="brands" class="brands-section py-5" aria-labelledby="brands-heading" style="background-color: #F8FAFC;">
    <div class="brands-inner">
        <span class="section-tag" style="background: rgba(30, 58, 138, 0.08); color: #1E3A8A;">PRODUCT DISCIPLINES</span>
        <h2 id="brands-heading">Our Product Categories</h2>
        <div class="brands-row">
            @foreach ($categories as $cat)
                <a href="{{ tfc_route('category', $cat->slug) }}" class="brand-card shadow-sm">
                    <div class="brand-circle">
                        @if ($cat->image)
                            <img class="brand-logo" src="{{ image_url($cat->image) }}" alt="{{ $cat->name }}" width="120" height="120" loading="lazy" decoding="async">
                        @else
                            <i class="ri-leaf-line display-5 text-warning"></i>
                        @endif
                    </div>
                    <span class="brand-label">{{ $cat->name }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- 4. Featured Products Showcase (Queried Live from Database) --}}
<section id="products" class="products-section py-5" style="background-color: #FFFFFF;">
    <div class="container py-2">
        <div class="section-heading text-center mb-5">
            <span class="section-tag" style="background: rgba(30, 58, 138, 0.08); color: #1E3A8A;">PREMIUM FORMULATIONS</span>
            <h2>Featured Products</h2>
            <p class="why-intro">
                High-potency botanical care and personal wellness essentials crafted for noticeable everyday vitality.
            </p>
        </div>

        <div class="row g-3 g-md-4">
            @forelse ($featuredProducts as $product)
                @php
                    $img = $product->getPrimaryImage()?->image_path;
                    $discount = $product->discountPercent();
                @endphp
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card tfc-clean-product-card h-100 shadow-sm border" style="border-radius: 16px; transition: transform 0.25s ease, box-shadow 0.25s ease;">
                        <div class="tfc-clean-product-img-wrap" style="position: relative; overflow: hidden; border-top-left-radius: 15px; border-top-right-radius: 15px; aspect-ratio: 1; background: #F8FAFC;">
                            <a href="{{ tfc_route('products.show', $product->slug) }}">
                                @if ($img)
                                    <img src="{{ image_url($img) }}" alt="{{ $product->name }}" class="tfc-clean-product-img w-100 h-100 object-fit-cover" loading="lazy">
                                @else
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                        <i class="ri-image-line fs-1"></i>
                                    </div>
                                @endif
                            </a>
                            @if ($discount > 0)
                                <span class="badge bg-danger position-absolute top-0 start-0 m-2 px-2.5 py-1 rounded-pill" style="font-size: 0.72rem; font-weight: 600;">
                                    {{ round($discount) }}% OFF
                                </span>
                            @endif
                        </div>
                        <div class="card-body d-flex flex-column p-3 p-md-3">
                            <span class="small text-muted mb-1 text-truncate" style="font-size: 0.75rem;">
                                {{ $product->primaryCategory()?->name ?? 'Consumer Products' }}
                            </span>
                            <h6 class="fw-bold mb-2 text-dark" style="font-size: 0.92rem; line-height: 1.4;">
                                <a href="{{ tfc_route('products.show', $product->slug) }}" class="text-dark text-decoration-none hover-primary">
                                    {{ $product->name }}
                                </a>
                            </h6>
                            <div class="mt-auto pt-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <span class="fw-bold text-dark fs-6">{{ format_price($product->selling_price) }}</span>
                                    @if ($product->mrp && $product->mrp > $product->selling_price)
                                        <small class="text-muted text-decoration-line-through ms-1" style="font-size: 0.75rem;">{{ format_price($product->mrp) }}</small>
                                    @endif
                                </div>
                                <a href="{{ tfc_route('products.show', $product->slug) }}" class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.8rem;">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Formulations currently updating.</p>
                </div>
            @endforelse
        </div>

        <div class="products-section__footer mt-5 text-center">
            <a href="{{ tfc_route('products') }}" class="products-all-btn shadow-sm">
                <span>View Complete Catalogue</span>
                <i class="ri-arrow-right-line" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</section>

{{-- 5. Why Choose Us (4-Card Grid 01, 02, 03, 04) --}}
<section id="why-choose-us" class="why-section py-5" style="background-color: #F8FAFC;">
    <div class="why-inner">
        <div class="section-heading text-center mb-5">
            <span class="section-tag" style="background: rgba(30, 58, 138, 0.08); color: #1E3A8A;">WHY CHOOSE US</span>
            <h2>Build Your Future With {{ tfc_setting('company_name', 'TFC Sales & Marketing') }}</h2>
            <p class="why-intro">
                Join a direct-selling company built on trust, training, and real entrepreneurial opportunity — designed to help you succeed step by step.
            </p>
        </div>
        <div class="why-grid">
            <article class="why-card shadow-sm">
                <span class="why-card-num" aria-hidden="true">01</span>
                <div class="why-card-icon"><i class="ri-shield-check-fill" aria-hidden="true"></i></div>
                <h3>Trusted Enterprise</h3>
                <p>Transparent binary business plan with long-term vision, zero joining fees, and sustainable growth.</p>
            </article>
            <article class="why-card shadow-sm">
                <span class="why-card-num" aria-hidden="true">02</span>
                <div class="why-card-icon"><i class="ri-graduation-cap-fill" aria-hidden="true"></i></div>
                <h3>Training &amp; Support</h3>
                <p>Comprehensive executive mentorship, digital back-office tools, and ongoing leadership webinars.</p>
            </article>
            <article class="why-card shadow-sm">
                <span class="why-card-num" aria-hidden="true">03</span>
                <div class="why-card-icon"><i class="ri-global-fill" aria-hidden="true"></i></div>
                <h3>Nationwide Network</h3>
                <p>Expanding rapidly across India with reliable door-to-door logistics and Pan-India customer fulfillment.</p>
            </article>
            <article class="why-card shadow-sm">
                <span class="why-card-num" aria-hidden="true">04</span>
                <div class="why-card-icon"><i class="ri-vip-crown-2-fill" aria-hidden="true"></i></div>
                <h3>Leadership Rewards</h3>
                <p>Generous retail margins, binary matching bonuses, leadership rank awards, and lifestyle recognition.</p>
            </article>
        </div>
    </div>
</section>

{{-- 6. 3-Step Success Journey --}}
<section class="py-5" style="background-color: #FFFFFF;">
    <div class="container py-4">
        <div class="section-heading text-center mb-5">
            <span class="section-tag" style="background: rgba(30, 58, 138, 0.08); color: #1E3A8A;">HOW IT WORKS</span>
            <h2>Three Simple Steps to Success</h2>
            <p class="why-intro">
                Starting your independent direct selling business is simple, transparent, and completely free.
            </p>
        </div>

        <div class="row g-4 text-center">
            <div class="col-12 col-md-4">
                <div class="p-4 rounded-4 h-100 border shadow-sm" style="background: #FAFAFA; border-radius: 18px;">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; background: rgba(30, 58, 138, 0.1); color: #1E3A8A; font-size: 1.5rem; font-weight: 700;">
                        1
                    </div>
                    <h5 class="fw-bold mb-2 text-dark">Register For Free</h5>
                    <p class="text-muted small mb-0">
                        Sign up with an existing sponsor's Member ID. Instant verification via WhatsApp and activation of your portal.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-4 rounded-4 h-100 border shadow-sm" style="background: #FAFAFA; border-radius: 18px;">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; background: rgba(212, 155, 36, 0.15); color: #D49B24; font-size: 1.5rem; font-weight: 700;">
                        2
                    </div>
                    <h5 class="fw-bold mb-2 text-dark">Discover &amp; Share</h5>
                    <p class="text-muted small mb-0">
                        Experience our high-performance wellness products, order at special distributor pricing, and share with your community.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-4 rounded-4 h-100 border shadow-sm" style="background: #FAFAFA; border-radius: 18px;">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; background: rgba(16, 185, 129, 0.1); color: #10B981; font-size: 1.5rem; font-weight: 700;">
                        3
                    </div>
                    <h5 class="fw-bold mb-2 text-dark">Build &amp; Earn</h5>
                    <p class="text-muted small mb-0">
                        Grow your Orange and Blue teams. Earn weekly matching bonuses, retail profit margins, and rank milestone rewards.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 7. Call to Action Banner --}}
<section class="py-5" style="background-color: #F8FAFC;">
    <div class="container py-2">
        <div class="p-4 p-md-5 rounded-4 shadow-sm text-center text-md-start" style="background: linear-gradient(135deg, #0A1D37 0%, #1E3A8A 100%); color: #FFFFFF; border-radius: 20px !important;">
            <div class="row align-items-center g-4">
                <div class="col-12 col-md-8">
                    <span class="badge bg-warning text-dark fw-bold mb-2 px-3 py-1.5" style="font-size: 0.78rem;">
                        START TODAY &bull; ZERO JOINING FEE
                    </span>
                    <h2 class="display-6 fw-bold text-white mb-2" style="letter-spacing: -0.01em;">Ready to Build Your Legacy with TFC?</h2>
                    <p class="text-white-50 mb-0 lead fs-6" style="max-width: 600px; line-height: 1.7;">
                        Take the first step toward financial independence. Register as an independent direct seller today with zero entry fees and access our complete product portfolio and business training.
                    </p>
                </div>
                <div class="col-12 col-md-4 text-md-end">
                    <div class="d-flex flex-column flex-sm-row justify-content-md-end gap-2">
                        <a href="{{ route('tfc.preview.member.register') }}" class="btn btn-warning text-dark fw-bold px-4 py-3 rounded-3 shadow-sm">
                            Register Free <i class="ri-arrow-right-line ms-1"></i>
                        </a>
                        <a href="{{ tfc_route('contact') }}" class="btn btn-outline-light rounded-3 px-4 py-3 fw-semibold">
                            Contact Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

</main>
@endsection
