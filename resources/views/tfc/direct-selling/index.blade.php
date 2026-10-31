@extends('tfc.layouts.app')

@section('title', tfc_setting('direct_selling_meta_title', 'Discover Direct Selling with TFC Sales & Marketing | ' . tfc_setting('company_name', 'TFC Sales & Marketing')))
@section('meta_description', 'Discover transparent, product-led direct selling with TFC Sales & Marketing. Learn how independent direct sellers recommend genuine wellness formulations with zero joining fee.')

@section('content')
{{-- Section 1: Hero --}}
<section class="py-5" style="background: linear-gradient(135deg, #061224 0%, #0A1D37 60%, #132D54 100%); color: #FFFFFF;">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-12 col-lg-7">
                <span class="badge tfc-badge-gold mb-3">
                    <i class="bi bi-stars me-1"></i>Official Direct-Selling Enterprise &bull; Zero Registration Fee
                </span>
                <h1 class="display-4 fw-bold mb-3" style="letter-spacing: -0.02em; line-height: 1.2;">
                    More Than Products. <br>
                    <span style="color: var(--tfc-gold);">A Business Built on Relationships.</span>
                </h1>
                <p class="lead text-white-50 mb-3" style="max-width: 620px;">
                    Discover a More Rewarding Way to Experience Wellness and Entrepreneurship.
                </p>
                <p class="text-white-50 mb-4" style="max-width: 620px; line-height: 1.7;">
                    TFC Sales &amp; Marketing connects consumers and independent direct sellers with a curated portfolio of herbal, wellness, and personal care products through an empowering, product-centered direct-selling approach.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ tfc_route('products') }}" class="btn btn-tfc-primary btn-lg">
                        <i class="bi bi-grid-fill me-2"></i>Explore Products
                    </a>
                    <a href="{{ tfc_route('incentive-program') }}" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-journal-text me-2"></i>Explore the TFC Opportunity
                    </a>
                </div>
            </div>
            <div class="col-12 col-lg-5">
                <div class="p-4 rounded-4 shadow-lg" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.15); backdrop-filter: blur(12px);">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="p-3 rounded-circle bg-warning bg-opacity-20 text-warning fs-3">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <h6 class="text-white fw-bold mb-0">Ethical Direct Selling</h6>
                            <small class="text-white-50">Consumer Protection (Direct Selling) Rules, 2021</small>
                        </div>
                    </div>
                    <ul class="list-unstyled text-white-50 small mb-0 d-flex flex-column gap-2">
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-check2-circle text-warning fs-5"></i>
                            <span><strong>Zero Registration Fee:</strong> Free enrollment without forced purchases.</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-check2-circle text-warning fs-5"></i>
                            <span><strong>Authentic Formulations:</strong> Lab-tested botanical care crafted to high standards.</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-check2-circle text-warning fs-5"></i>
                            <span><strong>Direct Customer Delivery:</strong> Fast, trackable PAN-India fulfillment.</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-check2-circle text-warning fs-5"></i>
                            <span><strong>Rule 7 Redressal:</strong> 48-hour complaint acknowledgment guarantee.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Section 2: What Is Direct Selling? --}}
<section class="py-5" style="background-color: #FFFFFF;">
    <div class="container py-3">
        <div class="text-center mb-5">
            <div class="tfc-section-eyebrow">Commerce Through Community</div>
            <h2 class="tfc-section-title">What Is Direct Selling?</h2>
            <p class="text-muted mx-auto" style="max-width: 700px;">
                Direct selling is a legitimate, recognized retail method that moves products directly from the manufacturer to the consumer through independent direct sellers.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="p-4 bg-light rounded-3 h-100 border border-light text-center">
                    <div class="tfc-feature-icon mx-auto mb-3"><i class="bi bi-person-lines-fill"></i></div>
                    <h5 class="fw-bold mb-2">Personal Demonstration</h5>
                    <p class="text-muted small mb-0">
                        Independent sellers personally introduce products, answer questions, explain herbal ingredients, and provide helpful usage instructions.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="p-4 bg-light rounded-3 h-100 border border-light text-center">
                    <div class="tfc-feature-icon mx-auto mb-3"><i class="bi bi-lightbulb"></i></div>
                    <h5 class="fw-bold mb-2">Informed Decisions</h5>
                    <p class="text-muted small mb-0">
                        Customers receive attentive, personalized guidance rather than navigating crowded supermarket shelves or impersonal algorithms alone.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="p-4 bg-light rounded-3 h-100 border border-light text-center">
                    <div class="tfc-feature-icon mx-auto mb-3"><i class="bi bi-bag-check-fill"></i></div>
                    <h5 class="fw-bold mb-2">Real Customer Demand</h5>
                    <p class="text-muted small mb-0">
                        The entire business is driven by genuine consumer need for daily wellness and natural personal care formulations that deliver genuine satisfaction.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="p-4 bg-light rounded-3 h-100 border border-light text-center">
                    <div class="tfc-feature-icon mx-auto mb-3"><i class="bi bi-shield-x"></i></div>
                    <h5 class="fw-bold mb-2">No Recruitment Substitutes</h5>
                    <p class="text-muted small mb-0">
                        Recruitment is never a substitute for product retailing. Neither TFC nor its sellers receive compensation for merely registering other individuals.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Section 3: How TFC Works (5 Practical Steps) --}}
<section class="py-5" style="background-color: var(--tfc-bg-soft); border-top: 1px solid var(--tfc-border); border-bottom: 1px solid var(--tfc-border);">
    <div class="container py-3">
        <div class="text-center mb-5">
            <div class="tfc-section-eyebrow">Five Step Journey</div>
            <h2 class="tfc-section-title">How Direct Selling Works with TFC</h2>
            <p class="text-muted mx-auto" style="max-width: 650px;">
                A simple, ethical, and product-focused path designed to empower everyday entrepreneurs across India.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-step-card h-100">
                    <span class="tfc-step-badge">Step 1</span>
                    <h5 class="fw-bold mb-2">Free Registration</h5>
                    <p class="text-muted small mb-0">
                        Interested individuals apply to register as prospective independent direct sellers without paying any enrollment, registration, training, or joining fee.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-step-card h-100">
                    <span class="tfc-step-badge">Step 2</span>
                    <h5 class="fw-bold mb-2">Explore Products</h5>
                    <p class="text-muted small mb-0">
                        Explore authentic TFC formulations, complete botanical ingredient disclosures, laboratory testing reports, safety precautions, and usage guidelines.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-step-card h-100">
                    <span class="tfc-step-badge">Step 3</span>
                    <h5 class="fw-bold mb-2">Product Purchase</h5>
                    <p class="text-muted small mb-0">
                        Members purchase genuine products for personal consumption or to fulfill verified consumer orders according to published business policies.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-6 offset-lg-2 col-lg-4">
                <div class="tfc-step-card h-100">
                    <span class="tfc-step-badge">Step 4</span>
                    <h5 class="fw-bold mb-2">Recommend Products</h5>
                    <p class="text-muted small mb-0">
                        Share truthful, accurate product information with interested customers and help them select natural remedies suited to their personal wellness needs.
                    </p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-step-card h-100">
                    <span class="tfc-step-badge">Step 5</span>
                    <h5 class="fw-bold mb-2">Build Customer Trust</h5>
                    <p class="text-muted small mb-0">
                        Focus on ongoing customer satisfaction, responsible consultations, and natural repeat reorders. Earn transparent retail margins from genuine sales.
                    </p>
                </div>
            </div>
        </div>

        <div class="alert alert-light border mt-4 p-3 text-start mx-auto shadow-sm" style="max-width: 860px; font-size: 0.82rem; color: #4A5568;">
            <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
            <strong>Important Income Clarification:</strong> Registration as an independent direct seller does not guarantee income or any financial benefit. Earnings are derived solely from legitimate retail product sales to consumers, backed by full consumer rights and cooling-off guarantees.
        </div>
    </div>
</section>

{{-- Section 4: Live Product Catalogue Integration --}}
<section class="py-5" style="background-color: #FFFFFF;">
    <div class="container py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <div class="tfc-section-eyebrow">Our Botanical Formulations</div>
                <h2 class="tfc-section-title mb-0">Product Showcase</h2>
                <p class="text-muted small mb-0 mt-1">Authentic Ayurvedic, Herbal &amp; Natural Personal Care Formulations</p>
            </div>
            <a href="{{ tfc_route('products') }}" class="btn btn-tfc-outline mt-3 mt-md-0">
                Explore Full Catalogue <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        {{-- Categories Ribbon --}}
        @if ($rootCategories->isNotEmpty())
            <div class="row g-3 mb-5">
                @foreach ($rootCategories as $cat)
                    <div class="col-12 col-md-4">
                        <a href="{{ tfc_route('category', $cat->slug) }}" class="p-3 rounded-3 border d-flex align-items-center gap-3 text-decoration-none bg-light hover-shadow transition">
                            <div class="p-2 rounded-circle bg-white shadow-sm text-warning fs-3">
                                <i class="bi bi-flower1"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0">{{ $cat->name }}</h6>
                                <small class="text-muted">{{ $cat->children->count() }} Sub-Disciplines</small>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Real Live Products --}}
        <div class="row g-3 g-md-4">
            @foreach ($showcaseProducts as $product)
                @php
                    $img = $product->getPrimaryImage()?->image_path;
                    $discount = $product->discountPercent();
                @endphp
                <div class="col-6 col-md-6 col-lg-3">
                    <div class="card tfc-card h-100">
                        <div class="position-relative overflow-hidden bg-white text-center">
                            <a href="{{ tfc_route('products.show', $product->slug) }}">
                                @if ($img)
                                    <img src="{{ image_url($img) }}" alt="{{ $product->name }}" class="tfc-product-img" loading="lazy">
                                @else
                                    <div class="tfc-product-img bg-light d-flex align-items-center justify-content-center text-muted">
                                        <i class="bi bi-image fs-1"></i>
                                    </div>
                                @endif
                            </a>
                            @if ($discount > 0)
                                <span class="badge bg-danger position-absolute top-0 start-0 m-2 px-2 py-1 shadow-sm" style="font-size: 0.72rem;">
                                    {{ round($discount) }}% OFF
                                </span>
                            @endif
                        </div>
                        <div class="card-body d-flex flex-column p-3 p-md-4">
                            <span class="small text-muted mb-1 text-truncate" style="font-size: 0.76rem;">
                                {{ $product->primaryCategory()?->name ?? 'Ayurvedic Care' }}
                            </span>
                            <h6 class="tfc-product-title mb-2">
                                <a href="{{ tfc_route('products.show', $product->slug) }}">
                                    {{ $product->name }}
                                </a>
                            </h6>
                            @if ($product->short_description)
                                <p class="text-muted small mb-3 text-truncate-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: 0.78rem;">
                                    {{ strip_tags($product->short_description) }}
                                </p>
                            @endif
                            <div class="mt-auto pt-3 border-top">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="tfc-price">{{ format_price($product->selling_price) }}</span>
                                    @if ($product->mrp && $product->mrp > $product->selling_price)
                                        <span class="tfc-mrp">{{ format_price($product->mrp) }}</span>
                                    @endif
                                </div>
                                <div class="d-grid gap-1">
                                    <a href="{{ tfc_route('products.show', $product->slug) }}" class="btn btn-sm btn-outline-dark">
                                        View Details
                                    </a>
                                    <a href="{{ tfc_vanriti_product_url($product->slug) }}" target="_blank" rel="noopener" class="btn btn-sm btn-tfc-primary">
                                        Order Online <i class="bi bi-box-arrow-up-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Section 5: Why TFC? (7 Core Business Principles) --}}
<section class="py-5" style="background-color: var(--tfc-bg-soft); border-top: 1px solid var(--tfc-border);">
    <div class="container py-3">
        <div class="text-center mb-5">
            <div class="tfc-section-eyebrow">Our Guiding Pillars</div>
            <h2 class="tfc-section-title">Why Partner with TFC Sales &amp; Marketing?</h2>
            <p class="text-muted mx-auto" style="max-width: 680px;">
                We stand for responsible direct selling built upon statutory transparency, verifiable wellness formulations, and enduring customer respect.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-box2-heart"></i></div>
                        <span class="tfc-why-number">01</span>
                    </div>
                    <h5 class="tfc-why-title">Product-Focused Business</h5>
                    <p class="tfc-why-text">Every transaction is anchored in authentic botanical formulations designed for real, daily consumer use.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-file-earmark-lock"></i></div>
                        <span class="tfc-why-number">02</span>
                    </div>
                    <h5 class="tfc-why-title">Transparent Policies</h5>
                    <p class="tfc-why-text">Clear, documented distributor terms with zero hidden fees, zero recruitment incentives, and straightforward retail margins.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-people"></i></div>
                        <span class="tfc-why-number">03</span>
                    </div>
                    <h5 class="tfc-why-title">Genuine Relationships</h5>
                    <p class="tfc-why-text">We prioritize long-term customer satisfaction and honest product recommendations over transactional high-pressure sales.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-mortarboard"></i></div>
                        <span class="tfc-why-number">04</span>
                    </div>
                    <h5 class="tfc-why-title">Comprehensive Product Education</h5>
                    <p class="tfc-why-text">Ongoing educational resources covering herbal properties, safe application methods, precautions, and authentic benefits.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-journal-check"></i></div>
                        <span class="tfc-why-number">05</span>
                    </div>
                    <h5 class="tfc-why-title">Responsible Direct Selling</h5>
                    <p class="tfc-why-text">Full compliance with Indian Consumer Protection regulations. Pyramids and money circulation schemes are strictly prohibited.</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="tfc-why-card">
                    <div class="tfc-why-header">
                        <div class="tfc-why-icon"><i class="bi bi-headset"></i></div>
                        <span class="tfc-why-number">06</span>
                    </div>
                    <h5 class="tfc-why-title">Dedicated Grievance Support</h5>
                    <p class="tfc-why-text">Statutory redressal officer desk with 48-hour mandatory ticket acknowledgment and transparent 30-day resolution tracking.</p>
                </div>
            </div>
            <div class="col-12">
                <div class="tfc-why-card text-center align-items-center">
                    <div class="tfc-why-icon mb-3"><i class="bi bi-info-circle"></i></div>
                    <h5 class="tfc-why-title">Clear Business Information</h5>
                    <p class="tfc-why-text mx-auto" style="max-width: 780px;">
                        Transparent disclosures regarding Product Credits, product retail prices, order tracking, and terms. No unrealistic promises or unverified testimonials.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Section 6: Who Can Explore This Opportunity? --}}
<section class="py-5" style="background-color: #FFFFFF;">
    <div class="container py-3">
        <div class="row align-items-center g-5">
            <div class="col-12 col-lg-6">
                <div class="tfc-section-eyebrow">Participation Profile</div>
                <h2 class="tfc-section-title">Who Can Explore This Opportunity?</h2>
                <p class="text-muted mb-4">
                    The TFC direct-selling model is open to any adult Indian citizen interested in natural wellness formulations, honest customer communication, and independent micro-entrepreneurship.
                </p>
                <div class="d-flex flex-column gap-3 mb-4">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-check-circle-fill text-warning fs-5"></i>
                        <div>
                            <strong class="d-block text-dark">Individuals Passionate About Natural Living</strong>
                            <small class="text-muted">People who believe in pure botanical ingredients and want to share authentic wellness with their local community.</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-check-circle-fill text-warning fs-5"></i>
                        <div>
                            <strong class="d-block text-dark">Relationship-Driven Communicators</strong>
                            <small class="text-muted">Individuals who take pride in understanding people's personal care needs and recommending tailored herbal remedies.</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-check-circle-fill text-warning fs-5"></i>
                        <div>
                            <strong class="d-block text-dark">Independent Learners</strong>
                            <small class="text-muted">Entrepreneurs committed to learning product compositions, ethical guidelines, and customer service skills.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="p-4 p-md-5 rounded-4 border bg-light shadow-sm">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shield-shaded text-warning me-2"></i>Statutory Eligibility Notice</h5>
                    <p class="text-muted small mb-3">
                        Participation in TFC's direct-selling activity does not constitute an offer of employment, partnership, or guaranteed income.
                    </p>
                    <p class="text-muted small mb-3">
                        Actual results depend entirely upon genuine product sales, individual dedication, customer demand, local market conditions, and compliance with the approved TFC business code of conduct.
                    </p>
                    <div class="p-3 bg-white rounded-3 border small text-muted">
                        <strong class="text-dark d-block mb-1">Registration Requirements:</strong>
                        Must be at least 18 years of age, hold valid Indian KYC identification, and agree to uphold all statutory consumer protection obligations.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Section 7: Business Transparency & Governance --}}
<section class="py-5" style="background-color: var(--tfc-bg-soft); border-top: 1px solid var(--tfc-border); border-bottom: 1px solid var(--tfc-border);">
    <div class="container py-3">
        <div class="text-center mb-5">
            <div class="tfc-section-eyebrow">Governance &amp; Statutory Compliance</div>
            <h2 class="tfc-section-title">Business Transparency Policies</h2>
            <p class="text-muted mx-auto" style="max-width: 650px;">
                Review our comprehensive legal declarations, consumer rights policies, and direct selling codes of conduct.
            </p>
        </div>

        <div class="row g-3">
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ tfc_route('policy', 'direct-seller-agreement') }}" class="p-3 bg-white rounded-3 border d-block text-decoration-none text-dark hover-shadow transition h-100">
                    <i class="bi bi-file-earmark-text text-warning fs-4 d-block mb-2"></i>
                    <h6 class="fw-bold mb-1" style="font-size: 0.88rem;">Code of Conduct</h6>
                    <small class="text-muted" style="font-size: 0.75rem;">Distributor ethics and guidelines</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ tfc_route('policy', 'statutory-disclosures') }}" class="p-3 bg-white rounded-3 border d-block text-decoration-none text-dark hover-shadow transition h-100">
                    <i class="bi bi-shield-check text-warning fs-4 d-block mb-2"></i>
                    <h6 class="fw-bold mb-1" style="font-size: 0.88rem;">Direct Selling Policy</h6>
                    <small class="text-muted" style="font-size: 0.75rem;">Rule 5 statutory disclosures</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ tfc_route('policy', 'terms-and-conditions') }}" class="p-3 bg-white rounded-3 border d-block text-decoration-none text-dark hover-shadow transition h-100">
                    <i class="bi bi-journal-bookmark text-warning fs-4 d-block mb-2"></i>
                    <h6 class="fw-bold mb-1" style="font-size: 0.88rem;">Terms &amp; Conditions</h6>
                    <small class="text-muted" style="font-size: 0.75rem;">Platform and commercial terms</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ tfc_route('policy', 'privacy-policy') }}" class="p-3 bg-white rounded-3 border d-block text-decoration-none text-dark hover-shadow transition h-100">
                    <i class="bi bi-lock text-warning fs-4 d-block mb-2"></i>
                    <h6 class="fw-bold mb-1" style="font-size: 0.88rem;">Privacy Policy</h6>
                    <small class="text-muted" style="font-size: 0.75rem;">Data protection and security</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ tfc_route('policy', 'cooling-off-buyback-policy') }}" class="p-3 bg-white rounded-3 border d-block text-decoration-none text-dark hover-shadow transition h-100">
                    <i class="bi bi-arrow-counterclockwise text-warning fs-4 d-block mb-2"></i>
                    <h6 class="fw-bold mb-1" style="font-size: 0.88rem;">Return &amp; Refund Policy</h6>
                    <small class="text-muted" style="font-size: 0.75rem;">Cooling-off and buy-back rights</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ tfc_route('grievance') }}" class="p-3 bg-white rounded-3 border d-block text-decoration-none text-dark hover-shadow transition h-100">
                    <i class="bi bi-headset text-warning fs-4 d-block mb-2"></i>
                    <h6 class="fw-bold mb-1" style="font-size: 0.88rem;">Grievance Redressal</h6>
                    <small class="text-muted" style="font-size: 0.75rem;">Rule 7 complaint mechanism</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ tfc_route('policy', 'statutory-disclosures') }}#income-disclaimer" class="p-3 bg-white rounded-3 border d-block text-decoration-none text-dark hover-shadow transition h-100">
                    <i class="bi bi-graph-down text-warning fs-4 d-block mb-2"></i>
                    <h6 class="fw-bold mb-1" style="font-size: 0.88rem;">Income Disclaimer</h6>
                    <small class="text-muted" style="font-size: 0.75rem;">No guaranteed earnings notice</small>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <a href="{{ tfc_route('grievance.track') }}" class="p-3 bg-white rounded-3 border d-block text-decoration-none text-dark hover-shadow transition h-100">
                    <i class="bi bi-ticket-perforated text-warning fs-4 d-block mb-2"></i>
                    <h6 class="fw-bold mb-1" style="font-size: 0.88rem;">Track Ticket Status</h6>
                    <small class="text-muted" style="font-size: 0.75rem;">Live complaint tracking</small>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- Section 8: Frequently Asked Questions (Accordion) --}}
<section class="py-5" style="background-color: #FFFFFF;">
    <div class="container py-3">
        <div class="text-center mb-5">
            <div class="tfc-section-eyebrow">Clear Answers</div>
            <h2 class="tfc-section-title">Frequently Asked Questions</h2>
            <p class="text-muted mx-auto" style="max-width: 650px;">
                Learn everything you need to know about direct selling with TFC Sales &amp; Marketing.
            </p>
        </div>

        <div class="accordion tfc-accordion mx-auto" id="dsFaqAccordion" style="max-width: 860px;">
            <div class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        1. What is direct selling?
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#dsFaqAccordion">
                    <div class="accordion-body text-muted small" style="line-height: 1.7;">
                        Direct selling is the retail sale of consumer products directly to consumers away from a permanent fixed retail store, typically through in-person demonstrations, consultations, or direct recommendations by independent direct sellers.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        2. What is TFC Sales &amp; Marketing?
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#dsFaqAccordion">
                    <div class="accordion-body text-muted small" style="line-height: 1.7;">
                        TFC Sales &amp; Marketing is an Indian direct-selling organization dedicated to responsible, compliant commerce. We enable independent entrepreneurs across the country to recommend genuine Ayurvedic, herbal, and personal care products.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        3. What categories of products does TFC offer?
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#dsFaqAccordion">
                    <div class="accordion-body text-muted small" style="line-height: 1.7;">
                        TFC Sales &amp; Marketing curates high-performance botanical wellness, nutrition, skin vitality, and daily personal care formulations. All products are manufactured in certified facilities with strict quality and safety compliance.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        4. Is registration free?
                    </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#dsFaqAccordion">
                    <div class="accordion-body text-muted small" style="line-height: 1.7;">
                        <strong>Yes, 100% free.</strong> In strict accordance with the Consumer Protection (Direct Selling) Rules, 2021, TFC does not charge any registration fee, joining fee, periodic subscription, or compulsory starter-kit fee.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="headingFive">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                        5. Do I need to purchase products to join?
                    </button>
                </h2>
                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#dsFaqAccordion">
                    <div class="accordion-body text-muted small" style="line-height: 1.7;">
                        No. You are never obligated to purchase products to register. Product purchases occur voluntarily for personal consumption or to fulfill real customer orders under approved package guidelines.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="headingSix">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                        6. Can I recommend products to customers?
                    </button>
                </h2>
                <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#dsFaqAccordion">
                    <div class="accordion-body text-muted small" style="line-height: 1.7;">
                        Yes! As an authorized independent direct seller, you present TFC's products, explain directions for use and herbal benefits, and facilitate orders for your retail customers.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="headingSeven">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                        7. Is income guaranteed?
                    </button>
                </h2>
                <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#dsFaqAccordion">
                    <div class="accordion-body text-muted small" style="line-height: 1.7;">
                        <strong>No. TFC does not guarantee any income.</strong> Earnings depend entirely upon genuine product sales, personal effort, customer demand, and adherence to approved business plan rules.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="headingEight">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                        8. How are genuine product sales handled?
                    </button>
                </h2>
                <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#dsFaqAccordion">
                    <div class="accordion-body text-muted small" style="line-height: 1.7;">
                        Orders are processed through official channels with GST-compliant invoices. Products are dispatched directly to consumers or direct sellers with full tracking and statutory return options.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="headingNine">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNine" aria-expanded="false" aria-controls="collapseNine">
                        9. Where can customers purchase products?
                    </button>
                </h2>
                <div id="collapseNine" class="accordion-collapse collapse" aria-labelledby="headingNine" data-bs-parent="#dsFaqAccordion">
                    <div class="accordion-body text-muted small" style="line-height: 1.7;">
                        You can explore products on this website and purchase directly through an authorized TFC direct seller or order online for direct doorstep dispatch across India.
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-3 border rounded-3 overflow-hidden shadow-sm">
                <h2 class="accordion-header" id="headingTen">
                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTen" aria-expanded="false" aria-controls="collapseTen">
                        10. How can I raise a complaint or grievance?
                    </button>
                </h2>
                <div id="collapseTen" class="accordion-collapse collapse" aria-labelledby="headingTen" data-bs-parent="#dsFaqAccordion">
                    <div class="accordion-body text-muted small" style="line-height: 1.7;">
                        Under Rule 7 of the Direct Selling Rules, you can submit an online grievance at <a href="{{ tfc_route('grievance') }}" class="text-warning fw-semibold">our Grievance Portal</a>. You will receive an acknowledgment number within 48 hours and a transparent resolution within 30 days.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Section 9: Final CTA --}}
<section class="py-5" style="background: linear-gradient(135deg, #0A1D37, #132D54); color: #FFFFFF;">
    <div class="container py-4 text-center">
        <h2 class="display-6 fw-bold mb-3">Start With Products. Grow Through Relationships.</h2>
        <p class="text-white-50 mx-auto mb-4" style="max-width: 620px;">
            Join a responsible community dedicated to natural Ayurvedic formulations, transparent direct selling, and customer wellness.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="{{ tfc_route('products') }}" class="btn btn-tfc-primary btn-lg">
                <i class="bi bi-grid-fill me-2"></i>Explore Products
            </a>
            <a href="{{ tfc_route('become-a-seller') }}" class="btn btn-outline-light btn-lg">
                <i class="bi bi-pencil-square me-2"></i>Become a Direct Seller
            </a>
        </div>
    </div>
</section>
@endsection
