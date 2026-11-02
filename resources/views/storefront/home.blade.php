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
            <a href="{{ route('shop.index', ['is_featured' => 1]) }}" class="vr-link-more">View all <i class="bi bi-arrow-right"></i></a>
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
                <a href="{{ route('shop.index', ['is_bestseller' => 1]) }}" class="vr-link-more">View all <i class="bi bi-arrow-right"></i></a>
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

@if (isset($reels) && $reels->isNotEmpty())
    <section class="vr-section vr-section-reels py-5" id="vrReelsSection">
        <div class="container">
            <div class="text-center mb-4 vr-animate-in">
                <h2 class="vr-section-title-reels">Watch &amp; Shop</h2>
            </div>

            {{-- Horizontal Reel Carousel --}}
            <div class="vr-reels-carousel" id="vrReelsCarousel">
                @foreach ($reels as $index => $reel)
                    @php
                        $taggedProduct = $reel->product;
                        $prodImg = $taggedProduct?->getPrimaryImage()?->image_path;
                        $likes = (int) ($reel->likes_count ?: 120);
                        $shares = (int) ($reel->shares_count ?: 45);
                        $views = $reel->views_count ?: '18.3K';
                    @endphp
                    <div class="vr-reel-card"
                         data-index="{{ $index }}"
                         data-id="{{ $reel->id }}"
                         data-title="{{ $reel->title ?: 'Vanriti Ayurvedic Ritual' }}"
                         data-caption="{{ $reel->caption }}"
                         data-video="{{ $reel->video_source }}"
                         data-instagram="{{ $reel->instagram_url }}"
                         data-poster="{{ $reel->poster }}"
                         data-views="{{ $views }}"
                         data-likes="{{ $likes }}"
                         data-shares="{{ $shares }}"
                         data-like-url="{{ route('reels.like', $reel) }}"
                         data-share-url="{{ route('reels.share', $reel) }}"
                         @if ($taggedProduct)
                             data-has-product="true"
                             data-product-name="{{ $taggedProduct->name }}"
                             data-product-price="{{ format_price($taggedProduct->selling_price) }}"
                             @if ($taggedProduct->mrp > $taggedProduct->selling_price)
                                 data-product-mrp="{{ format_price($taggedProduct->mrp) }}"
                             @endif
                             data-product-url="{{ route('product.show', $taggedProduct) }}"
                             data-product-img="{{ $prodImg ? image_url($prodImg) : asset('images/placeholder.png') }}"
                             data-product-id="{{ $taggedProduct->id }}"
                             data-add-url="{{ route('cart.add', $taggedProduct) }}"
                         @else
                             data-has-product="false"
                         @endif
                    >
                        {{-- 9:16 Auto-Playing Video Box --}}
                        <div class="vr-reel-video-wrap js-trigger-modal">
                            @if ($reel->video_source)
                                <video class="vr-reel-inline-video"
                                       src="{{ $reel->video_source }}"
                                       poster="{{ $reel->poster }}"
                                       autoplay
                                       muted
                                       loop
                                       playsinline
                                       preload="auto"></video>
                            @else
                                <img src="{{ $reel->poster }}" alt="{{ $reel->title }}" class="vr-reel-inline-video" style="object-fit:cover;" loading="lazy">
                            @endif

                            {{-- Floating Overlays inside Video at Bottom --}}
                            <div class="vr-reel-video-bottom-bar">
                                <span class="vr-reel-views-pill">
                                    {{ $views }} Views
                                </span>
                                <div class="vr-reel-actions-badge">
                                    <button type="button" class="vr-reel-act-btn js-card-like-btn" data-id="{{ $reel->id }}" data-url="{{ route('reels.like', $reel) }}" title="Like">
                                        <i class="bi bi-heart"></i>
                                    </button>
                                    <button type="button" class="vr-reel-act-btn js-card-share-btn" data-id="{{ $reel->id }}" data-url="{{ route('reels.share', $reel) }}" data-share-url="{{ $reel->instagram_url ?: ($taggedProduct ? route('product.show', $taggedProduct) : route('home')) }}" data-title="{{ $reel->title }}" title="Share">
                                        <i class="bi bi-send"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Attached Product Block --}}
                        @if ($taggedProduct)
                            <div class="vr-reel-card-product">
                                <a href="{{ route('product.show', $taggedProduct) }}" class="vr-reel-prod-details">
                                    <img src="{{ $prodImg ? image_url($prodImg) : asset('images/placeholder.png') }}" alt="{{ $taggedProduct->name }}" class="vr-reel-prod-img">
                                    <div class="vr-reel-prod-meta">
                                        <div class="vr-reel-prod-name" title="{{ $taggedProduct->name }}">{{ Str::limit($taggedProduct->name, 22) }}</div>
                                        <div class="vr-reel-prod-prices">
                                            <span class="vr-reel-prod-price">{{ format_price($taggedProduct->selling_price) }}</span>
                                            @if ($taggedProduct->mrp > $taggedProduct->selling_price)
                                                <del class="vr-reel-prod-mrp">{{ format_price($taggedProduct->mrp) }}</del>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                                <form method="POST" action="{{ route('cart.add', $taggedProduct) }}" class="m-0">
                                    @csrf
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn vr-reel-card-buy-btn">
                                        Buy Now
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Fullscreen Reel Video Modal Player (Exact match to Krishna Ayurved Screenshot 2) --}}
    <div class="vr-reel-modal" id="vrReelModal" tabindex="-1" aria-hidden="true">
        <div class="vr-reel-modal-backdrop" id="vrReelModalBackdrop"></div>

        {{-- Nav Buttons --}}
        <button type="button" class="vr-modal-nav-arrow prev" id="vrModalPrevBtn" aria-label="Previous reel">
            <i class="bi bi-chevron-left"></i>
        </button>
        <button type="button" class="vr-modal-nav-arrow next" id="vrModalNextBtn" aria-label="Next reel">
            <i class="bi bi-chevron-right"></i>
        </button>

        <div class="vr-reel-modal-dialog" id="vrModalDialog">
            {{-- Top Controls Bar --}}
            <div class="vr-modal-top-bar">
                <button type="button" class="vr-modal-ctrl-btn" id="vrModalFullscreenBtn" title="Fullscreen">
                    <i class="bi bi-arrows-angle-expand"></i>
                </button>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="vr-modal-ctrl-btn" id="vrModalSoundBtn" title="Sound toggle">
                        <i class="bi bi-volume-up-fill"></i>
                    </button>
                    <button type="button" class="vr-modal-ctrl-btn vr-modal-close" id="vrReelCloseBtn" title="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            {{-- Right-Side Vertical Floating Action Rail --}}
            <div class="vr-modal-action-rail">
                <div class="vr-rail-item">
                    <i class="bi bi-eye fs-5"></i>
                    <span class="vr-rail-number" id="vrModalViewsText">18.3K</span>
                    <span class="vr-rail-sub">Views</span>
                </div>
                <button type="button" class="vr-rail-btn" id="vrModalLikeBtn" title="Like this reel">
                    <i class="bi bi-heart fs-5" id="vrModalLikeIcon"></i>
                    <span class="vr-rail-number" id="vrModalLikesCount">192</span>
                </button>
                <button type="button" class="vr-rail-btn" id="vrModalShareBtn" title="Share this reel">
                    <i class="bi bi-send fs-5"></i>
                    <span class="vr-rail-number" id="vrModalSharesCount">104</span>
                </button>
                <button type="button" class="vr-rail-btn" id="vrModalCartIconBtn" title="View Cart">
                    <i class="bi bi-cart3 fs-5"></i>
                </button>
            </div>

            {{-- Video Element --}}
            <video id="vrReelVideo" class="vr-reel-video-element" playsinline loop preload="auto"></video>

            {{-- Fallback Card if Video MP4 is missing (Prevents "This content is blocked" error) --}}
            <div class="vr-reel-ig-fallback d-none" id="vrModalIgFallback">
                <div class="p-3">
                    <i class="bi bi-instagram fs-1 text-danger mb-2"></i>
                    <h5 class="text-white fw-bold mb-1">Watch on Instagram</h5>
                    <p class="text-white-50 small mb-3">Instagram restricts playing this video directly within an iframe on external sites. Tap below to watch on our official page!</p>
                    <a href="#" target="_blank" class="btn btn-danger btn-sm rounded-pill px-4 fw-bold" id="vrModalOpenInstaLink">
                        <i class="bi bi-instagram me-1"></i> Open Instagram Reel
                    </a>
                </div>
            </div>

            {{-- Play/Pause Indicator Overlay --}}
            <div class="vr-reel-play-overlay d-none" id="vrReelPlayOverlay">
                <i class="bi bi-play-circle-fill"></i>
            </div>

            {{-- Bottom Frosted Product Drawer --}}
            <div class="vr-modal-product-drawer d-none" id="vrModalProductDrawer">
                <div class="vr-modal-product-glass-card">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <img src="" alt="" class="vr-modal-drawer-thumb" id="vrModalProductImg">
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="vr-modal-drawer-title" id="vrModalProductName"></span>
                                <a href="#" target="_blank" class="vr-modal-drawer-ext" id="vrModalProductExtLink" title="View product details">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 mt-0.5">
                                <span class="vr-modal-drawer-price" id="vrModalProductPrice"></span>
                                <del class="vr-modal-drawer-mrp text-muted small d-none" id="vrModalProductMrp"></del>
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="" id="vrModalCartForm" class="m-0">
                        @csrf
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="btn vr-modal-buy-now-btn" id="vrModalBuyBtn">
                            Buy Now
                        </button>
                    </form>
                </div>
            </div>

            {{-- Realtime Progress Bar --}}
            <div class="vr-reel-progress-bar">
                <div class="vr-reel-progress-fill" id="vrReelProgressFill"></div>
            </div>
        </div>
    </div>

    {{-- Toast Notification for Share / Copy --}}
    <div class="vr-reel-toast d-none" id="vrReelToast">Link copied to clipboard!</div>
@endif

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
                <a href="{{ route('shop.index', ['is_new_arrival' => 1]) }}" class="vr-link-more">View all <i class="bi bi-arrow-right"></i></a>
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

@push('scripts')
<script nonce="{{ $cspNonce }}">
(function () {
    var carousel = document.getElementById('vrReelsCarousel');
    var modal = document.getElementById('vrReelModal');
    var backdrop = document.getElementById('vrReelModalBackdrop');
    var closeBtn = document.getElementById('vrReelCloseBtn');
    var video = document.getElementById('vrReelVideo');
    var fallback = document.getElementById('vrModalIgFallback');
    var fallbackLink = document.getElementById('vrModalOpenInstaLink');
    var playOverlay = document.getElementById('vrReelPlayOverlay');
    var soundBtn = document.getElementById('vrModalSoundBtn');
    var fullscreenBtn = document.getElementById('vrModalFullscreenBtn');
    var progressFill = document.getElementById('vrReelProgressFill');
    var modalPrevBtn = document.getElementById('vrModalPrevBtn');
    var modalNextBtn = document.getElementById('vrModalNextBtn');
    var toast = document.getElementById('vrReelToast');
    var csrfToken = '{{ csrf_token() }}';

    // Modal Rail Elements
    var modalViews = document.getElementById('vrModalViewsText');
    var modalLikeBtn = document.getElementById('vrModalLikeBtn');
    var modalLikeIcon = document.getElementById('vrModalLikeIcon');
    var modalLikesCount = document.getElementById('vrModalLikesCount');
    var modalShareBtn = document.getElementById('vrModalShareBtn');
    var modalSharesCount = document.getElementById('vrModalSharesCount');
    var modalCartIconBtn = document.getElementById('vrModalCartIconBtn');

    // Product Drawer Elements
    var drawer = document.getElementById('vrModalProductDrawer');
    var drawerImg = document.getElementById('vrModalProductImg');
    var drawerName = document.getElementById('vrModalProductName');
    var drawerPrice = document.getElementById('vrModalProductPrice');
    var drawerMrp = document.getElementById('vrModalProductMrp');
    var drawerExtLink = document.getElementById('vrModalProductExtLink');
    var cartForm = document.getElementById('vrModalCartForm');
    var buyBtn = document.getElementById('vrModalBuyBtn');

    if (!carousel) return;

    // 1. Ensure all inline videos in the carousel play muted simultaneously
    var inlineVideos = carousel.querySelectorAll('.vr-reel-inline-video');
    inlineVideos.forEach(function (v) {
        if (v.tagName === 'VIDEO') {
            v.muted = true;
            v.play().catch(function () {});
        }
    });

    var cards = Array.from(carousel.querySelectorAll('.vr-reel-card'));
    var currentIndex = 0;

    function showToast(msg) {
        if (!toast) return;
        toast.textContent = msg;
        toast.classList.remove('d-none');
        setTimeout(function () {
            toast.classList.add('d-none');
        }, 2500);
    }

    function isReelLiked(id) {
        return localStorage.getItem('vanriti_reel_liked_' + id) === '1';
    }

    function setReelLiked(id) {
        localStorage.setItem('vanriti_reel_liked_' + id, '1');
    }

    function updateSoundIcon(isMuted) {
        if (!soundBtn) return;
        soundBtn.innerHTML = isMuted ? '<i class="bi bi-volume-mute-fill"></i>' : '<i class="bi bi-volume-up-fill"></i>';
    }

    function loadReel(index) {
        if (index < 0 || index >= cards.length) return;
        currentIndex = index;
        var card = cards[index];

        var reelId = card.getAttribute('data-id');
        var videoSrc = card.getAttribute('data-video');
        var instaUrl = card.getAttribute('data-instagram') || '#';
        var poster = card.getAttribute('data-poster') || '';
        var views = card.getAttribute('data-views') || '18.3K';
        var likes = parseInt(card.getAttribute('data-likes'), 10) || 0;
        var shares = parseInt(card.getAttribute('data-shares'), 10) || 0;
        var hasProduct = card.getAttribute('data-has-product') === 'true';

        progressFill.style.width = '0%';
        modalViews.textContent = views;
        modalLikesCount.textContent = likes;
        modalSharesCount.textContent = shares;

        // Check liked state
        if (isReelLiked(reelId)) {
            modalLikeIcon.className = 'bi bi-heart-fill text-danger fs-5';
            modalLikeBtn.classList.add('liked');
        } else {
            modalLikeIcon.className = 'bi bi-heart fs-5';
            modalLikeBtn.classList.remove('liked');
        }

        if (videoSrc) {
            fallback.classList.add('d-none');
            video.classList.remove('d-none');
            video.poster = poster;
            video.src = videoSrc;
            video.muted = false; // Play with sound by default when clicked!
            updateSoundIcon(false);

            var playPromise = video.play();
            if (playPromise !== undefined) {
                playPromise.then(function () {
                    playOverlay.classList.add('d-none');
                }).catch(function () {
                    // If browser blocked unmuted autoplay, mute and resume
                    video.muted = true;
                    updateSoundIcon(true);
                    video.play().catch(function () {});
                });
            }
        } else {
            // No direct MP4: show fallback card instead of blocked iframe
            video.pause();
            video.src = '';
            video.classList.add('d-none');
            fallback.classList.remove('d-none');
            if (fallbackLink) fallbackLink.href = instaUrl;
        }

        if (hasProduct) {
            drawer.classList.remove('d-none');
            drawerImg.src = card.getAttribute('data-product-img') || '';
            drawerName.textContent = card.getAttribute('data-product-name') || '';
            drawerPrice.textContent = card.getAttribute('data-product-price') || '';
            var mrp = card.getAttribute('data-product-mrp');
            if (mrp) {
                drawerMrp.textContent = mrp;
                drawerMrp.classList.remove('d-none');
            } else {
                drawerMrp.classList.add('d-none');
            }
            if (drawerExtLink) drawerExtLink.href = card.getAttribute('data-product-url') || '#';
            cartForm.action = card.getAttribute('data-add-url') || '';
        } else {
            drawer.classList.add('d-none');
        }

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        if (video) {
            video.pause();
            video.src = '';
        }
        progressFill.style.width = '0%';
    }

    // Card Trigger (click on video box opens modal)
    cards.forEach(function (card) {
        var trigger = card.querySelector('.js-trigger-modal');
        if (trigger) {
            trigger.addEventListener('click', function (e) {
                // If clicked on like or share button inside, don't open modal
                if (e.target.closest('.vr-reel-act-btn')) return;
                var idx = parseInt(card.getAttribute('data-index'), 10) || 0;
                loadReel(idx);
            });
        }

        // Inline Card Like Button
        var cardLikeBtn = card.querySelector('.js-card-like-btn');
        if (cardLikeBtn) {
            var reelId = card.getAttribute('data-id');
            if (isReelLiked(reelId)) {
                cardLikeBtn.classList.add('liked');
                cardLikeBtn.innerHTML = '<i class="bi bi-heart-fill"></i>';
            }
            cardLikeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                handleLike(card);
            });
        }

        // Inline Card Share Button
        var cardShareBtn = card.querySelector('.js-card-share-btn');
        if (cardShareBtn) {
            cardShareBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                handleShare(card);
            });
        }
    });

    function handleLike(card) {
        var reelId = card.getAttribute('data-id');
        var likeUrl = card.getAttribute('data-like-url');
        var currentLikes = parseInt(card.getAttribute('data-likes'), 10) || 0;

        if (isReelLiked(reelId)) {
            showToast('You already liked this ritual!');
            return;
        }

        setReelLiked(reelId);
        var newLikes = currentLikes + 1;
        card.setAttribute('data-likes', newLikes);

        // Update card button
        var cardBtn = card.querySelector('.js-card-like-btn');
        if (cardBtn) {
            cardBtn.classList.add('liked');
            cardBtn.innerHTML = '<i class="bi bi-heart-fill"></i>';
        }

        // Update modal if currently viewing this reel
        if (currentIndex === parseInt(card.getAttribute('data-index'), 10)) {
            modalLikesCount.textContent = newLikes;
            modalLikeIcon.className = 'bi bi-heart-fill text-danger fs-5';
            modalLikeBtn.classList.add('liked');
        }

        // Send to server
        if (likeUrl) {
            fetch(likeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            }).catch(function () {});
        }
    }

    function handleShare(card) {
        var reelId = card.getAttribute('data-id');
        var shareUrl = card.getAttribute('data-share-url');
        var targetLink = card.getAttribute('data-instagram') || card.getAttribute('data-product-url') || window.location.href;
        var reelTitle = card.getAttribute('data-title') || 'Vanriti Ayurvedic Care';

        var currentShares = parseInt(card.getAttribute('data-shares'), 10) || 0;
        var newShares = currentShares + 1;
        card.setAttribute('data-shares', newShares);

        if (currentIndex === parseInt(card.getAttribute('data-index'), 10)) {
            modalSharesCount.textContent = newShares;
        }

        // Ping share endpoint
        if (shareUrl) {
            fetch(shareUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            }).catch(function () {});
        }

        if (navigator.share) {
            navigator.share({
                title: reelTitle,
                text: 'Check out this Ayurvedic ritual on Vanriti!',
                url: targetLink
            }).catch(function () {});
        } else if (navigator.clipboard) {
            navigator.clipboard.writeText(targetLink).then(function () {
                showToast('Link copied to clipboard!');
            }).catch(function () {
                showToast('Link: ' + targetLink);
            });
        } else {
            showToast('Link: ' + targetLink);
        }
    }

    // Modal Action Buttons
    if (modalLikeBtn) {
        modalLikeBtn.addEventListener('click', function () {
            handleLike(cards[currentIndex]);
        });
    }

    if (modalShareBtn) {
        modalShareBtn.addEventListener('click', function () {
            handleShare(cards[currentIndex]);
        });
    }

    if (modalCartIconBtn && buyBtn) {
        modalCartIconBtn.addEventListener('click', function () {
            buyBtn.click();
        });
    }

    if (soundBtn) {
        soundBtn.addEventListener('click', function () {
            if (!video) return;
            video.muted = !video.muted;
            updateSoundIcon(video.muted);
        });
    }

    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', function () {
            var dialog = document.getElementById('vrModalDialog');
            if (!dialog) return;
            if (!document.fullscreenElement) {
                if (dialog.requestFullscreen) dialog.requestFullscreen();
                else if (dialog.webkitRequestFullscreen) dialog.webkitRequestFullscreen();
            } else {
                if (document.exitFullscreen) document.exitFullscreen();
            }
        });
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    if (video) {
        video.addEventListener('timeupdate', function () {
            if (video.duration) {
                var pct = (video.currentTime / video.duration) * 100;
                progressFill.style.width = pct + '%';
            }
        });

        video.addEventListener('click', function () {
            if (video.paused) {
                video.play();
                playOverlay.classList.add('d-none');
            } else {
                video.pause();
                playOverlay.classList.remove('d-none');
            }
        });
    }

    if (modalPrevBtn) {
        modalPrevBtn.addEventListener('click', function () {
            if (currentIndex > 0) {
                loadReel(currentIndex - 1);
            } else {
                loadReel(cards.length - 1);
            }
        });
    }

    if (modalNextBtn) {
        modalNextBtn.addEventListener('click', function () {
            if (currentIndex < cards.length - 1) {
                loadReel(currentIndex + 1);
            } else {
                loadReel(0);
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (!modal.classList.contains('active')) return;
        if (e.key === 'Escape') closeModal();
        if (e.key === 'ArrowRight' && modalNextBtn) modalNextBtn.click();
        if (e.key === 'ArrowLeft' && modalPrevBtn) modalPrevBtn.click();
    });
})();
</script>
@endpush

@endsection