@extends('tfc.layouts.app')

@section('title', tfc_setting('company_name', 'TFC Sales & Marketing') . ' | Connecting Products. Creating Possibilities.')
@section('meta_description', 'Official direct-selling enterprise for VANRITI Ayurvedic personal care and wellness products. Operating with transparent retail margins, zero joining fee, and strict consumer protection compliance.')

@section('content')
{{-- 1. Hero Image Banner Slider (Pure Images with Slider, No Text, No Button, Responsive Desktop & Mobile Picture Tags) --}}
@if ($banners->isNotEmpty())
<section class="tfc-hero-slider">
    <div id="tfcHeroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">
        @if ($banners->count() > 1)
            <div class="carousel-indicators">
                @foreach ($banners as $i => $banner)
                    <button type="button" data-bs-target="#tfcHeroCarousel" data-bs-slide-to="{{ $i }}" class="{{ $i === 0 ? 'active' : '' }}" aria-current="{{ $i === 0 ? 'true' : 'false' }}" aria-label="Slide {{ $i + 1 }}"></button>
                @endforeach
            </div>
        @endif

        <div class="carousel-inner">
            @foreach ($banners as $i => $banner)
                @php
                    $desktopImage = image_url($banner->image);
                    $mobileImage = $banner->mobile_image ? image_url($banner->mobile_image) : null;
                    $bannerAlt = $banner->title ?: (tfc_setting('company_name', 'TFC Sales & Marketing') . ' Banner ' . ($i + 1));
                    $bannerTarget = $banner->link ? (str_starts_with($banner->link, 'http') ? $banner->link : tfc_route(ltrim($banner->link, '/'))) : null;
                @endphp
                <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                    @if ($bannerTarget)
                        <a href="{{ $bannerTarget }}" class="d-block w-100">
                            <picture>
                                @if ($mobileImage)
                                    <source media="(max-width: 767.98px)" srcset="{{ $mobileImage }}">
                                @endif
                                <img src="{{ $desktopImage }}" alt="{{ $bannerAlt }}" class="tfc-hero-banner-img d-block w-100" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                            </picture>
                        </a>
                    @else
                        <picture>
                            @if ($mobileImage)
                                <source media="(max-width: 767.98px)" srcset="{{ $mobileImage }}">
                            @endif
                            <img src="{{ $desktopImage }}" alt="{{ $bannerAlt }}" class="tfc-hero-banner-img d-block w-100" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                        </picture>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($banners->count() > 1)
            <button class="carousel-control-prev" type="button" data-bs-target="#tfcHeroCarousel" data-bs-slide="prev" aria-label="Previous Slide">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#tfcHeroCarousel" data-bs-slide="next" aria-label="Next Slide">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        @endif
    </div>
</section>
@endif

{{-- 2. Trust and Business Principles Section --}}
<section class="py-5" style="background-color: var(--tfc-bg-soft); border-bottom: 1px solid var(--tfc-border);">
    <div class="container">
        <div class="row g-4">
            <div class="col-12 col-md-4">
                <div class="p-4 bg-white rounded-3 shadow-sm h-100 border border-light d-flex flex-column">
                    <div class="tfc-feature-icon"><i class="bi bi-shield-check"></i></div>
                    <h5 class="fw-bold mb-2 text-dark">Transparent Business Practices</h5>
                    <p class="text-muted small mb-0 flex-grow-1">
                        Operating strictly on product sales with zero enrollment fees, zero mandatory starter packs, and zero commissions for recruitment. Every direct seller earns solely through legitimate consumer sales.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-4 bg-white rounded-3 shadow-sm h-100 border border-light d-flex flex-column">
                    <div class="tfc-feature-icon"><i class="bi bi-patch-check"></i></div>
                    <h5 class="fw-bold mb-2 text-dark">Genuine Product Catalogue</h5>
                    <p class="text-muted small mb-0 flex-grow-1">
                        Direct commercial access to authentic, batch-tested VANRITI Ayurvedic formulations. All products feature transparent ingredient labeling, verified weights, and compliant retail packaging.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-4 bg-white rounded-3 shadow-sm h-100 border border-light d-flex flex-column">
                    <div class="tfc-feature-icon"><i class="bi bi-headset"></i></div>
                    <h5 class="fw-bold mb-2 text-dark">Consumer Support &amp; Redressal</h5>
                    <p class="text-muted small mb-0 flex-grow-1">
                        In adherence to Rule 7 of the Direct Selling Rules, 2021, our centralized redressal desk provides mandatory 48-hour acknowledgment and transparent 30-day ticket resolution tracking.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 3. Product Categories Section (Live from Database) --}}
@if ($categories->isNotEmpty())
<section class="py-5" style="background-color: #FFFFFF;">
    <div class="container py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <div class="tfc-section-eyebrow">Explore By Category</div>
                <h2 class="tfc-section-title mb-0">Ayurvedic Wellness Disciplines</h2>
            </div>
            <a href="{{ tfc_route('products') }}" class="btn btn-tfc-outline mt-3 mt-md-0">
                All Products <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            @foreach ($categories as $cat)
                <div class="col-12 col-md-4">
                    <div class="tfc-category-card">
                        <div class="tfc-category-img-wrap">
                            @if ($cat->image)
                                <img src="{{ image_url($cat->image) }}" alt="{{ $cat->name }}" class="tfc-category-img" loading="lazy">
                            @else
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light text-muted">
                                    <i class="bi bi-flower1 display-4 text-warning opacity-50"></i>
                                </div>
                            @endif
                            <div class="tfc-category-overlay"></div>
                            <span class="tfc-category-count">
                                {{ $cat->total_products_count ?? $cat->products_count }} Formulations
                            </span>
                        </div>
                        <div class="tfc-category-body">
                            <h5 class="fw-bold mb-2">
                                <a href="{{ tfc_route('category', $cat->slug) }}" class="text-dark text-decoration-none">
                                    {{ $cat->name }}
                                </a>
                            </h5>
                            @if ($cat->description)
                                <p class="text-muted small mb-3 text-truncate-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    {{ $cat->description }}
                                </p>
                            @else
                                <p class="text-muted small mb-3">
                                    Pure botanical formulations crafted according to time-tested Ayurvedic standards.
                                </p>
                            @endif

                            @if ($cat->children->isNotEmpty())
                                <div class="d-flex flex-wrap gap-1 mb-3">
                                    @foreach ($cat->children->take(3) as $child)
                                        <a href="{{ tfc_route('category', $child->slug) }}" class="badge bg-light text-dark text-decoration-none border px-2 py-1" style="font-weight: 500; font-size: 0.72rem;">
                                            {{ $child->name }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            <a href="{{ tfc_route('category', $cat->slug) }}" class="tfc-category-link">
                                Explore Range <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- 4. Featured Products Catalogue (Live Synced from Central Database) --}}
<section class="py-5" style="background-color: var(--tfc-bg-soft); border-top: 1px solid var(--tfc-border);">
    <div class="container py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <div class="tfc-section-eyebrow">VANRITI Botanical Formulations</div>
                <h2 class="tfc-section-title mb-0">Featured Products</h2>
            </div>
            <a href="{{ tfc_route('products') }}" class="btn btn-tfc-outline mt-3 mt-md-0">
                Explore Complete Catalogue <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-3 g-md-4">
            @forelse ($featuredProducts as $product)
                <div class="col-6 col-md-6 col-lg-3">
                    <div class="card tfc-card h-100">
                        <div class="position-relative overflow-hidden bg-white text-center">
                            @php
                                $img = $product->getPrimaryImage()?->image_path;
                                $discount = $product->discountPercent();
                            @endphp
                            <a href="{{ tfc_route('products.show', $product->slug) }}" class="d-block">
                                @if ($img)
                                    <img src="{{ image_url($img) }}" alt="{{ $product->name }}" class="tfc-product-img" loading="lazy">
                                @else
                                    <div class="tfc-product-img bg-light d-flex align-items-center justify-content-center text-muted">
                                        <i class="bi bi-image fs-1"></i>
                                    </div>
                                @endif
                            </a>

                            @if ($discount > 0)
                                <span class="badge bg-danger position-absolute top-0 start-0 m-2 px-2 py-1 shadow-sm" style="font-size: 0.72rem; font-weight: 600;">
                                    {{ round($discount) }}% OFF
                                </span>
                            @endif
                        </div>
                        <div class="card-body d-flex flex-column p-3 p-md-4">
                            <div class="small text-muted mb-1 text-truncate" style="font-size: 0.78rem;">
                                {{ $product->primaryCategory()?->name ?? 'Ayurvedic Wellness' }}
                            </div>
                            <h6 class="tfc-product-title mb-2">
                                <a href="{{ tfc_route('products.show', $product->slug) }}">
                                    {{ $product->name }}
                                </a>
                            </h6>
                            <div class="mt-auto pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <span class="tfc-price">{{ format_price($product->selling_price) }}</span>
                                    @if ($product->mrp && $product->mrp > $product->selling_price)
                                        <span class="tfc-mrp">{{ format_price($product->mrp) }}</span>
                                    @endif
                                </div>
                                <a href="{{ tfc_route('products.show', $product->slug) }}" class="btn btn-sm btn-tfc-primary">
                                    Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Products currently updating. Please explore our full catalogue.</p>
                    <a href="{{ tfc_route('products') }}" class="btn btn-tfc-navy">View Catalogue</a>
                </div>
            @endforelse
        </div>

        <div class="text-center mt-5">
            <a href="{{ tfc_route('products') }}" class="btn btn-tfc-navy btn-lg">
                <i class="bi bi-grid-3x3-gap me-2"></i>Explore Complete Catalogue ({{ \App\Models\Product::active()->count() }} Items)
            </a>
        </div>
    </div>
</section>

{{-- 5. Why TFC Section (6 Pillars of Responsible Direct Selling) --}}
<section class="tfc-why-section">
    <div class="container">
        <div class="text-center mb-5">
            <div class="tfc-section-eyebrow">Ethical Foundation</div>
            <h2 class="tfc-section-title">Why Partner with TFC Sales &amp; Marketing</h2>
            <p class="tfc-section-subtitle mx-auto">
                We are committed to building a sustainable, customer-first direct-selling ecosystem founded on authentic products, statutory transparency, and long-term relationships.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-box2-heart"></i></div>
                        <span class="tfc-why-number">01</span>
                    </div>
                    <h5 class="tfc-why-title">Product-Led Awareness</h5>
                    <p class="tfc-why-text">Every commercial activity starts and ends with genuine herbal formulations that deliver practical wellness value to households.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-shield-check"></i></div>
                        <span class="tfc-why-number">02</span>
                    </div>
                    <h5 class="tfc-why-title">Transparent Business Practices</h5>
                    <p class="tfc-why-text">Complete commercial clarity with zero enrollment fees, zero mandatory purchases, and transparent retail margin structures.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-people"></i></div>
                        <span class="tfc-why-number">03</span>
                    </div>
                    <h5 class="tfc-why-title">Lasting Customer Relationships</h5>
                    <p class="tfc-why-text">Direct selling grounded in honest product guidance, personal recommendation, and ongoing consumer satisfaction.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-book"></i></div>
                        <span class="tfc-why-number">04</span>
                    </div>
                    <h5 class="tfc-why-title">Continuous Product Education</h5>
                    <p class="tfc-why-text">Comprehensive training on VANRITI botanical formulations, ingredient sourcing, proper usage directions, and safety guidelines.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-journal-check"></i></div>
                        <span class="tfc-why-number">05</span>
                    </div>
                    <h5 class="tfc-why-title">Responsible Direct Selling</h5>
                    <p class="tfc-why-text">Strict adherence to the Consumer Protection (Direct Selling) Rules, 2021 &amp; 2023 Amendment. Zero recruitment-derived earnings.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-headset"></i></div>
                        <span class="tfc-why-number">06</span>
                    </div>
                    <h5 class="tfc-why-title">Dedicated Consumer Support</h5>
                    <p class="tfc-why-text">Active support infrastructure offering structured grievance redressal, prompt assistance, and transparent tracking.</p>
                </div>
            </div>
        </div>

        {{-- Bottom Statutory & Transparency Reassurance Strip --}}
        <div class="tfc-why-trust-bar">
            <div class="row align-items-center g-3">
                <div class="col-12 col-lg-8">
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                        <span class="tfc-why-trust-badge">
                            <i class="bi bi-patch-check-fill"></i> Statutory Guarantee
                        </span>
                        <strong class="text-dark small">Consumer Protection (Direct Selling) Rules, 2021 &amp; 2023 Compliant</strong>
                    </div>
                    <p class="text-muted small mb-0">
                        Zero joining fees. Zero mandatory kit purchases. Earnings are strictly generated through actual customer product consumption, never from mere recruitment.
                    </p>
                </div>
                <div class="col-12 col-lg-4 text-lg-end">
                    <a href="{{ tfc_route('direct-selling') }}" class="btn btn-outline-dark btn-sm fw-semibold me-2">
                        <i class="bi bi-info-circle me-1"></i> Business Model
                    </a>
                    <a href="{{ tfc_route('incentive-program') }}" class="btn btn-tfc-primary btn-sm fw-semibold">
                        <i class="bi bi-award me-1"></i> Incentive Plan
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 6. VANRITI Brand Partnership (Factual & Verified Heritage) --}}
<section class="py-5" style="background: linear-gradient(135deg, #0A1D37, #132D54); color: #FFFFFF;">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-12 col-lg-6">
                <span class="badge tfc-badge-gold mb-3">Authentic Brand Partnership</span>
                <h2 class="display-6 fw-bold mb-4">
                    Pure Botanical Formulations. <br>
                    <span style="color: var(--tfc-gold);">Crafted with Ayurvedic Integrity.</span>
                </h2>
                <p class="text-white-50 mb-4" style="line-height: 1.7;">
                    <strong>VANRITI</strong> combines traditional Indian botanical wisdom with rigorous quality standards. Formulated with pure plant actives, ethically sourced herbs, and transparent ingredient declarations, each product is designed to support natural wellness and personal care.
                </p>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-warning fs-5"></i>
                            <span class="fw-semibold">100% Herbal Actives</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-warning fs-5"></i>
                            <span class="fw-semibold">Zero Harmful Additives</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-warning fs-5"></i>
                            <span class="fw-semibold">Batch-Tested Quality</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-warning fs-5"></i>
                            <span class="fw-semibold">PAN India Customer Delivery</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ tfc_route('brand') }}" class="btn btn-tfc-primary">
                        Discover Brand Heritage <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                    <a href="{{ config('tfc.vanriti_store_url', 'https://vanriti.com') }}" target="_blank" rel="noopener" class="btn btn-outline-light">
                        Visit VANRITI Online Store <i class="bi bi-box-arrow-up-right ms-1"></i>
                    </a>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="p-4 p-md-5 rounded-4 shadow" style="background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px);">
                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <div class="p-3 bg-white bg-opacity-10 rounded-3 h-100">
                                <h3 class="fw-bold text-warning mb-1">100+</h3>
                                <small class="text-white-50">Active Formulations</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-white bg-opacity-10 rounded-3 h-100">
                                <h3 class="fw-bold text-warning mb-1">100%</h3>
                                <small class="text-white-50">Pure Herbal Actives</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-white bg-opacity-10 rounded-3 h-100">
                                <h3 class="fw-bold text-warning mb-1">PAN India</h3>
                                <small class="text-white-50">Reliable Order Dispatch</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-white bg-opacity-10 rounded-3 h-100">
                                <h3 class="fw-bold text-warning mb-1">Verified</h3>
                                <small class="text-white-50">Transparent Labeling</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-top border-white border-opacity-10 text-center">
                        <small class="text-white-50" style="font-size: 0.78rem;">
                            <i class="bi bi-info-circle me-1 text-warning"></i>All product formulations, batch testing, and manufacturing are directly governed by VANRITI.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 7. How Direct Selling Works (4-Step Compliant Flow) --}}
<section class="py-5" style="background-color: var(--tfc-bg-soft);">
    <div class="container py-4 text-center">
        <div class="tfc-section-eyebrow">Clear &bull; Product-Based &bull; Ethical</div>
        <h2 class="tfc-section-title mb-2">How Direct Selling Works with TFC</h2>
        <p class="text-muted mx-auto mb-5" style="max-width: 650px;">
            A straightforward commercial relationship built entirely around genuine wellness products, transparent retail margins, and customer satisfaction.
        </p>

        <div class="row g-4 text-start">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="tfc-step-card">
                    <span class="tfc-step-badge">Step 1</span>
                    <h5>Learn About TFC</h5>
                    <p>
                        Understand our direct-selling model, ethical code of conduct, and terms. Register as an independent direct seller with zero registration fee.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="tfc-step-card">
                    <span class="tfc-step-badge">Step 2</span>
                    <h5>Explore Products</h5>
                    <p>
                        Familiarize yourself with authentic VANRITI formulations, ingredients, usage directions, and legitimate benefits for consumers.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="tfc-step-card">
                    <span class="tfc-step-badge">Step 3</span>
                    <h5>Recommend Products</h5>
                    <p>
                        Ethically present and recommend genuine products to customers based on their actual wellness needs, with transparent product claims.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="tfc-step-card">
                    <span class="tfc-step-badge">Step 4</span>
                    <h5>Build Customer Trust</h5>
                    <p>
                        Earn lawful retail margins strictly derived from genuine consumer sales, supported by full customer rights and grievance support.
                    </p>
                </div>
            </div>
        </div>

        {{-- Explanatory Compliance Note --}}
        <div class="alert alert-light border mt-4 p-3 text-start mx-auto" style="max-width: 920px; font-size: 0.82rem; color: #4A5568;">
            <i class="bi bi-info-circle-fill text-primary me-2"></i>
            <strong>Important Notice:</strong> Direct selling earnings are solely based on the sale of genuine products to consumers. TFC does not offer guaranteed earnings, investment returns, or compensation for recruitment. Participation is voluntary with zero enrollment fee.
        </div>

        <div class="mt-4">
            <a href="{{ tfc_route('become-a-seller') }}" class="btn btn-tfc-navy btn-lg">
                <i class="bi bi-pencil-square me-2"></i>Apply to Become a Direct Seller
            </a>
        </div>
    </div>
</section>

{{-- 8. Consumer Grievance Redressal Portal Banner --}}
<section class="py-5" style="background-color: #FFFFFF;">
    <div class="container">
        <div class="tfc-grievance-banner">
            <div class="d-flex align-items-center gap-4">
                <div class="tfc-grievance-icon d-none d-sm-flex">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div>
                    <span class="badge bg-warning text-dark mb-2 px-2 py-1" style="font-size: 0.72rem; font-weight: 600;">
                        Rule 7 Compliance Desk
                    </span>
                    <h4 class="fw-bold mb-2 text-dark">Consumer Grievance &amp; Redressal Portal</h4>
                    <p class="text-muted mb-0 small" style="max-width: 620px; line-height: 1.6;">
                        Have a query, order concern, or feedback regarding a direct seller? Under Rule 7 of the Consumer Protection (Direct Selling) Rules, 2021, lodge your complaint online for mandatory 48-hour acknowledgment and transparent 30-day ticket resolution.
                    </p>
                </div>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2 text-nowrap mt-3 mt-lg-0">
                <a href="{{ tfc_route('grievance') }}" class="btn btn-tfc-primary">
                    <i class="bi bi-file-earmark-plus me-1"></i>File Grievance
                </a>
                <a href="{{ tfc_route('grievance.track') }}" class="btn btn-outline-dark">
                    <i class="bi bi-search me-1"></i>Track Ticket
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
