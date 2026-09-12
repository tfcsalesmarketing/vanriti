@extends('storefront.layouts.app')

@section('title', $product->name)
@section('meta_description', $product->meta_description ?: $product->short_description ?: setting('meta_description'))
@section('meta_keywords', $product->meta_keywords ?: setting('meta_keywords'))
@section('og_title', $product->name)
@section('og_description', $product->short_description ?: $product->meta_description ?: setting('meta_description'))
@section('og_image', $product->getPrimaryImage()
    ? image_url($product->getPrimaryImage()?->image_path, 'favicon.ico')
    : image_url(null, 'favicon.ico'))

@section('content')

@php
    $img = $product->getPrimaryImage()?->image_path;
    $gallery = $product->images;
    $available = $product->getAvailableStock();
    $out = $available <= 0;
    $low = ! $out && $product->isLowStock();
    $category = $product->categories->first();
    $reviews = $product->reviews;
    $reviewCount = $reviews->count();
    $reviewAvg = $reviewCount ? round((float) $reviews->avg('rating'), 1) : 0;
    $discount = $product->discountPercent();
    $wishlistService = app(App\Services\WishlistService::class);
    $liked = $wishlistService->has($product);
    $firstInStockVariant = $product->activeVariants->first(fn ($v) => (int) $v->stock > 0);
    $viewItemPayload = app(App\Services\Analytics\EcommerceDataService::class)->viewItem($product, $firstInStockVariant, 1);
@endphp

<div class="container py-4">
    <nav class="vr-breadcrumb mb-4" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            @if ($category)
                <li class="breadcrumb-item"><a href="{{ route('shop.category', $category->slug) }}">{{ $category->name }}</a></li>
            @endif
            <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="row g-4 g-lg-5">
        <div class="col-lg-5">
            <div class="vr-zoom-container" id="vrZoomContainer">
                @if ($img)
                    <a href="#" id="vrMainImageWrap" class="vr-zoom d-block mb-3" role="button" aria-label="Zoom product image" data-bs-toggle="modal" data-bs-target="#vrLightbox">
                        <img id="vrMainImage" src="{{ image_url($img) }}" alt="{{ $product->name }}" class="img-fluid rounded-3 w-100 vr-zoom-image" style="aspect-ratio:1/1;object-fit:cover;" fetchpriority="high">
                    </a>
                @else
                    <a href="#" id="vrMainImageWrap" class="vr-zoom d-block mb-3" role="button" aria-label="Zoom product image" data-bs-toggle="modal" data-bs-target="#vrLightbox">
                        <div id="vrMainImage" class="bg-light rounded-3 d-flex align-items-center justify-content-center" style="aspect-ratio:1/1;">
                            <i class="bi bi-image text-secondary display-4"></i>
                        </div>
                    </a>
                @endif
                <div class="vr-zoom-lens" id="vrZoomLens"></div>
                <div class="vr-zoom-result" id="vrZoomResult"></div>
            </div>

            @if ($gallery->count() > 1)
                <div class="row g-2">
                    @foreach ($gallery as $image)
                        @php $thumb = $image->image_path; @endphp
                        <div class="col-3">
                            <div class="vr-gallery-thumb {{ $thumb && $thumb === $img ? 'active' : '' }}" data-image="{{ image_url($thumb) }}">
                                <img src="{{ image_url($thumb) }}" alt="{{ $image->alt_text ?: $product->name }}" class="img-fluid" style="aspect-ratio:1/1;object-fit:cover;">
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="col-lg-7">
            @if ($category)
                <a href="{{ route('shop.category', $category->slug) }}" class="cat-name text-uppercase text-decoration-none d-inline-block mb-1">{{ $category->name }}</a>
            @endif
            <h1 class="h3 fw-bold mb-2">{{ $product->name }}</h1>

            @if ($reviewCount > 0)
                <div class="rating-stars small mb-3">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="bi bi-star{{ $i <= (int) round($reviewAvg) ? '-fill filled' : '' }}"></i>
                    @endfor
                    <span class="text-muted ms-1">{{ $reviewAvg }} · {{ $reviewCount }} review{{ $reviewCount === 1 ? '' : 's' }}</span>
                </div>
            @endif

            <div class="vr-price mb-3">
                <span class="now fs-3" id="vrNowPrice">{{ format_price($product->selling_price) }}</span>
                <span class="mrp fs-6" id="vrMrpPrice" @if ($product->mrp <= $product->selling_price) style="display:none;" @endif>{{ format_price($product->mrp) }}</span>
                @if ($discount > 0)
                    <span class="small fw-bold" style="color:var(--vr-green);" id="vrDiscPct">{{ (int) round($discount) }}% OFF</span>
                @endif
            </div>

            @if ($product->short_description)
                <p class="text-muted mb-3">{{ $product->short_description }}</p>
            @endif

            <form method="POST" action="{{ route('cart.add', $product) }}" id="addToCartForm">
                @csrf
                <input type="hidden" name="buy_now" value="0">

                @if ($product->activeVariants->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select Option</label>
                        <div class="d-grid gap-2" id="vrVariantList">
                            @foreach ($product->activeVariants as $v)
                                @php $vOut = (int) $v->stock <= 0; @endphp
                                <label class="vr-variant-option border rounded-3 p-2 d-flex align-items-center gap-2 {{ $vOut ? 'is-out' : '' }} {{ $firstInStockVariant && $firstInStockVariant->id === $v->id ? 'is-selected' : '' }}"
                                       data-price="{{ $v->selling_price }}"
                                       data-price-fmt="{{ format_price($v->selling_price) }}"
                                       data-mrp="{{ $v->mrp }}"
                                       data-mrp-fmt="{{ format_price($v->mrp) }}"
                                       data-instock="{{ $vOut ? 0 : (int) $v->stock }}">
                                    <input type="radio" name="variant_id" value="{{ $v->id }}" class="form-check-input mt-0"
                                           @checked($firstInStockVariant && $firstInStockVariant->id === $v->id)
                                           {{ $vOut ? 'disabled' : '' }}>
                                    <span class="small flex-grow-1">{{ $v->name }}</span>
                                    <span class="small fw-semibold">{{ format_price($v->selling_price) }}</span>
                                    @if ($v->mrp > $v->selling_price)
                                        <span class="small text-muted text-decoration-line-through">{{ format_price($v->mrp) }}</span>
                                    @endif
                                    @if ($vOut)
                                        <span class="vr-stock-label out">Out of stock</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                    <div class="vr-qty">
                        <button type="button" class="qty-minus" data-step="-1" aria-label="Decrease quantity"><i class="bi bi-dash"></i></button>
                        <input type="number" name="quantity" value="1" min="1" aria-label="Quantity" id="vrBuyQty">
                        <button type="button" data-step="1" aria-label="Increase quantity"><i class="bi bi-plus"></i></button>
                    </div>
                    <button type="submit" class="btn btn-vr btn-lg flex-grow-1 js-pdp-add" {{ $out ? 'disabled' : '' }}>
                        <i class="bi bi-bag me-2"></i> Add to Cart
                    </button>
                    <button type="submit" name="buy_now" value="1" class="btn btn-vr-outline btn-lg flex-grow-1 js-pdp-buy" {{ $out ? 'disabled' : '' }}>
                        <i class="bi bi-lightning-charge me-2"></i> Buy Now
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('wishlist.toggle', $product) }}" class="mb-3">
                @csrf
                <button type="submit" class="btn btn-vr-outline btn-lg w-100">
                    <i class="bi {{ $liked ? 'bi-heart-fill' : 'bi-heart' }} me-2"></i>
                    {{ $liked ? 'In Wishlist' : 'Add to Wishlist' }}
                </button>
            </form>

            <div class="mb-3">
                @if ($out)
                    <span class="vr-stock-label out d-inline-block mb-2">Out of stock</span>
                @elseif ($low)
                    <span class="vr-stock-label in d-inline-block mb-2">Only {{ $available }} left in stock</span>
                @endif
            </div>

            <ul class="list-unstyled d-flex flex-wrap gap-3 small text-muted mb-0">
                <li><i class="bi bi-truck me-1" style="color:var(--vr-green);"></i> Free shipping above {{ format_price(setting('free_shipping_threshold', 499)) }}</li>
                <li><i class="bi bi-cash-coin me-1" style="color:var(--vr-green);"></i> COD available</li>
                <li><i class="bi bi-arrow-counterclockwise me-1" style="color:var(--vr-green);"></i> {{ setting('return_window_days', 7) }}-day easy returns</li>
                <li><i class="bi bi-flower1 me-1" style="color:var(--vr-green);"></i> Cruelty-free &amp; natural</li>
            </ul>
        </div>
    </div>

    <div class="vr-accordion accordion mt-5" id="vrProductAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-description">Description</button>
            </h2>
            <div id="collapse-description" class="accordion-collapse collapse show" data-bs-parent="#vrProductAccordion">
                <div class="accordion-body">
                    <div>{!! $product->description ?: 'No description available.' !!}</div>
                    @if ($product->net_quantity || $product->country_of_origin || $product->shelf_life)
                        <div class="row g-3 mt-3 small">
                            @if ($product->net_quantity)
                                <div class="col-6 col-md-3"><div class="text-muted">Net Quantity</div><div class="fw-semibold">{{ $product->net_quantity }} {{ $product->unit }}</div></div>
                            @endif
                            @if ($product->country_of_origin)
                                <div class="col-6 col-md-3"><div class="text-muted">Country of Origin</div><div class="fw-semibold">{{ $product->country_of_origin }}</div></div>
                            @endif
                            @if ($product->shelf_life)
                                <div class="col-6 col-md-3"><div class="text-muted">Shelf Life</div><div class="fw-semibold">{{ $product->shelf_life }}</div></div>
                            @endif
                            @if ($product->manufacturer)
                                <div class="col-6 col-md-3"><div class="text-muted">Manufacturer</div><div class="fw-semibold">{{ $product->manufacturer }}</div></div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($product->highlights)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-highlights">Highlights</button>
                </h2>
                <div id="collapse-highlights" class="accordion-collapse collapse" data-bs-parent="#vrProductAccordion">
                    <div class="accordion-body">
                        <ul class="mb-0 ps-3">
                            @foreach (preg_split('/\R/', (string) $product->highlights) as $line)
                                @if (trim($line) !== '')
                                    <li class="mb-1">{{ $line }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        @if ($product->ingredients)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-ingredients">Ingredients</button>
                </h2>
                <div id="collapse-ingredients" class="accordion-collapse collapse" data-bs-parent="#vrProductAccordion">
                    <div class="accordion-body">{{ $product->ingredients }}</div>
                </div>
            </div>
        @endif

        @if ($product->how_to_use)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-usage">How to Use</button>
                </h2>
                <div id="collapse-usage" class="accordion-collapse collapse" data-bs-parent="#vrProductAccordion">
                    <div class="accordion-body">{{ $product->how_to_use }}</div>
                </div>
            </div>
        @endif

        @if ($product->benefits)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-benefits">Benefits</button>
                </h2>
                <div id="collapse-benefits" class="accordion-collapse collapse" data-bs-parent="#vrProductAccordion">
                    <div class="accordion-body">
                        <ul class="mb-0 ps-3">
                            @foreach (preg_split('/\R/', (string) $product->benefits) as $line)
                                @if (trim($line) !== '')
                                    <li class="mb-1">{{ $line }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        @if ($product->warnings)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-warnings">Warnings</button>
                </h2>
                <div id="collapse-warnings" class="accordion-collapse collapse" data-bs-parent="#vrProductAccordion">
                    <div class="accordion-body">{{ $product->warnings }}</div>
                </div>
            </div>
        @endif

        @if ($product->precautions)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-precautions">Precautions</button>
                </h2>
                <div id="collapse-precautions" class="accordion-collapse collapse" data-bs-parent="#vrProductAccordion">
                    <div class="accordion-body">{{ $product->precautions }}</div>
                </div>
            </div>
        @endif

        @if ($product->disclaimer)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-disclaimer">Disclaimer</button>
                </h2>
                <div id="collapse-disclaimer" class="accordion-collapse collapse" data-bs-parent="#vrProductAccordion">
                    <div class="accordion-body"><em class="small text-muted">{{ $product->disclaimer }}</em></div>
                </div>
            </div>
        @endif

        @if (setting('return_policy'))
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-shipping">Shipping &amp; Returns</button>
                </h2>
                <div id="collapse-shipping" class="accordion-collapse collapse" data-bs-parent="#vrProductAccordion">
                    <div class="accordion-body">{{ setting('return_policy') }}</div>
                </div>
            </div>
        @endif
    </div>

    <section class="mt-5 vr-animate-in">
        <h2 class="h5 fw-bold mb-3" style="color:var(--vr-green-dark);">Customer Reviews</h2>
        @if ($reviewCount > 0)
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="text-center pe-3" style="border-right:1px solid var(--vr-border);">
                    <div class="fs-3 fw-bold" style="color:var(--vr-green-dark);">{{ $reviewAvg }}</div>
                    <div class="rating-stars small">
                        @for ($i = 1; $i <= 5; $i++)
                            <i class="bi bi-star{{ $i <= (int) round($reviewAvg) ? '-fill filled' : '' }}"></i>
                        @endfor
                    </div>
                    <div class="small text-muted mt-1">{{ $reviewCount }} review{{ $reviewCount === 1 ? '' : 's' }}</div>
                </div>
            </div>
            <div class="d-grid gap-3">
                @foreach ($reviews as $review)
                    <div class="p-3" style="border-radius:var(--vr-radius);background:#fff;box-shadow:var(--vr-shadow-xs);">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-semibold small">{{ $review->user?->name ?: 'Verified Buyer' }}</span>
                            @if ($review->is_verified_purchase)
                                <span class="vr-pill-badge ps-paid" style="font-size:0.62rem;">Verified Purchase</span>
                            @endif
                            <span class="small text-muted ms-auto">{{ $review->created_at?->format('d M Y') }}</span>
                        </div>
                        <div class="rating-stars small mb-1">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bi bi-star{{ $i <= (int) $review->rating ? '-fill filled' : '' }}"></i>
                            @endfor
                        </div>
                        @if ($review->title)
                            <div class="fw-semibold small">{{ $review->title }}</div>
                        @endif
                        @if ($review->comment)
                            <p class="small text-muted mb-0 mt-1">{{ $review->comment }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="vr-empty bg-white rounded-4" style="box-shadow:var(--vr-shadow-xs);">
                <i class="bi bi-chat-left-text"></i>
                <p class="mt-2 mb-0">No reviews yet. Be the first to review this product.</p>
            </div>
        @endif
    </section>

    @if ($related->isNotEmpty())
        <section class="vr-section pt-3">
            <div class="d-flex align-items-end justify-content-between mb-4 vr-animate-in">
                <div>
                    <h2 class="vr-section-title mb-1">You may also like</h2>
                    <p class="vr-section-sub mb-0">More from our collection</p>
                </div>
            </div>
            <div class="row g-4 vr-stagger">
                @foreach ($related as $relatedProduct)
                    <div class="col-6 col-md-4 col-xl-3">
                        @include('storefront.partials.product-card', ['product' => $relatedProduct])
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>

<div class="modal fade vr-lightbox" id="vrLightbox" tabindex="-1" role="dialog" aria-modal="true" aria-label="{{ $product->name }} image">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-body text-center">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <img id="vrLightboxImg" src="" alt="{{ $product->name }}">
            </div>
        </div>
    </div>
</div>

<div class="vr-mobile-bar d-lg-none" id="vrMobileBar">
    <div class="container d-flex align-items-center gap-2">
        <div class="vr-qty flex-shrink-0">
            <button type="button" class="qty-minus" data-step="-1" aria-label="Decrease quantity"><i class="bi bi-dash"></i></button>
            <input type="number" value="1" min="1" aria-label="Quantity" id="vrBarQty">
            <button type="button" data-step="1" aria-label="Increase quantity"><i class="bi bi-plus"></i></button>
        </div>
        <button type="button" class="btn btn-vr-outline flex-grow-1 js-bar-btn js-bar-add" {{ $out ? 'disabled' : '' }}>
            <i class="bi bi-bag-plus me-1"></i> Add
        </button>
        <button type="button" class="btn btn-vr flex-grow-1 js-bar-btn js-bar-buy" {{ $out ? 'disabled' : '' }}>
            <i class="bi bi-lightning-charge me-1"></i> Buy Now
        </button>
    </div>
</div>

@endsection

@push('scripts')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@@type": "ListItem",
            "position": 1,
            "name": {!! json_encode(store_name(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
            "item": {!! json_encode(route('home'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        },
        @if ($category)
        {
            "@@type": "ListItem",
            "position": 2,
            "name": {!! json_encode($category->name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
            "item": {!! json_encode(route('shop.category', $category->slug), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        },
        {
            "@@type": "ListItem",
            "position": 3,
            "name": {!! json_encode($product->name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
            "item": {!! json_encode(route('product.show', $product), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        }
        @else
        {
            "@@type": "ListItem",
            "position": 2,
            "name": {!! json_encode($product->name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
            "item": {!! json_encode(route('product.show', $product), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        }
        @endif
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Product",
    "name": {!! json_encode($product->name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
    "image": {!! json_encode($img ? image_url($img) : null, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
    @if ($product->description)"description": {!! json_encode(strip_tags((string) $product->description), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},@endif
    @if ($product->sku)"sku": {!! json_encode($product->sku, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},@endif
    "brand": {
        "@@type": "Brand",
        "name": {!! json_encode(store_name(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
    },
    "offers": {
        "@@type": "Offer",
        "url": {!! json_encode(route('product.show', $product), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
        "priceCurrency": "INR",
        "price": {!! json_encode(number_format((float) $product->selling_price, 2, '.', ''), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
        "availability": {!! json_encode($out ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
        "itemCondition": "https://schema.org/NewCondition"
    }
    @if ($product->review_count > 0),
    "aggregateRating": {
        "@@type": "AggregateRating",
        "ratingValue": {!! json_encode((string) $product->review_rating, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
        "reviewCount": {!! json_encode((string) $product->review_count, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
    }
    @endif
}
</script>
@endpush

@push('scripts')
<script>
window.dataLayer.push({!! json_encode($viewItemPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!});
</script>
@endpush

@push('scripts')
<script>
(function () {
    var form = document.getElementById('addToCartForm');
    if (!form) return;

    if (document.getElementById('vrMobileBar') && window.innerWidth < 992) {
        document.body.classList.add('has-mobile-bar');
    }

    var buyNowInput = form.querySelector('input[name="buy_now"]');
    var mainQty = document.getElementById('vrBuyQty');
    var barQty = document.getElementById('vrBarQty');

    if (mainQty && barQty) {
        [mainQty, barQty].forEach(function (input) {
            input.addEventListener('input', function () {
                var other = input === mainQty ? barQty : mainQty;
                if (other) other.value = input.value;
            });
        });
    }

    document.querySelectorAll('.js-bar-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!form.checkValidity()) { form.reportValidity(); return; }
            if (barQty) form.querySelector('input[name="quantity"]').value = barQty.value;
            if (buyNowInput) buyNowInput.value = btn.classList.contains('js-bar-buy') ? '1' : '0';
            form.submit();
        });
    });

    var list = document.getElementById('vrVariantList');
    if (list) {
        function setButtonsDisabled(disabled) {
            form.querySelectorAll('button[type="submit"]').forEach(function (b) { b.disabled = disabled; });
            document.querySelectorAll('.js-bar-btn').forEach(function (b) { b.disabled = disabled; });
        }
        list.addEventListener('change', function (e) {
            var radio = e.target;
            if (!radio || radio.type !== 'radio') return;
            var label = radio.closest('.vr-variant-option');
            if (!label) return;
            list.querySelectorAll('.vr-variant-option').forEach(function (l) { l.classList.remove('is-selected'); });
            label.classList.add('is-selected');
            var nowEl = document.getElementById('vrNowPrice');
            var mrpEl = document.getElementById('vrMrpPrice');
            var instock = parseInt(label.dataset.instock || '1', 10) > 0;
            if (nowEl) nowEl.textContent = label.dataset.priceFmt;
            if (mrpEl) {
                if (parseFloat(label.dataset.mrp) > parseFloat(label.dataset.price)) {
                    mrpEl.textContent = label.dataset.mrpFmt;
                    mrpEl.style.display = '';
                } else {
                    mrpEl.style.display = 'none';
                }
            }
            setButtonsDisabled(!instock);
        });
    }
})();
</script>
@endpush

@push('scripts')
<script>
(function () {
    if (window.innerWidth < 992) return;

    var container = document.getElementById('vrZoomContainer');
    var img = document.getElementById('vrMainImage');
    var lens = document.getElementById('vrZoomLens');
    var result = document.getElementById('vrZoomResult');
    if (!container || !img || !lens || !result || img.tagName !== 'IMG') return;

    var zoomFactor = 2.5;

    function activateZoom() {
        var src = img.src;
        if (!src) return;

        result.style.backgroundImage = 'url(' + src + ')';
        var bgW = img.offsetWidth * zoomFactor;
        var bgH = img.offsetHeight * zoomFactor;
        result.style.backgroundSize = bgW + 'px ' + bgH + 'px';

        lens.style.display = 'block';
        result.style.display = 'block';
    }

    function deactivateZoom() {
        lens.style.display = 'none';
        result.style.display = 'none';
    }

    function moveLens(e) {
        var rect = img.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var y = e.clientY - rect.top;

        var lensW = lens.offsetWidth / 2;
        var lensH = lens.offsetHeight / 2;

        var left = x - lensW;
        var top = y - lensH;

        if (left < 0) left = 0;
        if (top < 0) top = 0;
        if (left > img.offsetWidth - lens.offsetWidth) left = img.offsetWidth - lens.offsetWidth;
        if (top > img.offsetHeight - lens.offsetHeight) top = img.offsetHeight - lens.offsetHeight;

        lens.style.left = left + 'px';
        lens.style.top = top + 'px';

        var ratioX = left / (img.offsetWidth - lens.offsetWidth);
        var ratioY = top / (img.offsetHeight - lens.offsetHeight);

        var bgW = img.offsetWidth * zoomFactor;
        var bgH = img.offsetHeight * zoomFactor;
        var bgX = -(ratioX * (bgW - result.offsetWidth));
        var bgY = -(ratioY * (bgH - result.offsetHeight));

        result.style.backgroundPosition = bgX + 'px ' + bgY + 'px';
    }

    container.addEventListener('mouseenter', activateZoom);
    container.addEventListener('mouseleave', deactivateZoom);
    container.addEventListener('mousemove', moveLens);
})();
</script>
@endpush