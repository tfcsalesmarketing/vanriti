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
            <div class="d-flex align-items-end justify-content-between mb-4 vr-animate-in">
                <div>
                    <div class="vr-section-kicker vr-kicker-reels">
                        <i class="ri-instagram-line"></i> AS SEEN ON INSTAGRAM
                    </div>
                    <h2 class="vr-section-title mb-1">Watch &amp; Shop</h2>
                    <p class="vr-section-sub mb-0">Ayurvedic hair care routines, unboxings &amp; honest beauty rituals</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle d-none d-md-flex align-items-center justify-content-center vr-reels-arrow-btn" id="vrReelsPrevBtn" aria-label="Previous reels" style="width:38px;height:38px;">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle d-none d-md-flex align-items-center justify-content-center vr-reels-arrow-btn" id="vrReelsNextBtn" aria-label="Next reels" style="width:38px;height:38px;">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            {{-- Horizontal Reel Carousel --}}
            <div class="vr-reels-carousel" id="vrReelsCarousel">
                @foreach ($reels as $index => $reel)
                    @php
                        $taggedProduct = $reel->product;
                        $prodImg = $taggedProduct?->getPrimaryImage()?->image_path;
                    @endphp
                    <div class="vr-reel-card js-open-reel"
                         data-index="{{ $index }}"
                         data-title="{{ $reel->title ?: 'Vanriti Ayurvedic Ritual' }}"
                         data-caption="{{ $reel->caption }}"
                         data-video="{{ $reel->video_source }}"
                         data-embed="{{ $reel->embed_url }}"
                         data-instagram="{{ $reel->instagram_url }}"
                         data-poster="{{ $reel->poster }}"
                         @if ($taggedProduct)
                             data-has-product="true"
                             data-product-name="{{ $taggedProduct->name }}"
                             data-product-price="{{ format_price($taggedProduct->selling_price) }}"
                             @if ($taggedProduct->mrp > $taggedProduct->selling_price)
                                 data-product-mrp="{{ format_price($taggedProduct->mrp) }}"
                             @endif
                             data-product-url="{{ route('product.show', $taggedProduct) }}"
                             data-product-img="{{ $prodImg ? image_url($prodImg) : '' }}"
                             data-product-id="{{ $taggedProduct->id }}"
                             data-add-url="{{ route('cart.add', $taggedProduct) }}"
                         @else
                             data-has-product="false"
                         @endif
                    >
                        <div class="vr-reel-poster-wrap">
                            <img src="{{ $reel->poster }}" alt="{{ $reel->title }}" class="vr-reel-img" loading="lazy">
                            <div class="vr-reel-gradient-top">
                                <span class="vr-reel-ig-badge">
                                    <i class="ri-instagram-line"></i> {{ setting('instagram_handle', '@vanriti.in') }}
                                </span>
                                @if ($reel->views_count)
                                    <span class="vr-reel-views-chip">
                                        <i class="ri-eye-line"></i> {{ $reel->views_count }}
                                    </span>
                                @endif
                            </div>

                            <div class="vr-reel-play-icon">
                                <i class="ri-play-fill"></i>
                            </div>

                            <div class="vr-reel-gradient-bottom">
                                @if ($reel->title)
                                    <div class="vr-reel-title-text">{{ $reel->title }}</div>
                                @endif

                                @if ($taggedProduct)
                                    <div class="vr-reel-product-pill">
                                        @if ($prodImg)
                                            <img src="{{ image_url($prodImg) }}" alt="" class="vr-pill-thumb">
                                        @else
                                            <i class="ri-shopping-bag-3-line text-success"></i>
                                        @endif
                                        <div class="vr-pill-info">
                                            <span class="vr-pill-name">{{ Str::limit($taggedProduct->name, 26) }}</span>
                                            <span class="vr-pill-price">{{ format_price($taggedProduct->selling_price) }}</span>
                                        </div>
                                        <span class="vr-pill-cta">Shop</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Reel Fullscreen / Modal Viewer --}}
    <div class="vr-reel-modal" id="vrReelModal" tabindex="-1" aria-hidden="true">
        <div class="vr-reel-modal-backdrop" id="vrReelModalBackdrop"></div>
        <div class="vr-reel-modal-dialog">
            <button type="button" class="vr-reel-close-btn" id="vrReelCloseBtn" aria-label="Close modal">
                <i class="bi bi-x-lg"></i>
            </button>

            {{-- Nav Buttons --}}
            <button type="button" class="vr-reel-nav-btn prev" id="vrReelModalPrevBtn" aria-label="Previous reel">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button type="button" class="vr-reel-nav-btn next" id="vrReelModalNextBtn" aria-label="Next reel">
                <i class="bi bi-chevron-right"></i>
            </button>

            <div class="vr-reel-player-box">
                {{-- Video Container --}}
                <div class="vr-reel-media-wrap">
                    <video id="vrReelVideo" class="vr-reel-video-element" playsinline loop preload="metadata"></video>
                    <iframe id="vrReelIframe" class="vr-reel-iframe-element d-none" frameborder="0" allowfullscreen allowtransparency="true"></iframe>
                    <div class="vr-reel-play-overlay d-none" id="vrReelPlayOverlay">
                        <i class="ri-play-circle-fill"></i>
                    </div>

                    {{-- Player Top Controls --}}
                    <div class="vr-reel-player-top">
                        <div class="d-flex align-items-center gap-2">
                            <span class="vr-reel-brand-pill">
                                <i class="ri-instagram-line"></i> {{ setting('instagram_handle', '@vanriti.in') }}
                            </span>
                            <span class="vr-reel-title-badge" id="vrModalReelTitle"></span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="vr-reel-sound-btn" id="vrReelSoundBtn" title="Toggle sound">
                                <i class="bi bi-volume-mute-fill"></i>
                            </button>
                            <a href="#" target="_blank" class="vr-reel-ext-btn" id="vrReelInstaLink" title="View on Instagram">
                                <i class="bi bi-instagram"></i>
                            </a>
                        </div>
                    </div>

                    {{-- Video Progress Bar --}}
                    <div class="vr-reel-progress-bar">
                        <div class="vr-reel-progress-fill" id="vrReelProgressFill"></div>
                    </div>

                    {{-- Bottom Shoppable Product Drawer --}}
                    <div class="vr-reel-shoppable-drawer d-none" id="vrModalProductDrawer">
                        <div class="d-flex align-items-center gap-2.5">
                            <img src="" alt="" class="vr-drawer-thumb" id="vrModalProductImg">
                            <div class="flex-grow-1 min-w-0">
                                <div class="vr-drawer-name" id="vrModalProductName"></div>
                                <div class="d-flex align-items-center gap-2 mt-0.5">
                                    <span class="vr-drawer-price" id="vrModalProductPrice"></span>
                                    <span class="vr-drawer-mrp text-muted text-decoration-line-through small" id="vrModalProductMrp"></span>
                                </div>
                            </div>
                            <form method="POST" action="" id="vrModalCartForm" class="m-0">
                                @csrf
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn btn-vr btn-sm vr-drawer-buy-btn text-nowrap" id="vrModalBuyBtn">
                                    <i class="bi bi-bag-plus me-1"></i> Add to Cart
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
    var prevBtn = document.getElementById('vrReelsPrevBtn');
    var nextBtn = document.getElementById('vrReelsNextBtn');
    var modal = document.getElementById('vrReelModal');
    var backdrop = document.getElementById('vrReelModalBackdrop');
    var closeBtn = document.getElementById('vrReelCloseBtn');
    var video = document.getElementById('vrReelVideo');
    var iframe = document.getElementById('vrReelIframe');
    var playOverlay = document.getElementById('vrReelPlayOverlay');
    var soundBtn = document.getElementById('vrReelSoundBtn');
    var modalTitle = document.getElementById('vrModalReelTitle');
    var instaLink = document.getElementById('vrReelInstaLink');
    var progressFill = document.getElementById('vrReelProgressFill');
    var modalPrevBtn = document.getElementById('vrReelModalPrevBtn');
    var modalNextBtn = document.getElementById('vrReelModalNextBtn');

    // Drawer elements
    var drawer = document.getElementById('vrModalProductDrawer');
    var drawerImg = document.getElementById('vrModalProductImg');
    var drawerName = document.getElementById('vrModalProductName');
    var drawerPrice = document.getElementById('vrModalProductPrice');
    var drawerMrp = document.getElementById('vrModalProductMrp');
    var cartForm = document.getElementById('vrModalCartForm');

    if (!carousel) return;

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            carousel.scrollBy({ left: -300, behavior: 'smooth' });
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            carousel.scrollBy({ left: 300, behavior: 'smooth' });
        });
    }

    var cards = Array.from(document.querySelectorAll('.js-open-reel'));
    var currentIndex = 0;

    function loadReel(index) {
        if (index < 0 || index >= cards.length) return;
        currentIndex = index;
        var card = cards[index];

        var videoSrc = card.getAttribute('data-video');
        var embedSrc = card.getAttribute('data-embed');
        var instaUrl = card.getAttribute('data-instagram') || '#';
        var title = card.getAttribute('data-title') || '';
        var poster = card.getAttribute('data-poster') || '';
        var hasProduct = card.getAttribute('data-has-product') === 'true';

        modalTitle.textContent = title;
        instaLink.href = instaUrl;
        progressFill.style.width = '0%';

        if (videoSrc) {
            iframe.classList.add('d-none');
            iframe.src = '';
            video.classList.remove('d-none');
            video.poster = poster;
            video.src = videoSrc;
            video.muted = true;
            updateSoundIcon(true);
            video.play().catch(function () {});
            playOverlay.classList.add('d-none');
        } else if (embedSrc) {
            video.classList.add('d-none');
            video.pause();
            video.src = '';
            iframe.classList.remove('d-none');
            iframe.src = embedSrc;
            playOverlay.classList.add('d-none');
        } else if (instaUrl && instaUrl !== '#') {
            window.open(instaUrl, '_blank');
            return;
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
        if (iframe) {
            iframe.src = '';
        }
        progressFill.style.width = '0%';
    }

    function updateSoundIcon(isMuted) {
        if (!soundBtn) return;
        soundBtn.innerHTML = isMuted ? '<i class="bi bi-volume-mute-fill"></i>' : '<i class="bi bi-volume-up-fill"></i>';
    }

    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            var idx = parseInt(this.getAttribute('data-index'), 10) || 0;
            loadReel(idx);
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    if (soundBtn) {
        soundBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (!video) return;
            video.muted = !video.muted;
            updateSoundIcon(video.muted);
        });
    }

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
        modalPrevBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (currentIndex > 0) {
                loadReel(currentIndex - 1);
            } else {
                loadReel(cards.length - 1);
            }
        });
    }

    if (modalNextBtn) {
        modalNextBtn.addEventListener('click', function (e) {
            e.stopPropagation();
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