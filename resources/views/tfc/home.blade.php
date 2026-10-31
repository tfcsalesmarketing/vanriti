@extends('tfc.layouts.app')

@section('title', tfc_setting('company_name', 'TFC Sales & Marketing') . ' | ' . tfc_setting('company_tagline', 'Connecting Products. Creating Possibilities.'))
@section('meta_description', 'TFC Sales & Marketing is a premier direct-selling enterprise in India offering high-performance wellness and personal care formulations with an equitable, rewarding business model.')

@section('content')
<main id="main-content">

{{-- 1. Hero Slider (Clean Responsive Carousel with frosted glass controls like Eduse) --}}
@if ($banners->isNotEmpty())
<section id="home" class="hero-slider" aria-roledescription="carousel" aria-label="Featured banners">
    <div class="slides" id="tfcSliderTrack">
        @foreach ($banners as $i => $banner)
            @php
                $desktopImage = image_url($banner->image);
                $mobileImage = $banner->mobile_image ? image_url($banner->mobile_image) : null;
                $bannerAlt = $banner->title ?: (tfc_setting('company_name', 'TFC Sales & Marketing') . ' promotional banner');
                $bannerTarget = $banner->link ? (str_starts_with($banner->link, 'http') ? $banner->link : tfc_route(ltrim($banner->link, '/'))) : null;
            @endphp
            <div class="slide {{ $i === 0 ? 'active' : '' }}">
                @if ($bannerTarget)
                    <a href="{{ $bannerTarget }}" class="d-block w-100 h-100">
                        <picture>
                            @if ($mobileImage)
                                <source media="(max-width: 767.98px)" srcset="{{ $mobileImage }}">
                            @endif
                            <img src="{{ $desktopImage }}" alt="{{ $bannerAlt }}" width="1920" height="840" decoding="async" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                        </picture>
                    </a>
                @else
                    <picture>
                        @if ($mobileImage)
                            <source media="(max-width: 767.98px)" srcset="{{ $mobileImage }}">
                        @endif
                        <img src="{{ $desktopImage }}" alt="{{ $bannerAlt }}" width="1920" height="840" decoding="async" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                    </picture>
                @endif
            </div>
        @endforeach
    </div>

    @if ($banners->count() > 1)
        <button type="button" class="nav-slider-btn prev" id="sliderPrevBtn" aria-label="Previous slide">
            <i class="ri-arrow-left-s-line" aria-hidden="true"></i>
        </button>
        <button type="button" class="nav-slider-btn next" id="sliderNextBtn" aria-label="Next slide">
            <i class="ri-arrow-right-s-line" aria-hidden="true"></i>
        </button>
        <div class="dots" id="sliderDots" role="tablist" aria-label="Slide navigation">
            @foreach ($banners as $i => $banner)
                <button type="button" class="dot {{ $i === 0 ? 'active' : '' }}" data-slide="{{ $i }}" aria-label="Slide {{ $i + 1 }}"></button>
            @endforeach
        </div>
    @endif
</section>
@else
{{-- Clean Hero Section fallback --}}
<section class="tfc-hero-clean-banner py-5" style="background: linear-gradient(135deg, #070F1C 0%, #0A1D37 60%, #132D54 100%); color: #FFFFFF;">
    <div class="container py-5">
        <div class="row align-items-center g-5">
            <div class="col-12 col-lg-7">
                <span class="section-tag" style="background: rgba(212, 155, 36, 0.2); color: var(--tfc-gold);">
                    <i class="ri-sparkling-fill me-1"></i> DIRECT SELLING ENTERPRISE
                </span>
                <h1 class="display-4 fw-bold mb-3" style="letter-spacing: -0.02em; line-height: 1.2;">
                    Empowering Entrepreneurs. <br>
                    <span style="color: var(--tfc-gold);">Transforming Lifestyles.</span>
                </h1>
                <p class="lead text-white-50 mb-4" style="max-width: 600px; line-height: 1.8;">
                    Experience high-performance botanical wellness formulations and build an independent direct selling enterprise backed by transparent rewards and proven leadership mentorship.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('tfc.preview.member.register') }}" class="about-cta" style="background: var(--tfc-gold); color: #000; font-weight: 700;">
                        Join TFC Today <i class="ri-arrow-right-line" aria-hidden="true"></i>
                    </a>
                    <a href="{{ tfc_route('products') }}" class="btn btn-outline-light rounded-3 px-4 py-3 fw-semibold">
                        <i class="ri-grid-line me-1"></i> Browse Products
                    </a>
                </div>
            </div>
            <div class="col-12 col-lg-5 text-center">
                <div class="p-5 rounded-4 shadow-lg" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.15); backdrop-filter: blur(12px);">
                    <img src="{{ tfc_logo_url() }}" alt="TFC Logo" class="img-fluid rounded mb-3" style="max-height: 75px; background: #fff; padding: 8px 16px;">
                    <h4 class="fw-bold text-white mb-2">Build Your Future</h4>
                    <p class="text-white-50 small mb-0">Join India's fastest-growing ethical direct selling community.</p>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- 2. About Section (Eduse Marketing Signature Two-Column Split Layout) --}}
<section id="about" class="about-section py-5">
    <div class="about-container">
        {{-- Visual with curved container, decorative organic blob, and floating stat badges --}}
        <div class="about-visual">
            <span class="about-blob" aria-hidden="true"></span>
            <div class="about-image-wrap">
                <img src="https://eduse-uploadedfiles.s3.ap-south-1.amazonaws.com/edusemarketing/static/kfjghjdshfiuehdsvnmd.webp" alt="TFC Team and Business Opportunity" width="1200" height="800" loading="lazy" decoding="async">
            </div>
            <div class="about-stat-card about-stat-card--1">
                <strong>100+</strong>
                <span>Active Formulations</span>
            </div>
            <div class="about-stat-card about-stat-card--2">
                <strong>PAN India</strong>
                <span>Logistics Network</span>
            </div>
        </div>

        {{-- About Content --}}
        <div class="about-content">
            <span class="section-tag">ABOUT TFC</span>
            <h2>India's Fast Growing Direct Selling Opportunity</h2>
            <p class="about-lead">
                {{ tfc_setting('company_name', 'TFC Sales & Marketing') }} is creating powerful business opportunities for leaders, entrepreneurs, and dreamers across India through an ethical, product-centered business model.
            </p>
            <ul class="about-highlights">
                <li><i class="ri-checkbox-circle-fill text-warning" aria-hidden="true"></i> Transparent, trusted direct-selling business model</li>
                <li><i class="ri-checkbox-circle-fill text-warning" aria-hidden="true"></i> High-performance wellness &amp; lifestyle formulations</li>
                <li><i class="ri-checkbox-circle-fill text-warning" aria-hidden="true"></i> Hands-on training, executive mentorship &amp; leadership support</li>
            </ul>
            <div class="about-features">
                <div class="about-feature-item">
                    <span class="about-feature-icon"><i class="ri-team-fill" aria-hidden="true"></i></span>
                    <div>
                        <h4>Strong Distributor Network</h4>
                        <p>Nationwide community of motivated direct sellers and leaders</p>
                    </div>
                </div>
                <div class="about-feature-item">
                    <span class="about-feature-icon"><i class="ri-money-rupee-circle-fill" aria-hidden="true"></i></span>
                    <div>
                        <h4>High-Yield Binary Rewards</h4>
                        <p>Balanced binary compensation plan designed for sustainable long-term prosperity</p>
                    </div>
                </div>
                <div class="about-feature-item">
                    <span class="about-feature-icon"><i class="ri-line-chart-fill" aria-hidden="true"></i></span>
                    <div>
                        <h4>Executive Growth &amp; Mentorship</h4>
                        <p>Digital portal, live webinars, and proven team-building systems</p>
                    </div>
                </div>
            </div>
            <a href="{{ tfc_route('about') }}" class="about-cta">
                Why Choose TFC <i class="ri-arrow-right-line" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</section>

{{-- 3. Product Categories Section (Eduse Signature Circular Row) --}}
@if ($categories->isNotEmpty())
<section id="brands" class="brands-section" aria-labelledby="brands-heading">
    <div class="brands-inner">
        <span class="section-tag">OUR DISCIPLINES</span>
        <h2 id="brands-heading">Our Product Categories</h2>
        <div class="brands-row">
            @foreach ($categories as $cat)
                <a href="{{ tfc_route('category', $cat->slug) }}" class="brand-card">
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

{{-- 4. Featured Products Section (Clean Product Cards like Eduse) --}}
<section id="products" class="products-section">
    <div class="container py-2">
        <div class="section-heading text-center mb-4">
            <span class="section-tag">WELLNESS FORMULATIONS</span>
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
                    <div class="card tfc-clean-product-card h-100">
                        <div class="tfc-clean-product-img-wrap">
                            <a href="{{ tfc_route('products.show', $product->slug) }}">
                                @if ($img)
                                    <img src="{{ image_url($img) }}" alt="{{ $product->name }}" class="tfc-clean-product-img" loading="lazy">
                                @else
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                        <i class="ri-image-line fs-1"></i>
                                    </div>
                                @endif
                            </a>
                            @if ($discount > 0)
                                <span class="badge bg-danger position-absolute top-0 start-0 m-2 px-2 py-1 rounded-pill" style="font-size: 0.72rem; font-weight: 600;">
                                    {{ round($discount) }}% OFF
                                </span>
                            @endif
                        </div>
                        <div class="card-body d-flex flex-column p-3 p-md-4">
                            <span class="small text-muted mb-1 text-truncate" style="font-size: 0.75rem;">
                                {{ $product->primaryCategory()?->name ?? 'Wellness Care' }}
                            </span>
                            <h6 class="fw-bold mb-2 text-dark" style="font-size: 0.92rem; line-height: 1.4;">
                                <a href="{{ tfc_route('products.show', $product->slug) }}" class="text-dark text-decoration-none hover-primary">
                                    {{ $product->name }}
                                </a>
                            </h6>
                            <div class="mt-auto pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <span class="fw-bold text-dark fs-6">{{ format_price($product->selling_price) }}</span>
                                    @if ($product->mrp && $product->mrp > $product->selling_price)
                                        <small class="text-muted text-decoration-line-through ms-1">{{ format_price($product->mrp) }}</small>
                                    @endif
                                </div>
                                <a href="{{ tfc_route('products.show', $product->slug) }}" class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1 fw-semibold">
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
            <a href="{{ tfc_route('products') }}" class="products-all-btn">
                <span>View Complete Catalogue</span>
                <i class="ri-arrow-right-line" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</section>

{{-- 5. Why Choose Us (Eduse Signature 4-Card Grid with 01, 02, 03, 04 numbers) --}}
<section id="why-choose-us" class="why-section">
    <div class="why-inner">
        <div class="section-heading">
            <span class="section-tag">WHY CHOOSE US</span>
            <h2>Build Your Future With {{ tfc_setting('company_name', 'TFC Sales & Marketing') }}</h2>
            <p class="why-intro">
                Join a direct-selling company built on trust, training, and real entrepreneurial opportunity — designed to help you succeed step by step.
            </p>
        </div>
        <div class="why-grid">
            <article class="why-card">
                <span class="why-card-num" aria-hidden="true">01</span>
                <div class="why-card-icon"><i class="ri-shield-check-fill" aria-hidden="true"></i></div>
                <h3>Trusted Enterprise</h3>
                <p>Transparent binary business plan with long-term vision, zero joining fees, and sustainable growth.</p>
            </article>
            <article class="why-card">
                <span class="why-card-num" aria-hidden="true">02</span>
                <div class="why-card-icon"><i class="ri-graduation-cap-fill" aria-hidden="true"></i></div>
                <h3>Training &amp; Support</h3>
                <p>Comprehensive executive mentorship, digital back-office tools, and ongoing leadership webinars.</p>
            </article>
            <article class="why-card">
                <span class="why-card-num" aria-hidden="true">03</span>
                <div class="why-card-icon"><i class="ri-global-fill" aria-hidden="true"></i></div>
                <h3>Nationwide Network</h3>
                <p>Expanding rapidly across India with reliable door-to-door logistics and Pan-India customer fulfillment.</p>
            </article>
            <article class="why-card">
                <span class="why-card-num" aria-hidden="true">04</span>
                <div class="why-card-icon"><i class="ri-vip-crown-2-fill" aria-hidden="true"></i></div>
                <h3>Leadership Rewards</h3>
                <p>Generous retail margins, binary matching bonuses, leadership rank awards, and lifestyle recognition.</p>
            </article>
        </div>
    </div>
</section>

{{-- 6. Call to Action Banner --}}
<section class="py-5" style="background-color: #FFFFFF;">
    <div class="container py-4">
        <div class="p-4 p-md-5 rounded-4 shadow-sm text-center text-md-start" style="background: linear-gradient(135deg, #070F1C 0%, #0A1D37 60%, #132D54 100%); color: #FFFFFF; border-radius: 24px !important;">
            <div class="row align-items-center g-4">
                <div class="col-12 col-md-8">
                    <span class="section-tag" style="background: rgba(212, 155, 36, 0.2); color: var(--tfc-gold);">
                        START YOUR JOURNEY
                    </span>
                    <h2 class="display-6 fw-bold text-white mb-3" style="letter-spacing: -0.01em;">Ready to Build Your Legacy with TFC?</h2>
                    <p class="text-white-50 mb-0 lead fs-6" style="max-width: 600px; line-height: 1.7;">
                        Take the first step toward financial independence. Register as an independent direct seller today with zero entry fees and access our complete product portfolio and business training.
                    </p>
                </div>
                <div class="col-12 col-md-4 text-md-end">
                    <div class="d-flex flex-column flex-sm-row justify-content-md-end gap-2">
                        <a href="{{ route('tfc.preview.member.register') }}" class="about-cta" style="background: var(--tfc-gold); color: #000; font-weight: 700;">
                            Register Free <i class="ri-arrow-right-line" aria-hidden="true"></i>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const track = document.getElementById('tfcSliderTrack');
    const slides = document.querySelectorAll('#tfcSliderTrack .slide');
    const prevBtn = document.getElementById('sliderPrevBtn');
    const nextBtn = document.getElementById('sliderNextBtn');
    const dots = document.querySelectorAll('#sliderDots .dot');
    
    if (!track || slides.length <= 1) return;
    
    let currentIndex = 0;
    const totalSlides = slides.length;
    let autoPlayInterval = null;
    
    function goToSlide(index) {
        if (index < 0) index = totalSlides - 1;
        if (index >= totalSlides) index = 0;
        currentIndex = index;
        track.style.transform = `translateX(-${currentIndex * 100}%)`;
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === currentIndex);
        });
    }
    
    if (prevBtn) prevBtn.addEventListener('click', () => { goToSlide(currentIndex - 1); resetAutoplay(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { goToSlide(currentIndex + 1); resetAutoplay(); });
    
    dots.forEach((dot) => {
        dot.addEventListener('click', function() {
            const idx = parseInt(this.getAttribute('data-slide'));
            goToSlide(idx);
            resetAutoplay();
        });
    });
    
    function startAutoplay() {
        autoPlayInterval = setInterval(() => {
            goToSlide(currentIndex + 1);
        }, 5000);
    }
    
    function resetAutoplay() {
        if (autoPlayInterval) clearInterval(autoPlayInterval);
        startAutoplay();
    }
    
    startAutoplay();
});
</script>
@endpush
