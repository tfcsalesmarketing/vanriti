@extends('tfc.layouts.app')

@section('title', 'Direct Seller Benefits & Rights | TFC Sales & Marketing')

@section('content')
<section class="tfc-page-header">
    <div class="container text-center">
        <span class="tfc-badge-gold mb-2 d-inline-block">Distributor Rights</span>
        <h1 class="display-5 fw-bold text-white mb-2">Benefits &amp; Safeguards</h1>
        <p class="text-white-50 lead mb-0">Empowerment Built on Protection and Statutory Compliance</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4 mb-5">
            <div class="col-12 col-md-4">
                <div class="p-4 bg-white rounded-3 shadow-sm h-100 border">
                    <div class="tfc-feature-icon"><i class="bi bi-wallet2"></i></div>
                    <h5 class="fw-bold mb-2">Transparent Retail Margins</h5>
                    <p class="text-muted small mb-0">Earn directly from genuine consumer sales without complicated MLM recruitment obligations or pyramid quotas.</p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-4 bg-white rounded-3 shadow-sm h-100 border">
                    <div class="tfc-feature-icon"><i class="bi bi-arrow-repeat"></i></div>
                    <h5 class="fw-bold mb-2">Statutory Cooling-Off Rights</h5>
                    <p class="text-muted small mb-0">Enjoy peace of mind with statutory cooling-off protections and inventory return/buy-back provisions per direct selling rules.</p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-4 bg-white rounded-3 shadow-sm h-100 border">
                    <div class="tfc-feature-icon"><i class="bi bi-person-badge"></i></div>
                    <h5 class="fw-bold mb-2">Official Identity &amp; Contract</h5>
                    <p class="text-muted small mb-0">Every authorized direct seller receives an official agreement and digital ID confirming independent contractor credentials.</p>
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="{{ tfc_route('become-a-seller') }}" class="btn btn-tfc-primary btn-lg">
                Join TFC as a Direct Seller <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</section>
@endsection
