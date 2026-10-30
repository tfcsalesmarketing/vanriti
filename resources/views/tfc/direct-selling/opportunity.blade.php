@extends('tfc.layouts.app')

@section('title', 'Direct Selling Opportunity | TFC Sales & Marketing')

@section('content')
<section class="tfc-page-header">
    <div class="container text-center">
        <span class="tfc-badge-gold mb-2 d-inline-block">Entrepreneurial Pathway</span>
        <h1 class="display-5 fw-bold text-white mb-2">The TFC Direct Selling Opportunity</h1>
        <p class="text-white-50 lead mb-0">Empowering Sustainable Micro-Enterprises with Zero Joining Fee</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        {{-- Statutory Compliance Callout --}}
        <div class="alert alert-success border-success p-4 rounded-4 mb-5 shadow-sm d-flex align-items-center gap-3">
            <i class="bi bi-shield-check text-success fs-1"></i>
            <div>
                <h5 class="fw-bold mb-1">100% Compliant with Direct Selling Rules, 2021</h5>
                <p class="mb-0 small text-muted">
                    In compliance with official government regulations, TFC provides a legitimate, transparent direct-selling platform with <strong>NO registration fees</strong>, <strong>NO compulsory purchase packages</strong>, and <strong>NO pyramid recruitment commissions</strong>. Earnings are strictly derived from retail sales of genuine wellness products.
                </p>
            </div>
        </div>

        <div class="row align-items-center g-5 mb-5">
            <div class="col-12 col-lg-6">
                <div class="tfc-section-eyebrow">Why Partner with TFC?</div>
                <h2 class="tfc-section-title">Turn Your Passion for Wellness into an Independent Business</h2>
                <p class="text-muted mb-4">
                    Direct selling is one of the most accessible avenues for micro-entrepreneurship in modern India. As an independent TFC direct seller, you represent certified Ayurvedic and personal care formulations that customers genuinely need and reorder.
                </p>
                <div class="d-flex flex-column gap-3 mb-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="tfc-feature-icon" style="width: 44px; height: 44px; font-size: 1.2rem; flex-shrink: 0;"><i class="bi bi-currency-rupee"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Earn Legitimate Retail Margins</h6>
                            <small class="text-muted">Earn competitive margins between distributor procurement rates and Maximum Retail Price (MRP).</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="tfc-feature-icon" style="width: 44px; height: 44px; font-size: 1.2rem; flex-shrink: 0;"><i class="bi bi-clock-history"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Work on Your Own Schedule</h6>
                            <small class="text-muted">Part-time or full-time flexibility that fits around your family, career, or personal lifestyle.</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="tfc-feature-icon" style="width: 44px; height: 44px; font-size: 1.2rem; flex-shrink: 0;"><i class="bi bi-box2-heart"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">High Re-order Natural Formulations</h6>
                            <small class="text-muted">Wellness products with high daily consumption resulting in consistent organic repeat orders.</small>
                        </div>
                    </div>
                </div>
                <a href="{{ tfc_route('become-a-seller') }}" class="btn btn-tfc-primary btn-lg">
                    Apply to Become a Direct Seller <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card p-4 p-md-5 border-0 shadow-lg rounded-4" style="background: linear-gradient(135deg, #0A1D37, #132D54); color: #FFFFFF;">
                    <h4 class="fw-bold text-warning mb-4">Our Direct Selling Commitment</h4>
                    <div class="mb-3 border-bottom pb-3">
                        <h6 class="fw-bold text-white mb-1"><i class="bi bi-check2-circle text-warning me-2"></i>No Financial Risk</h6>
                        <small class="text-white-50">Never spend thousands on compulsory inventory packages just to qualify.</small>
                    </div>
                    <div class="mb-3 border-bottom pb-3">
                        <h6 class="fw-bold text-white mb-1"><i class="bi bi-check2-circle text-warning me-2"></i>Statutory Cooling-Off Rights</h6>
                        <small class="text-white-50">Both customers and distributors benefit from clear return and buy-back rights.</small>
                    </div>
                    <div class="mb-3 border-bottom pb-3">
                        <h6 class="fw-bold text-white mb-1"><i class="bi bi-check2-circle text-warning me-2"></i>Product Training Support</h6>
                        <small class="text-white-50">Learn genuine product knowledge and ethical consultation techniques.</small>
                    </div>
                    <div>
                        <h6 class="fw-bold text-white mb-1"><i class="bi bi-check2-circle text-warning me-2"></i>Direct Brand Backing</h6>
                        <small class="text-white-50">Products backed by VANRITI’s certified Ayurvedic manufacturing heritage.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
