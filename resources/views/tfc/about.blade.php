@extends('tfc.layouts.app')

@section('title', 'About Us | ' . tfc_setting('company_name', 'TFC Sales & Marketing'))
@section('meta_description', 'Discover the story, vision, and leadership values of TFC Sales & Marketing — a premier Indian direct-selling enterprise empowering individuals through wellness and entrepreneurship.')

@section('content')
<section class="tfc-page-header py-5" style="background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%); border-bottom: 1px solid rgba(0,0,0,0.06);">
    <div class="container text-center py-2">
        <span class="badge bg-warning text-dark fw-bold mb-2 px-3 py-1.5" style="font-size: 0.8rem; letter-spacing: 0.5px;">CORPORATE PROFILE</span>
        <h1 class="display-5 fw-bold text-dark mb-2">About TFC Sales &amp; Marketing</h1>
        <p class="text-muted lead mb-0">{{ tfc_setting('company_tagline', 'Connecting Products. Creating Possibilities.') }}</p>
    </div>
</section>

{{-- Company Story & Vision --}}
<section class="py-5">
    <div class="container py-3">
        <div class="row align-items-center g-5 mb-5">
            <div class="col-12 col-lg-6">
                <div class="tfc-section-eyebrow">Who We Are</div>
                <h2 class="tfc-section-title">Empowering Lives Through Wellness &amp; Entrepreneurship</h2>
                <p class="text-muted mb-3" style="line-height: 1.8;">
                    <strong>TFC Sales &amp; Marketing</strong> is a forward-thinking Indian direct-selling enterprise founded on the belief that everyday people possess extraordinary entrepreneurial potential. We bridge the gap between high-performance wellness innovations and households across India through the power of trusted interpersonal recommendation.
                </p>
                <p class="text-muted mb-4" style="line-height: 1.8;">
                    In a rapidly evolving digital economy, personal recommendation remains the most authentic bridge between premium formulations and satisfied consumers. By pairing scientifically crafted botanical formulations with a rewarding, binary-driven business model, we enable aspiring entrepreneurs to build self-sustaining independent enterprises with zero capital risk.
                </p>
                <div class="p-3 rounded-3 bg-light border-start border-4 border-warning">
                    <p class="fw-semibold text-dark mb-0 small">
                        "Our purpose is simple: to make life-enhancing wellness accessible to every Indian family while creating sustainable economic freedom for those who champion our products."
                    </p>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="p-4 p-md-5 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #0A1D37, #132D54); color: #FFFFFF;">
                    <div class="text-center mb-4">
                        <img src="{{ tfc_logo_url() }}" alt="TFC Logo" class="img-fluid rounded mb-2" style="max-height: 80px; background: #FFFFFF; padding: 8px 16px;">
                        <h4 class="fw-bold mb-1">The TFC Enterprise Vision</h4>
                        <span class="text-warning small text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Building India's Most Trusted Direct Selling Community</span>
                    </div>

                    <div class="d-flex flex-column gap-3 text-white-50 small">
                        <div class="d-flex align-items-start gap-3 p-3 rounded-3" style="background: rgba(255, 255, 255, 0.05);">
                            <i class="bi bi-eye-fill text-warning fs-4 mt-1"></i>
                            <div>
                                <strong class="text-white d-block mb-1">Our Vision</strong>
                                To be recognized as India's benchmark direct-selling company, celebrated for product efficacy, business transparency, and the financial transformation of our distributor family.
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3 p-3 rounded-3" style="background: rgba(255, 255, 255, 0.05);">
                            <i class="bi bi-compass-fill text-warning fs-4 mt-1"></i>
                            <div>
                                <strong class="text-white d-block mb-1">Our Mission</strong>
                                To deliver pure, results-driven wellness and personal care formulations to millions of consumers while providing an equitable, binary compensation plan that rewards dedication and leadership.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Core Values Section --}}
        <div class="mt-5 pt-4 border-top">
            <div class="text-center mb-5">
                <div class="tfc-section-eyebrow">Guiding Principles</div>
                <h2 class="tfc-section-title">The Values That Define Us</h2>
                <p class="text-muted mx-auto" style="max-width: 650px;">
                    Integrity, empowerment, and excellence guide every decision we make — from formulation research to distributor compensation.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-12 col-md-4">
                    <div class="p-4 rounded-3 bg-light border h-100 text-center">
                        <div class="tfc-feature-icon mx-auto mb-3"><i class="bi bi-shield-check"></i></div>
                        <h5 class="fw-bold text-dark mb-2">Uncompromising Quality</h5>
                        <p class="text-muted small mb-0">
                            We never cut corners on ingredient purity, clinical safety, or manufacturing hygiene. Every product delivered carries our absolute mark of excellence.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="p-4 rounded-3 bg-light border h-100 text-center">
                        <div class="tfc-feature-icon mx-auto mb-3"><i class="bi bi-people-fill"></i></div>
                        <h5 class="fw-bold text-dark mb-2">Equal Opportunity for All</h5>
                        <p class="text-muted small mb-0">
                            Whether you are a seasoned business leader, a homemaker, or a young professional, TFC provides a level playing field with zero joining fees and limitless upward mobility.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="p-4 rounded-3 bg-light border h-100 text-center">
                        <div class="tfc-feature-icon mx-auto mb-3"><i class="bi bi-trophy-fill"></i></div>
                        <h5 class="fw-bold text-dark mb-2">Ethical Leadership</h5>
                        <p class="text-muted small mb-0">
                            We foster a community of servant leaders dedicated to mentorship, truthful product claims, and uplifting every member of their binary team.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Call to Action --}}
        <div class="text-center mt-5 pt-4">
            <div class="d-inline-flex flex-wrap gap-3">
                <a href="{{ route('tfc.preview.member.register') }}" class="btn btn-tfc-primary btn-lg shadow-sm">
                    <i class="bi bi-person-plus-fill me-2"></i>Join TFC Today
                </a>
                <a href="{{ tfc_route('products') }}" class="btn btn-outline-dark btn-lg">
                    <i class="bi bi-grid-fill me-2"></i>Browse Our Products
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
