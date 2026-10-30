@extends('tfc.layouts.app')

@section('title', 'About Us | TFC Sales & Marketing')

@section('content')
<section class="tfc-page-header">
    <div class="container text-center">
        <span class="tfc-badge-gold mb-2 d-inline-block">Corporate Profile</span>
        <h1 class="display-5 fw-bold text-white mb-2">About TFC Sales &amp; Marketing</h1>
        <p class="text-white-50 lead mb-0">{{ tfc_setting('company_tagline', 'Connecting Products. Creating Possibilities.') }}</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row align-items-center g-5 mb-5">
            <div class="col-12 col-lg-6">
                <div class="tfc-section-eyebrow">Our Mission &bull; Ethical Direct Selling</div>
                <h2 class="tfc-section-title">Connecting Quality Products with Real People</h2>
                <p class="text-muted mb-4">
                    TFC Sales &amp; Marketing was established with a singular vision: to bridge the gap between world-class Ayurvedic wellness innovations and households across India through the power of trusted interpersonal recommendation.
                </p>
                <p class="text-muted mb-4">
                    In an age of faceless e-commerce algorithms, authentic personal trust remains the strongest foundation for consumer wellness. Through our structured direct-selling model, we empower passionate individuals to establish independent, lawful micro-enterprises with zero initial investment risk.
                </p>
                <div class="p-3 rounded-3 bg-light border-start border-4 border-warning">
                    <p class="fw-semibold text-dark mb-0 small">
                        "We believe commercial success must always be aligned with consumer protection and strict statutory compliance."
                    </p>
                </div>
            </div>
            <div class="col-12 col-lg-6 text-center">
                <div class="p-5 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #0A1D37, #132D54); color: #FFFFFF;">
                    <img src="{{ tfc_logo_url() }}" alt="TFC Logo" class="img-fluid rounded mb-4" style="max-height: 100px; background: #FFFFFF; padding: 10px 20px;">
                    <h4 class="fw-bold mb-3">Our Core Principles</h4>
                    <ul class="list-unstyled text-start text-white-50 small mb-0">
                        <li class="mb-3 d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-warning mt-1"></i>
                            <div><strong>100% Genuine Products:</strong> Every rupee earned is tied to the physical delivery of genuine consumer goods.</div>
                        </li>
                        <li class="mb-3 d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-warning mt-1"></i>
                            <div><strong>Zero Recruitment Schemes:</strong> We explicitly prohibit pyramid, matrix, or head-hunting compensation.</div>
                        </li>
                        <li class="mb-3 d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-warning mt-1"></i>
                            <div><strong>Consumer Priority:</strong> Unconditional respect for customer rights, cooling-off return windows, and grievance redressal.</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
