@extends('storefront.layouts.app')

@section('title', setting('meta_title') ?: (store_name() . ' - ' . setting('store_tagline', 'PURE BY NATURE')))

@section('content')

@if ($banners->isNotEmpty())
    <section class="vr-hero">
        <div id="vrHeroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="6000">
            <div class="carousel-indicators">
                @foreach ($banners as $i => $banner)
                    <button type="button" data-bs-target="#vrHeroCarousel" data-bs-slide-to="{{ $i }}" class="{{ $i === 0 ? 'active' : '' }}" aria-label="Slide {{ $i + 1 }}"></button>
                @endforeach
            </div>
            <div class="carousel-inner">
                @foreach ($banners as $i => $banner)
                    @php $img = $banner->image; @endphp
                    <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                        @if ($banner->link)
                            <a href="{{ $banner->link }}" class="d-block">
                                <div class="vr-hero-img" style="background-image:url('{{ image_url($img) }}');"></div>
                            </a>
                        @else
                            <div class="vr-hero-img" style="background-image:url('{{ image_url($img) }}');"></div>
                        @endif
                    </div>
                @endforeach
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#vrHeroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#vrHeroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </section>
@else
    <section class="vr-hero">
        <div class="vr-hero-img" style="background:linear-gradient(120deg, #263D25 0%, #6F8736 100%);">
            <div class="d-flex align-items-center" style="min-height:520px;">
                <div class="container">
                    <h1 class="hero-title mb-3" style="color:#fff;">{{ store_name() }}</h1>
                    <p class="lead mb-4" style="color:rgba(255,255,255,0.8);max-width:520px;">Handcrafted skincare, herbal teas and wellness essentials made with nature in mind.</p>
                    <a href="{{ route('shop.index') }}" class="btn btn-vr">
                        <i class="bi bi-bag me-2"></i>Shop Now
                    </a>
                </div>
            </div>
        </div>
    </section>
@endif

<section class="vr-trust py-4">
    <div class="container">
        <div class="row g-3 vr-stagger">
            <div class="col-6 col-lg-3">
                <div class="t-item">
                    <i class="bi bi-truck"></i>
                    <div>
                        <div class="t-title">Free Shipping</div>
                        <div class="t-sub">On orders above {{ format_price(setting('free_shipping_threshold', 499)) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="t-item">
                    <i class="bi bi-cash-coin"></i>
                    <div>
                        <div class="t-title">COD Available</div>
                        <div class="t-sub">Pay at your doorstep</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="t-item">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <div>
                        <div class="t-title">Easy Returns</div>
                        <div class="t-sub">{{ setting('return_window_days', 7) }}-day returns</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="t-item">
                    <i class="bi bi-flower1"></i>
                    <div>
                        <div class="t-title">Cruelty-Free</div>
                        <div class="t-sub">100% natural &amp; kind</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="vr-section">
    <div class="container">
        <div class="d-flex align-items-end justify-content-between mb-4 vr-animate-in">
            <div>
                <h2 class="vr-section-title mb-1">Featured</h2>
                <p class="vr-section-sub mb-0">Handpicked favourites from our collection</p>
            </div>
            <a href="{{ route('shop.index') }}" class="vr-link-more">View all <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-4 vr-stagger">
            @forelse ($featured as $product)
                <div class="col-6 col-md-4 col-xl-3">
                    @include('storefront.partials.product-card', ['product' => $product])
                </div>
            @empty
                <div class="col-12">
                    <div class="vr-empty bg-white rounded-4" style="box-shadow:var(--vr-shadow-xs);">
                        <i class="bi bi-box"></i>
                        <p class="mt-2 mb-0">No featured products yet.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</section>

@if ($bestsellers->isNotEmpty())
    <section class="vr-section pt-0">
        <div class="container">
            <div class="d-flex align-items-end justify-content-between mb-4 vr-animate-in">
                <div>
                    <h2 class="vr-section-title mb-1">Bestsellers</h2>
                    <p class="vr-section-sub mb-0">Loved by thousands of happy customers</p>
                </div>
                <a href="{{ route('shop.index') }}?sort=popular" class="vr-link-more">View all <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="row g-4 vr-stagger">
                @foreach ($bestsellers as $product)
                    <div class="col-6 col-md-4 col-xl-3">
                        @include('storefront.partials.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if ($newArrivals->isNotEmpty())
    <section class="vr-section pt-0">
        <div class="container">
            <div class="d-flex align-items-end justify-content-between mb-4 vr-animate-in">
                <div>
                    <h2 class="vr-section-title mb-1">New Arrivals</h2>
                    <p class="vr-section-sub mb-0">Fresh from our lab to your shelf</p>
                </div>
                <a href="{{ route('shop.index') }}?sort=newest" class="vr-link-more">View all <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="row g-4 vr-stagger">
                @foreach ($newArrivals as $product)
                    <div class="col-6 col-md-4 col-xl-3">
                        @include('storefront.partials.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if ($categories->isNotEmpty())
    <section class="vr-section pt-0">
        <div class="container">
            <div class="mb-4 text-center vr-animate-in">
                <h2 class="vr-section-title mb-1">Shop by Category</h2>
                <p class="vr-section-sub mb-0">Explore our collections</p>
            </div>
            <div class="row g-3 justify-content-center vr-stagger">
                @foreach ($categories as $cat)
                    <div class="col-4 col-md-3">
                        <a href="{{ route('shop.category', $cat->slug) }}" class="vr-cat-card d-block text-center">
                            <div class="vr-cat-img-wrap">
                                @if ($cat->image)
                                    <img src="{{ image_url($cat->image) }}" alt="{{ $cat->name }}" loading="lazy">
                                @else
                                    <div class="vr-cat-placeholder"><i class="bi bi-grid-3x3-gap"></i></div>
                                @endif
                            </div>
                            <span class="vr-cat-name">{{ $cat->name }}</span>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

<section class="vr-section pt-0">
    <div class="container">
        <div class="vr-cta-band p-4 p-md-5 text-center vr-animate-in">
            <div class="cta-inner">
                <div class="vr-kicker justify-content-center mb-2">PURE BY NATURE</div>
                <h2 class="vr-section-title mb-2">Join the {{ strtoupper(store_name()) }} family</h2>
                <p class="vr-section-sub mx-auto mb-4" style="max-width:520px;">Get skincare tips, early access to new drops and exclusive offers straight to your inbox.</p>
                <form class="mx-auto d-flex gap-2" style="max-width:460px;" action="{{ route('newsletter.subscribe') }}" method="POST" novalidate>
                    @csrf
                    <div class="flex-grow-1">
                        <input type="email" name="email" class="form-control" placeholder="Your email address">
                        <div class="invalid-feedback"></div>
                    </div>
                    <button type="submit" class="btn btn-vr text-nowrap">
                        <i class="bi bi-send me-1"></i>Subscribe
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
@php
    $_logo = setting('store_logo');
    if (! $_logo) { foreach (['svg', 'png', 'webp', 'jpg'] as $_ext) { if (file_exists(public_path('images/logo.' . $_ext))) { $_logo = 'images/logo.' . $_ext; break; } } }
@endphp
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Organization",
    "name": {!! json_encode(store_name(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
    "url": {!! json_encode(route('home'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
    "logo": {!! json_encode(image_url($_logo, 'favicon.ico'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
    @if (setting('store_email'))"email": {!! json_encode(setting('store_email'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},@endif
    @if (setting('store_phone'))"telephone": {!! json_encode(setting('store_phone'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},@endif
    @if (setting('store_address'))"address": {!! json_encode(setting('store_address'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},@endif
    "sameAs": [
        @php
            $_socials = array_filter([
                setting('facebook_url'),
                setting('instagram_url'),
                setting('twitter_url'),
                setting('youtube_url'),
                setting('linkedin_url'),
            ]);
        @endphp
        @foreach ($_socials as $_social)
        {!! json_encode($_social, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}@if (! $loop->last),@endif
        @endforeach
    ]
}
</script>
@endpush