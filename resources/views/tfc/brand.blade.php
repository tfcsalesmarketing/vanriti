@extends('tfc.layouts.app')

@section('title', 'The VANRITI Brand Partnership | TFC Sales & Marketing')

@section('content')
<section class="tfc-page-header">
    <div class="container text-center">
        <span class="tfc-badge-gold mb-2 d-inline-block">Brand Partnership</span>
        <h1 class="display-5 fw-bold text-white mb-2">The VANRITI Heritage</h1>
        <p class="text-white-50 lead mb-0">Rooted in Ayurveda &bull; Validated by Science</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row align-items-center g-5 mb-5">
            <div class="col-12 col-lg-6">
                <div class="tfc-section-eyebrow">Formulation Excellence</div>
                <h2 class="tfc-section-title">Ancient Wisdom. Contemporary Quality.</h2>
                <p class="text-muted mb-4">
                    VANRITI was born from the timeless traditions of classical Ayurveda, designed to bring holistic wellness, vitality, and purity into every Indian household. Each formulation draws from potent herbal botanicals harvested at their peak therapeutic potency.
                </p>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 d-flex align-items-center gap-3">
                            <i class="bi bi-shield-check text-success fs-3"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">GMP &amp; ISO Quality Standards</h6>
                                <small class="text-muted">Manufactured in state-of-the-art facilities with strict hygiene and batch testing protocols.</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 d-flex align-items-center gap-3">
                            <i class="bi bi-droplet-half text-primary fs-3"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Standardized Botanical Extracts</h6>
                                <small class="text-muted">High-potency bio-active compounds delivering consistent, noticeable therapeutic benefits.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <a href="{{ config('tfc.vanriti_store_url', 'https://vanriti.com') }}" target="_blank" class="btn btn-tfc-navy">
                    Visit Official VANRITI Store <i class="bi bi-box-arrow-up-right ms-1"></i>
                </a>
            </div>
            <div class="col-12 col-lg-6">
                <div class="row g-3">
                    @forelse ($products as $product)
                        <div class="col-6">
                            <div class="card tfc-card p-3 text-center h-100">
                                @php $img = $product->getPrimaryImage()?->image_path; @endphp
                                @if ($img)
                                    <img src="{{ image_url($img) }}" alt="{{ $product->name }}" class="img-fluid rounded mb-2" style="max-height: 120px; object-fit: contain;">
                                @endif
                                <h6 class="fw-bold small text-dark mb-1 text-truncate">{{ $product->name }}</h6>
                                <small class="text-muted">{{ format_price($product->selling_price) }}</small>
                            </div>
                        </div>
                    @empty
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
