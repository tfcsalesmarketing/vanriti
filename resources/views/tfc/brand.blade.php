@extends('tfc.layouts.app')

@section('title', 'Product Philosophy & Standards | ' . tfc_setting('company_name', 'TFC Sales & Marketing'))
@section('meta_description', 'Discover the formulation philosophy, pure botanical standards, and certified manufacturing excellence behind TFC Sales & Marketing products.')

@section('content')
<section class="tfc-page-header">
    <div class="container text-center">
        <span class="tfc-badge-gold mb-2 d-inline-block">Formulation Standards</span>
        <h1 class="display-5 fw-bold text-white mb-2">Our Product Philosophy</h1>
        <p class="text-white-50 lead mb-0">Pure Botanicals &bull; Validated Science &bull; Certified Efficacy</p>
    </div>
</section>

<section class="py-5">
    <div class="container py-3">
        <div class="row align-items-center g-5 mb-5">
            <div class="col-12 col-lg-6">
                <div class="tfc-section-eyebrow">Formulation Excellence</div>
                <h2 class="tfc-section-title">Ancient Botanical Wisdom. Contemporary Scientific Quality.</h2>
                <p class="text-muted mb-4" style="line-height: 1.8;">
                    At <strong>TFC Sales &amp; Marketing</strong>, our product standards are built on the principle that true wellness begins with uncompromising ingredient purity. Each product in our catalogue is thoughtfully developed by marrying traditional herbal synergy with standardized bio-active extraction methods.
                </p>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 d-flex align-items-center gap-3">
                            <i class="bi bi-shield-check text-success fs-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">GMP &amp; ISO Certified Facilities</h6>
                                <small class="text-muted">Manufactured under pharmaceutical-grade cleanroom protocols with complete batch traceability.</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 d-flex align-items-center gap-3">
                            <i class="bi bi-droplet-half text-primary fs-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">Standardized Botanical Bio-Actives</h6>
                                <small class="text-muted">Potent concentrations of active phytochemicals ensure predictable, consistent results with every use.</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 d-flex align-items-center gap-3">
                            <i class="bi bi-patch-check text-warning fs-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1 text-dark">Heavy Metal &amp; Purity Screening</h6>
                                <small class="text-muted">Multi-phase laboratory testing verifying absolute freedom from harmful toxins, pesticides, and parabens.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <a href="{{ tfc_route('products') }}" class="btn btn-tfc-primary btn-lg">
                    <i class="bi bi-grid-fill me-2"></i>Explore Product Catalogue
                </a>
            </div>
            <div class="col-12 col-lg-6">
                <div class="p-4 p-md-5 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #0A1D37, #132D54); color: #FFFFFF;">
                    <h4 class="fw-bold text-white mb-3 text-center">Featured Formulations</h4>
                    <p class="text-white-50 small text-center mb-4">Crafted for everyday health, skincare rejuvenation, and vitality.</p>
                    <div class="row g-3">
                        @forelse ($products as $product)
                            <div class="col-6">
                                <div class="card tfc-card p-3 text-center h-100 bg-white">
                                    @php $img = $product->getPrimaryImage()?->image_path; @endphp
                                    <a href="{{ tfc_route('products.show', $product->slug) }}" class="d-block mb-2">
                                        @if ($img)
                                            <img src="{{ image_url($img) }}" alt="{{ $product->name }}" class="img-fluid rounded" style="max-height: 110px; object-fit: contain;">
                                        @else
                                            <div class="d-flex align-items-center justify-content-center bg-light text-muted rounded" style="height: 110px;">
                                                <i class="bi bi-flower1 fs-2 text-warning"></i>
                                            </div>
                                        @endif
                                    </a>
                                    <h6 class="fw-bold small text-dark mb-1 text-truncate">
                                        <a href="{{ tfc_route('products.show', $product->slug) }}" class="text-dark text-decoration-none">
                                            {{ $product->name }}
                                        </a>
                                    </h6>
                                    <small class="text-primary fw-bold">{{ format_price($product->selling_price) }}</small>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center text-white-50 py-3">
                                <p>Catalogue currently updating.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
