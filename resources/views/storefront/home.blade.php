@extends('storefront.layouts.app')

@section('title', setting('meta_title') ?: (store_name() . ' - ' . setting('store_tagline', 'PURE BY NATURE')))
@section('meta_description', setting('meta_description'))
@section('og_title', setting('meta_title') ?: (store_name() . ' - ' . setting('store_tagline', 'PURE BY NATURE')))
@section('canonical', route('home'))

@section('content')

@if ($banners->isNotEmpty())
    <section class="vr-hero">
        <h1 class="visually-hidden">{{ store_name() }}{{ setting('store_tagline') ? ' — '.setting('store_tagline') : '' }}</h1>
        <div id="vrHeroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="6000">
            <div class="carousel-indicators">
                @foreach ($banners as $i => $banner)
                    <button type="button" data-bs-target="#vrHeroCarousel" data-bs-slide-to="{{ $i }}" class="{{ $i === 0 ? 'active' : '' }}" aria-label="Slide {{ $i + 1 }}"></button>
                @endforeach
            </div>
            <div class="carousel-inner">
                @foreach ($banners as $i => $banner)
                    @php
                        $desktopImage = image_url($banner->image);
                        $mobileImage = $banner->mobile_image ? image_url($banner->mobile_image) : null;
                        $bannerAlt = store_name().' banner '.($i + 1);
                    @endphp
                    <div class="carousel-item {{ $i === 0 ? 'active' : '' }}">
                        @if ($banner->link)
                            <a href="{{ $banner->link }}" class="d-block">
                                <picture>
                                    @if ($mobileImage)
                                        <source media="(max-width: 767.98px)" srcset="{{ $mobileImage }}">
                                    @endif
                                    <img src="{{ $desktopImage }}" alt="{{ $bannerAlt }}" class="vr-hero-pic" width="1920" height="600" decoding="async" @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif>
                                </picture>
                            </a>
                        @else
                            <picture>
                                @if ($mobileImage)
                                    <source media="(max-width: 767.98px)" srcset="{{ $mobileImage }}">
                                @endif
                                <img src="{{ $desktopImage }}" alt="{{ $bannerAlt }}" class="vr-hero-pic" width="1920" height="600" decoding="async" @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif>
                            </picture>
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

@if ($featured->isNotEmpty())
<section class="vr-section vr-section-featured">
    <div class="container">
        <div class="d-flex align-items-end justify-content-between mb-4 vr-animate-in">
            <div>
                <div class="vr-section-kicker vr-kicker-featured">
                    <i class="ri-sparkling-fill"></i> CURATED FORMULATIONS
                </div>
                <h2 class="vr-section-title mb-1">Featured Collection</h2>
                <p class="vr-section-sub mb-0">Handpicked favourites from our authentic Ayurvedic collection</p>
            </div>
            <a href="{{ route('shop.index') }}" class="vr-link-more">View all <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-3 g-md-4 vr-stagger">
            @foreach ($featured as $product)
                <div class="col-6 col-xl-3">
                    @include('storefront.partials.product-card', ['product' => $product, 'cardFlag' => 'featured'])
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@include('storefront.partials.section-banner', ['banner' => $sectionBanners->get('after_featured')])

@if ($bestsellers->isNotEmpty())
    <section class="vr-section vr-section-bestsellers py-5">
        <div class="container">
            <div class="d-flex align-items-end justify-content-between mb-4 vr-animate-in">
                <div>
                    <div class="vr-section-kicker vr-kicker-bestsellers">
                        <i class="ri-fire-fill"></i> MOST LOVED BY CUSTOMERS
                    </div>
                    <h2 class="vr-section-title mb-1">Our Bestsellers</h2>
                    <p class="vr-section-sub mb-0">Loved &amp; trusted by thousands of happy Ayurvedic routines</p>
                </div>
                <a href="{{ route('shop.index') }}?sort=popular" class="vr-link-more">View all <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="row g-3 g-md-4 vr-stagger">
                @foreach ($bestsellers as $product)
                    <div class="col-6 col-xl-3">
                        @include('storefront.partials.product-card', ['product' => $product, 'cardFlag' => 'bestseller', 'rank' => $loop->iteration])
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@include('storefront.partials.section-banner', ['banner' => $sectionBanners->get('after_bestsellers')])

@if ($newArrivals->isNotEmpty())
    <section class="vr-section vr-section-newarrivals py-5">
        <div class="container">
            <div class="d-flex align-items-end justify-content-between mb-4 vr-animate-in">
                <div>
                    <div class="vr-section-kicker vr-kicker-newarrivals">
                        <i class="ri-leaf-line"></i> FRESH BATCH DROPS
                    </div>
                    <h2 class="vr-section-title mb-1">New Arrivals</h2>
                    <p class="vr-section-sub mb-0">Fresh botanical herbs &amp; new formulations crafted with care</p>
                </div>
                <a href="{{ route('shop.index') }}?sort=newest" class="vr-link-more">View all <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="row g-3 g-md-4 vr-stagger">
                @foreach ($newArrivals as $product)
                    <div class="col-6 col-xl-3">
                        @include('storefront.partials.product-card', ['product' => $product, 'cardFlag' => 'new_arrival'])
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

@include('storefront.partials.section-banner', ['banner' => $sectionBanners->get('after_new_arrivals')])

@if ($categories->isNotEmpty())
    <section class="vr-section vr-section-categories py-5">
        <div class="container">
            <div class="mb-4 text-center vr-animate-in">
                <div class="vr-section-kicker vr-kicker-categories justify-content-center">
                    <i class="ri-grid-fill"></i> EXPLORE BY CONCERN
                </div>
                <h2 class="vr-section-title mb-1">Shop by Category</h2>
                <p class="vr-section-sub mb-0">Explore our specialized botanical ranges</p>
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
                            <span class="vr-cat-explore d-none d-md-inline-block">Explore &rarr;</span>
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