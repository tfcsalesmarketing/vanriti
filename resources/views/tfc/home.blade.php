@extends('tfc.layouts.app')

@section('title', tfc_setting('company_name', 'TFC Sales & Marketing') . ' | ' . tfc_setting('company_tagline', 'Connecting Products. Creating Possibilities.'))
@section('meta_description', 'TFC Sales & Marketing is a premier direct-selling enterprise in India offering high-performance wellness and personal care formulations with an equitable, rewarding business model.')

@section('content')
{{-- 1. Hero Image Banner Slider (Responsive Picture Tags) --}}
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
@else
{{-- Fallback Hero Section when no banners are configured --}}
<section class="py-5" style="background: linear-gradient(135deg, #061224 0%, #0A1D37 60%, #132D54 100%); color: #FFFFFF;">
    <div class="container py-5">
        <div class="row align-items-center g-5">
            <div class="col-12 col-lg-7">
                <span class="badge tfc-badge-gold mb-3">
                    <i class="bi bi-stars me-1"></i>Premier Direct Selling Enterprise
                </span>
                <h1 class="display-4 fw-bold mb-3" style="letter-spacing: -0.02em; line-height: 1.2;">
                    Empowering Entrepreneurs. <br>
                    <span style="color: var(--tfc-gold);">Transforming Lifestyles.</span>
                </h1>
                <p class="lead text-white-50 mb-4" style="max-width: 600px;">
                    Experience high-performance wellness formulations and build an independent direct selling business backed by world-class products, transparent rewards, and proven leadership mentorship.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('tfc.preview.member.register') }}" class="btn btn-tfc-primary btn-lg">
                        <i class="bi bi-person-plus-fill me-2"></i>Join TFC Network
                    </a>
                    <a href="{{ tfc_route('products') }}" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-grid-fill me-2"></i>Explore Products
                    </a>
                </div>
            </div>
            <div class="col-12 col-lg-5">
                <div class="p-4 p-md-5 rounded-4 shadow-lg text-center" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.15); backdrop-filter: blur(12px);">
                    <img src="{{ tfc_logo_url() }}" alt="TFC Logo" class="img-fluid rounded mb-4" style="max-height: 80px; background: #FFFFFF; padding: 10px 18px;">
                    <h5 class="fw-bold text-white mb-2">Build Your Independent Enterprise</h5>
                    <p class="text-white-50 small mb-4">Zero enrollment barrier. Equal opportunity. Unlimited growth.</p>
                    <div class="row g-2 text-start text-white-50 small">
                        <div class="col-6 d-flex align-items-center gap-2">
                            <i class="bi bi-check2-circle text-warning fs-5"></i> Authentic Care
                        </div>
                        <div class="col-6 d-flex align-items-center gap-2">
                            <i class="bi bi-check2-circle text-warning fs-5"></i> Binary Rewards
                        </div>
                        <div class="col-6 d-flex align-items-center gap-2">
                            <i class="bi bi-check2-circle text-warning fs-5"></i> Weekly Payouts
                        </div>
                        <div class="col-6 d-flex align-items-center gap-2">
                            <i class="bi bi-check2-circle text-warning fs-5"></i> Executive Mentorship
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- 2. Enterprise Core Pillars (Replaces defensive disclaimers) --}}
<section class="py-5" style="background-color: var(--tfc-bg-soft); border-bottom: 1px solid var(--tfc-border);">
    <div class="container">
        <div class="row g-4">
            <div class="col-12 col-md-4">
                <div class="p-4 bg-white rounded-3 shadow-sm h-100 border border-light d-flex flex-column">
                    <div class="tfc-feature-icon"><i class="bi bi-flower1"></i></div>
                    <h5 class="fw-bold mb-2 text-dark">Premium Botanical Formulations</h5>
                    <p class="text-muted small mb-0 flex-grow-1">
                        High-potency botanical extracts and clean wellness essentials formulated for noticeable vitality, personal care, and daily consumer wellness.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-4 bg-white rounded-3 shadow-sm h-100 border border-light d-flex flex-column">
                    <div class="tfc-feature-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    <h5 class="fw-bold mb-2 text-dark">Rewarding Binary Synergy</h5>
                    <p class="text-muted small mb-0 flex-grow-1">
                        An equitable dual-team incentive architecture offering retail margins, binary matching bonuses, leadership rank rewards, and sustainable business volume.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-4 bg-white rounded-3 shadow-sm h-100 border border-light d-flex flex-column">
                    <div class="tfc-feature-icon"><i class="bi bi-mortarboard"></i></div>
                    <h5 class="fw-bold mb-2 text-dark">Executive Training &amp; Mentorship</h5>
                    <p class="text-muted small mb-0 flex-grow-1">
                        Comprehensive distributor enablement including digital back-office tools, product webinars, and hands-on leadership coaching to accelerate your success.
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
                <div class="tfc-section-eyebrow">Explore Collections</div>
                <h2 class="tfc-section-title mb-1">Curated Wellness &amp; Lifestyle Categories</h2>
                <p class="text-muted small mb-0" style="max-width: 620px;">
                    Discover pure botanical formulations, targeted wellness supplements, and personal care solutions designed for modern living.
                </p>
            </div>
            <a href="{{ tfc_route('products') }}" class="btn btn-tfc-outline mt-3 mt-md-0">
                All Products <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            @foreach ($categories as $cat)
                @php
                    $prodCount = $cat->total_products_count ?? $cat->products_count ?? 0;
                    $colClass = $categories->count() >= 4 ? 'col-12 col-sm-6 col-lg-3' : 'col-12 col-md-6 col-lg-4';
                @endphp
                <div class="{{ $colClass }}">
                    <div class="tfc-category-card">
                        <a href="{{ tfc_route('category', $cat->slug) }}" class="tfc-category-img-wrap" aria-label="{{ $cat->name }}">
                            @if ($cat->image)
                                <img src="{{ image_url($cat->image) }}" alt="{{ $cat->name }}" class="tfc-category-img" loading="lazy">
                            @else
                                <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center bg-light text-muted p-4 text-center">
                                    <i class="bi bi-flower1 display-3 text-warning opacity-75 mb-2"></i>
                                    <span class="small fw-semibold text-secondary">Botanical Care</span>
                                </div>
                            @endif
                            <div class="tfc-category-overlay"></div>
                            <span class="tfc-category-tag-discipline">
                                <i class="bi bi-stars me-1"></i> Premium Line
                            </span>
                            <span class="tfc-category-count-badge">
                                <i class="bi bi-box-seam text-warning"></i> {{ $prodCount }} {{ \Illuminate\Support\Str::plural('Product', $prodCount) }}
                            </span>
                        </a>

                        <div class="tfc-category-body">
                            <h5 class="tfc-category-title">
                                <a href="{{ tfc_route('category', $cat->slug) }}">
                                    {{ $cat->name }}
                                </a>
                            </h5>
                            @if ($cat->description)
                                <p class="tfc-category-desc" title="{{ $cat->description }}">
                                    {{ $cat->description }}
                                </p>
                            @else
                                <p class="tfc-category-desc">
                                    High-potency botanical care crafted to support daily vitality and radiant health.
                                </p>
                            @endif

                            <div class="tfc-category-subs">
                                @if ($cat->children->isNotEmpty())
                                    @foreach ($cat->children->take(3) as $child)
                                        <a href="{{ tfc_route('category', $child->slug) }}" class="tfc-category-sub-pill">
                                            {{ $child->name }}
                                        </a>
                                    @endforeach
                                @else
                                    <span class="badge bg-light text-muted border px-2 py-1" style="font-weight: 500; font-size: 0.74rem;">
                                        <i class="bi bi-patch-check text-success me-1"></i> Quality Tested Formulations
                                    </span>
                                @endif
                            </div>

                            <div class="tfc-category-footer">
                                <a href="{{ tfc_route('category', $cat->slug) }}" class="tfc-category-link">
                                    Explore Range <i class="bi bi-arrow-right"></i>
                                </a>
                                <a href="{{ tfc_route('category', $cat->slug) }}" class="tfc-category-circle-btn" aria-label="Explore {{ $cat->name }}">
                                    <i class="bi bi-arrow-up-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- 4. Featured Products Catalogue (Live from Database) --}}
<section class="py-5" style="background-color: var(--tfc-bg-soft); border-top: 1px solid var(--tfc-border);">
    <div class="container py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <div class="tfc-section-eyebrow">High-Performance Formulations</div>
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
                                {{ $product->primaryCategory()?->name ?? 'Wellness & Care' }}
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
                <i class="bi bi-grid-3x3-gap me-2"></i>Explore Complete Catalogue ({{ \App\Models\Product::active()->count() }} Formulations)
            </a>
        </div>
    </div>
</section>

{{-- 5. The TFC Direct Selling Advantage (6 Inspiring Pillars) --}}
<section class="tfc-why-section">
    <div class="container">
        <div class="text-center mb-5">
            <div class="tfc-section-eyebrow">The Business Advantage</div>
            <h2 class="tfc-section-title">Why Partner with TFC Sales &amp; Marketing</h2>
            <p class="tfc-section-subtitle mx-auto">
                We provide independent entrepreneurs with an empowering platform, high-demand wellness formulations, and a scalable compensation plan built for long-term prosperity.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-box2-heart"></i></div>
                        <span class="tfc-why-number">01</span>
                    </div>
                    <h5 class="tfc-why-title">High-Demand Product Portfolio</h5>
                    <p class="tfc-why-text">Every commercial interaction is anchored by daily consumable wellness, hair care, and skincare formulations that customers love, trust, and regularly re-order.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-diagram-3"></i></div>
                        <span class="tfc-why-number">02</span>
                    </div>
                    <h5 class="tfc-why-title">Equitable Binary Synergy</h5>
                    <p class="tfc-why-text">Our dual-team binary business model harnesses collective team momentum, enabling distributors to benefit from depth volume, pair matching, and executive rank bonuses.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-cash-stack"></i></div>
                        <span class="tfc-why-number">03</span>
                    </div>
                    <h5 class="tfc-why-title">Retail Profits &amp; Direct Margins</h5>
                    <p class="tfc-why-text">Enjoy generous upfront retail margins when sharing products with customers, complemented by recurring organizational performance incentives.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-laptop"></i></div>
                        <span class="tfc-why-number">04</span>
                    </div>
                    <h5 class="tfc-why-title">Digital Back-Office &amp; Tools</h5>
                    <p class="tfc-why-text">Access a dedicated digital member portal featuring real-time binary tree visualization, team tracking, automated commission statements, and instant KYC management.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-award"></i></div>
                        <span class="tfc-why-number">05</span>
                    </div>
                    <h5 class="tfc-why-title">Leadership Academy &amp; Mentorship</h5>
                    <p class="tfc-why-text">Continuous learning through professional product training, business strategy webinars, and executive guidance from top industry leaders.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-truck"></i></div>
                        <span class="tfc-why-number">06</span>
                    </div>
                    <h5 class="tfc-why-title">Seamless Nationwide Logistics</h5>
                    <p class="tfc-why-text">Robust Pan-India warehouse and dispatch network ensuring rapid, reliable delivery directly to your retail customers' doorsteps.</p>
                </div>
            </div>
        </div>

        {{-- Inspiring Action Strip (Replacing defensive compliance box) --}}
        <div class="tfc-why-trust-bar">
            <div class="row align-items-center g-3">
                <div class="col-12 col-lg-8">
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                        <span class="tfc-why-trust-badge">
                            <i class="bi bi-trophy-fill"></i> Financial Freedom
                        </span>
                        <strong class="text-dark">Build a Scalable Business on Your Own Terms</strong>
                    </div>
                    <p class="text-muted small mb-0">
                        Zero joining fee. No forced inventory kits. Your earnings grow directly with your genuine customer retail reach and leadership leadership excellence.
                    </p>
                </div>
                <div class="col-12 col-lg-4 text-lg-end">
                    <a href="{{ route('tfc.preview.member.register') }}" class="btn btn-tfc-primary btn-sm fw-semibold me-2">
                        <i class="bi bi-person-plus-fill me-1"></i> Register Free
                    </a>
                    <a href="{{ tfc_route('incentive-program') }}" class="btn btn-outline-dark btn-sm fw-semibold">
                        <i class="bi bi-award me-1"></i> Compensation Plan
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 6. Formulation Excellence & Product Quality --}}
<section class="py-5" style="background: linear-gradient(135deg, #0A1D37, #132D54); color: #FFFFFF;">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-12 col-lg-6">
                <span class="badge tfc-badge-gold mb-3">Formulation Excellence</span>
                <h2 class="display-6 fw-bold mb-4">
                    Pure Botanical Formulations. <br>
                    <span style="color: var(--tfc-gold);">Crafted with Scientific Rigor.</span>
                </h2>
                <p class="text-white-50 mb-4" style="line-height: 1.7;">
                    At TFC Sales &amp; Marketing, our product philosophy combines ancient botanical wisdom with contemporary laboratory precision. Every formulation is crafted using concentrated herbal bio-actives, transparent ingredient declarations, and strict batch hygiene protocols to deliver proven wellness results.
                </p>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-warning fs-5"></i>
                            <span class="fw-semibold">100% Botanical Actives</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-warning fs-5"></i>
                            <span class="fw-semibold">No Harsh Synthetics</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-warning fs-5"></i>
                            <span class="fw-semibold">GMP &amp; ISO Quality Standards</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill text-warning fs-5"></i>
                            <span class="fw-semibold">Pan-India Express Dispatch</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ tfc_route('products') }}" class="btn btn-tfc-primary">
                        Explore Formulations <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                    <a href="{{ tfc_route('brand') }}" class="btn btn-outline-light">
                        Product Standards &bull; Learn More
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
                                <small class="text-white-50">Standardized Extracts</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-white bg-opacity-10 rounded-3 h-100">
                                <h3 class="fw-bold text-warning mb-1">Pan-India</h3>
                                <small class="text-white-50">Direct Delivery</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-white bg-opacity-10 rounded-3 h-100">
                                <h3 class="fw-bold text-warning mb-1">Certified</h3>
                                <small class="text-white-50">Batch Purity</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-top border-white border-opacity-10 text-center">
                        <small class="text-white-50" style="font-size: 0.8rem;">
                            <i class="bi bi-shield-check me-1 text-warning"></i>Rigorous laboratory testing for stability, safety, and therapeutic consistency.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- 7. How Direct Selling Works (4-Step Entrepreneur Journey) --}}
<section class="py-5" style="background-color: var(--tfc-bg-soft);">
    <div class="container py-4 text-center">
        <div class="tfc-section-eyebrow">Simple &bull; Lucrative &bull; Scalable</div>
        <h2 class="tfc-section-title mb-2">How It Works with TFC Sales &amp; Marketing</h2>
        <p class="text-muted mx-auto mb-5" style="max-width: 650px;">
            A proven path designed to help you launch, build, and scale a lucrative direct-selling business with complete confidence.
        </p>

        <div class="row g-4 text-start">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="tfc-step-card">
                    <span class="tfc-step-badge">Step 1</span>
                    <h5>Register for Free</h5>
                    <p>
                        Sign up with your verified sponsor referral, receive your unique TFC Member ID, and gain immediate access to your distributor portal.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="tfc-step-card">
                    <span class="tfc-step-badge">Step 2</span>
                    <h5>Experience Products</h5>
                    <p>
                        Discover our botanical wellness formulations, explore ingredients and usage benefits, and identify the solutions you are passionate about.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="tfc-step-card">
                    <span class="tfc-step-badge">Step 3</span>
                    <h5>Share &amp; Retail</h5>
                    <p>
                        Recommend genuine formulations to your network, fulfill customer orders through our Pan-India delivery, and earn immediate retail margins.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="tfc-step-card">
                    <span class="tfc-step-badge">Step 4</span>
                    <h5>Build Your Team</h5>
                    <p>
                        Coach and empower new leaders on your Left and Right binary teams, accumulating organizational volume and qualifying for executive bonuses.
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-5">
            <a href="{{ route('tfc.preview.member.register') }}" class="btn btn-tfc-primary btn-lg shadow-sm">
                <i class="bi bi-rocket-takeoff me-2"></i>Launch Your Business Today
            </a>
        </div>
    </div>
</section>

{{-- 8. High-Converting Call to Action Banner (Replaces Rule 7 Banner) --}}
<section class="py-5" style="background-color: #FFFFFF;">
    <div class="container">
        <div class="tfc-grievance-banner" style="background: linear-gradient(135deg, #0A1D37, #132D54); color: #FFFFFF; border: none; padding: 2.5rem; border-radius: 16px;">
            <div class="d-flex align-items-center gap-4">
                <div class="tfc-grievance-icon d-none d-sm-flex" style="background: rgba(212, 155, 36, 0.2); color: var(--tfc-gold);">
                    <i class="bi bi-trophy"></i>
                </div>
                <div>
                    <span class="badge tfc-badge-gold mb-2 px-2.5 py-1" style="font-size: 0.74rem;">
                        Entrepreneurial Freedom
                    </span>
                    <h3 class="fw-bold mb-2 text-white">Ready to Build Your Legacy with TFC?</h3>
                    <p class="text-white-50 mb-0 small" style="max-width: 650px; line-height: 1.6;">
                        Step into financial independence with an ethical, product-centered direct selling company. Gain access to premium formulations, automated business tracking, and a community of supportive leaders.
                    </p>
                </div>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2 text-nowrap mt-3 mt-lg-0">
                <a href="{{ route('tfc.preview.member.register') }}" class="btn btn-tfc-primary btn-lg">
                    <i class="bi bi-person-plus-fill me-1"></i>Join Now Free
                </a>
                <a href="{{ tfc_route('contact') }}" class="btn btn-outline-light btn-lg">
                    <i class="bi bi-chat-dots me-1"></i>Talk with Us
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
