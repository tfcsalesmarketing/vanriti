@extends('tfc.layouts.app')

@section('title', $product->name . ' | TFC Sales & Marketing')
@section('meta_description', $product->short_description ?: 'Explore ' . $product->name . ' by VANRITI, marketed by TFC Sales & Marketing.')

@section('content')
<div class="container py-4">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ tfc_route('home') }}" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ tfc_route('products') }}" class="text-decoration-none">Products</a></li>
            @if ($product->primaryCategory())
                <li class="breadcrumb-item"><a href="{{ tfc_route('category', $product->primaryCategory()->slug) }}" class="text-decoration-none">{{ $product->primaryCategory()->name }}</a></li>
            @endif
            <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="row g-5">
        {{-- Product Image Gallery --}}
        <div class="col-12 col-lg-6">
            <div class="position-sticky" style="top: 100px;">
                @php
                    $primaryImg = $product->getPrimaryImage()?->image_path;
                @endphp
                <div class="card p-3 border rounded-4 shadow-sm text-center mb-3 bg-white">
                    @if ($primaryImg)
                        <img src="{{ image_url($primaryImg) }}" alt="{{ $product->name }}" id="mainProductImage" class="img-fluid rounded" style="max-height: 440px; object-fit: contain;">
                    @else
                        <div class="py-5 text-muted d-flex align-items-center justify-content-center" style="height: 380px;">
                            <i class="bi bi-image fs-1"></i>
                        </div>
                    @endif
                </div>

                @if ($product->images->count() > 1)
                    <div class="d-flex gap-2 overflow-auto pb-2">
                        @foreach ($product->images as $imgObj)
                            <div class="border rounded p-1 cursor-pointer bg-white" onclick="document.getElementById('mainProductImage').src='{{ image_url($imgObj->image_path) }}';" style="width: 72px; height: 72px; flex-shrink: 0; cursor: pointer;">
                                <img src="{{ image_url($imgObj->image_path) }}" alt="{{ $product->name }}" class="w-100 h-100 rounded" style="object-fit: cover;">
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Product Information & Purchase CTAs --}}
        <div class="col-12 col-lg-6">
            <div class="d-flex align-items-center gap-2 mb-2">
                @if ($product->primaryCategory())
                    <span class="badge tfc-badge-gold">{{ $product->primaryCategory()->name }}</span>
                @endif
                @if ($product->sku)
                    <span class="text-muted small">SKU: {{ $product->sku }}</span>
                @endif
            </div>

            <h1 class="fw-bold mb-3 text-dark">{{ $product->name }}</h1>

            {{-- Price Display --}}
            <div class="d-flex align-items-baseline gap-3 mb-4 p-3 rounded-3 bg-light border">
                <span class="display-6 fw-bold text-dark">{{ format_price($product->selling_price) }}</span>
                @if ($product->mrp && $product->mrp > $product->selling_price)
                    <span class="fs-5 text-muted text-decoration-line-through">{{ format_price($product->mrp) }}</span>
                    <span class="badge bg-danger fs-6 px-2.5 py-1">Save {{ round($product->discountPercent()) }}%</span>
                @endif
            </div>

            {{-- Live Stock Availability --}}
            <div class="mb-4">
                @if ($product->hasStock())
                    <div class="d-flex align-items-center gap-2 text-success fw-semibold">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>In Stock ({{ $product->getAvailableStock() }} units available)</span>
                    </div>
                @else
                    <div class="d-flex align-items-center gap-2 text-danger fw-semibold">
                        <i class="bi bi-x-circle-fill"></i>
                        <span>Currently Out of Stock</span>
                    </div>
                @endif
            </div>

            @if ($product->short_description)
                <p class="text-muted mb-4 lead fs-6">
                    {{ $product->short_description }}
                </p>
            @endif

            {{-- Official Purchase Link to VANRITI Storefront --}}
            <div class="p-4 rounded-4 mb-4 border" style="background: linear-gradient(135deg, #0A1D37, #132D54); color: #FFFFFF;">
                <h6 class="text-warning fw-bold mb-2"><i class="bi bi-bag-check-fill me-2"></i>Official Store Checkout</h6>
                <p class="text-white-50 small mb-3">
                    As an ethical direct-selling entity, orders, customer accounts, and secure online payments are fulfilled directly by the master VANRITI platform.
                </p>
                <a href="{{ tfc_vanriti_product_url($product->slug) }}" target="_blank" class="btn btn-tfc-primary btn-lg w-100 fw-bold shadow">
                    <i class="bi bi-cart3 me-2"></i>Buy on Official VANRITI Store <i class="bi bi-arrow-up-right small ms-1"></i>
                </a>
            </div>

            {{-- Direct Selling Consultation Option --}}
            <div class="p-3 rounded-3 bg-light border d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill text-primary fs-4"></i>
                    <div>
                        <div class="fw-semibold small text-dark">Have an Independent TFC Direct Seller?</div>
                        <div class="text-muted" style="font-size: 0.75rem;">Support your local distributor with personalized product advice.</div>
                    </div>
                </div>
                <a href="{{ tfc_route('contact') }}" class="btn btn-sm btn-outline-dark text-nowrap">Connect</a>
            </div>

            {{-- Specifications Tabs --}}
            <div class="accordion" id="productDetailsAccordion">
                @if ($product->description)
                    <div class="accordion-item border rounded mb-2 shadow-sm">
                        <h2 class="accordion-header" id="headingDesc">
                            <button class="accordion-button fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDesc">
                                Product Description
                            </button>
                        </h2>
                        <div id="collapseDesc" class="accordion-collapse collapse show" data-bs-parent="#productDetailsAccordion">
                            <div class="accordion-body text-muted" style="line-height: 1.7;">
                                {!! clean_html($product->description) !!}
                            </div>
                        </div>
                    </div>
                @endif

                @if ($product->ingredients)
                    <div class="accordion-item border rounded mb-2 shadow-sm">
                        <h2 class="accordion-header" id="headingIngr">
                            <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseIngr">
                                Herbal Ingredients &amp; Actives
                            </button>
                        </h2>
                        <div id="collapseIngr" class="accordion-collapse collapse" data-bs-parent="#productDetailsAccordion">
                            <div class="accordion-body text-muted">
                                {!! clean_html($product->ingredients) !!}
                            </div>
                        </div>
                    </div>
                @endif

                @if ($product->benefits)
                    <div class="accordion-item border rounded mb-2 shadow-sm">
                        <h2 class="accordion-header" id="headingBen">
                            <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBen">
                                Key Wellness Benefits
                            </button>
                        </h2>
                        <div id="collapseBen" class="accordion-collapse collapse" data-bs-parent="#productDetailsAccordion">
                            <div class="accordion-body text-muted">
                                {!! clean_html($product->benefits) !!}
                            </div>
                        </div>
                    </div>
                @endif

                @if ($product->how_to_use || $product->directions)
                    <div class="accordion-item border rounded mb-2 shadow-sm">
                        <h2 class="accordion-header" id="headingUse">
                            <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUse">
                                Directions for Use
                            </button>
                        </h2>
                        <div id="collapseUse" class="accordion-collapse collapse" data-bs-parent="#productDetailsAccordion">
                            <div class="accordion-body text-muted">
                                {!! clean_html($product->how_to_use ?: $product->directions) !!}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Related Products --}}
    @if ($relatedProducts->isNotEmpty())
        <div class="mt-5 pt-5 border-top">
            <h4 class="fw-bold mb-4">Complementary Wellness Products</h4>
            <div class="row g-4">
                @foreach ($relatedProducts as $rel)
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card tfc-card h-100">
                            @php $relImg = $rel->getPrimaryImage()?->image_path; @endphp
                            <a href="{{ tfc_route('products.show', $rel->slug) }}">
                                @if ($relImg)
                                    <img src="{{ image_url($relImg) }}" alt="{{ $rel->name }}" class="tfc-product-img">
                                @else
                                    <div class="tfc-product-img bg-light d-flex align-items-center justify-content-center text-muted">
                                        <i class="bi bi-image fs-1"></i>
                                    </div>
                                @endif
                            </a>
                            <div class="card-body d-flex flex-column p-3">
                                <h6 class="fw-bold mb-2">
                                    <a href="{{ tfc_route('products.show', $rel->slug) }}" class="text-dark text-decoration-none">
                                        {{ $rel->name }}
                                    </a>
                                </h6>
                                <div class="mt-auto d-flex align-items-center justify-content-between">
                                    <span class="tfc-price fs-6">{{ format_price($rel->selling_price) }}</span>
                                    <a href="{{ tfc_route('products.show', $rel->slug) }}" class="btn btn-sm btn-tfc-primary">View</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
