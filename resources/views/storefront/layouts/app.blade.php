<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="facebook-domain-verification" content="uiy2uo8dqx16mp5h2f784kpbs4ry53" />
    @php
        $_seoTitle = trim((string) $__env->yieldContent('title'));
        $_brandName = store_name();
        $_escapedBrand = e($_brandName);
        if ($_seoTitle === '') {
            $_seoTitle = setting('meta_title') ? e(setting('meta_title')) : $_escapedBrand;
        }
        if ($_seoTitle !== '' && $_seoTitle !== $_escapedBrand && mb_strpos($_seoTitle, $_escapedBrand) === false) {
            $_seoTitle .= ' - ' . $_escapedBrand;
        }
        $_ogTagline = (string) setting('store_tagline', 'PURE BY NATURE');
        $_ogDefault = $_brandName . ($_ogTagline !== '' ? ' - ' . $_ogTagline : '');
        if (setting('meta_title')) {
            $_ogDefault = (string) setting('meta_title');
        }
    @endphp
    <title>{{ $_seoTitle }}</title>
    <meta name="description" content="@yield('meta_description', setting('meta_description'))">
    <meta name="keywords" content="@yield('meta_keywords', setting('meta_keywords'))">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="canonical" href="{{ request()->url() }}">
    @php
        $_brandLogo = setting('store_logo');
        if (! $_brandLogo) {
            foreach (['svg', 'png', 'webp', 'jpg', 'ico'] as $_ext) {
                if (file_exists(public_path('images/logo.' . $_ext))) {
                    $_brandLogo = 'images/logo.' . $_ext;
                    break;
                }
            }
        }
    @endphp
    <meta property="og:title" content="@yield('og_title', $_ogDefault)">
    <meta property="og:description" content="@yield('og_description', setting('meta_description'))">
    <meta property="og:url" content="{{ request()->url() }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ store_name() }}">
    <meta property="og:locale" content="en_IN">
    <meta property="og:image" content="@yield('og_image', image_url($_brandLogo, 'favicon.ico'))">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', $_ogDefault)">
    <meta name="twitter:description" content="@yield('og_description', setting('meta_description'))">
    <meta name="twitter:image" content="@yield('og_image', image_url($_brandLogo, 'favicon.ico'))">
    <meta name="theme-color" content="#263D25">
    <link rel="icon" href="{{ image_url($_brandLogo, 'favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ image_url($_brandLogo, 'favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Plus+Jakarta+Sans:ital,wght@0,400..800;1,400..800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}?v={{ time() }}">
    @stack('styles')
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebSite",
        "name": {!! json_encode(store_name(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
        "url": {!! json_encode(route('home'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
        "potentialAction": {
            "@type": "SearchAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": {!! json_encode(route('shop.index') . '?q={search_term_string}', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
            },
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    <script>
    window.dataLayer = window.dataLayer || [];
    </script>
    {{-- Password reset and forgot-password URLs carry a single-use reset token
         (and the phone flow's OTP confirmation happen inside the query string).
         Analytics containers must never run on those pages, so a token can't
         leak to GTM / Meta as part of the page URL. --}}
    @unless (request()->routeIs('password.request', 'password.reset'))
    @if (config('analytics.gtm_container_id'))
        <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;
        j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
        f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ config('analytics.gtm_container_id') }}');
        </script>
    @endif
    @if (setting('meta_pixel_id'))
        <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
        document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ setting('meta_pixel_id') }}');
        fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id={{ setting('meta_pixel_id') }}&ev=PageView&noscript=1"
        /></noscript>
        <script>
        // Centralised Meta browser event helper (M3.2). Observational only: it
        // verifies fbq exists, fails silently, and never alters commerce flow.
        window.vrMeta = (function () {
            function fire(event, payload, opts) {
                try {
                    if (typeof window.fbq !== 'function') {
                        return false;
                    }
                    window.fbq('track', event, payload || {}, opts || {});
                    return true;
                } catch (e) {
                    return false;
                }
            }

            function ecommerceOf(analytics) {
                if (!analytics) {
                    return null;
                }
                return analytics.ecommerce ? analytics.ecommerce : analytics;
            }

            function round2(value) {
                var n = Number(value);
                return isNaN(n) ? 0 : Math.round((n + Number.EPSILON) * 100) / 100;
            }

            // Builds the Meta AddToCart payload from the authoritative server
            // GA4 add_to_cart analytics payload (SKU/quantity/price/value are
            // all resolved server-side). Returns null when no valid SKU exists.
            function fromGa4AddToCart(analytics) {
                var ecommerce = ecommerceOf(analytics);
                if (!ecommerce || !Array.isArray(ecommerce.items) || ecommerce.items.length === 0) {
                    return null;
                }
                var item = ecommerce.items[0];
                if (!item || item.item_id === undefined || item.item_id === null || item.item_id === '') {
                    return null;
                }
                var quantity = Math.max(1, parseInt(item.quantity, 10) || 1);
                var price = parseFloat(item.price) || 0;

                return {
                    content_ids: [String(item.item_id)],
                    content_type: 'product',
                    content_name: item.item_name || '',
                    value: round2(ecommerce.value !== undefined ? ecommerce.value : price * quantity),
                    currency: ecommerce.currency || 'INR',
                    contents: [
                        { id: String(item.item_id), quantity: quantity, item_price: price }
                    ]
                };
            }

            function track(event, payload, opts) {
                return fire(event, payload, opts);
            }

            function trackAddToCartFromGa4(analytics) {
                var payload = fromGa4AddToCart(analytics);
                if (!payload) {
                    return false;
                }
                return fire('AddToCart', payload, {});
            }

            return {
                track: track,
                trackAddToCartFromGa4: trackAddToCartFromGa4
            };
        })();
        </script>
    @endif
    @endunless
</head>
<body class="has-mobile-bar">
    @if (config('analytics.gtm_container_id'))
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ config('analytics.gtm_container_id') }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif
    @include('storefront.partials.header')

    <main>
        @if (session('success'))
            <div class="container mt-3">
                <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="container mt-3">
                <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        @endif
        @if (! empty($errors) && $errors->any())
            <script type="application/json" id="vrValidationErrors">{!! json_encode($errors->getMessages(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
        @endif

        @yield('content')
    </main>
    @include('storefront.partials.cart-drawer')
    @include('storefront.partials.login-modal')
    @include('storefront.partials.footer')

    {{-- Guest flag for the storefront JS: lets the add-to-cart / wishlist
         handlers + login modal know whether a replayed action must first pass
         through login. Server-side gates in CartController/WishlistController
         are authoritative regardless. --}}
    <meta name="vr-is-guest" content="{{ auth('web')->guest() ? '1' : '0' }}">

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/storefront.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/vr-login-modal.js') }}?v={{ time() }}"></script>
    <script>
    (function () {
        var nav = document.getElementById('vrNavbar');
        if (nav) {
            window.addEventListener('scroll', function () {
                nav.classList.toggle('scrolled', window.scrollY > 20);
            }, { passive: true });
        }

        if ('IntersectionObserver' in window) {
            var items = document.querySelectorAll('.vr-animate-in, .vr-stagger');
            if (items.length) {
                var obs = new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (e.isIntersecting) {
                            e.target.classList.add('vr-visible');
                            obs.unobserve(e.target);
                        }
                    });
                }, { threshold: 0.12 });
                items.forEach(function (el) { obs.observe(el); });
            }
        }
    })();
    </script>
    <script>
    (function () {
        var el = document.getElementById('vrValidationErrors');
        if (!el) return;
        try {
            var errors = JSON.parse(el.textContent);
        } catch (e) { return; }
        if (typeof window.vrInlineErrors === 'function') {
            window.vrInlineErrors(errors);
        }
    })();
    </script>
    @php $pendingAddToCart = session()->pull('pending_add_to_cart'); @endphp
    @if ($pendingAddToCart)
        <script>
        window.dataLayer.push({!! json_encode($pendingAddToCart, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!});
        @if (setting('meta_pixel_id'))
        window.vrMeta.trackAddToCartFromGa4({!! json_encode($pendingAddToCart['ecommerce'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!});
        @endif
        </script>
    @endif
    @php
        $pendingRefundUserId = auth('web')->id();
        $pendingRefundEvents = $pendingRefundUserId
            ? cache()->pull(\App\Services\RefundService::PENDING_REFUND_CACHE_KEY.$pendingRefundUserId)
            : null;
    @endphp
    @if (! empty($pendingRefundEvents) && is_array($pendingRefundEvents))
        @foreach ($pendingRefundEvents as $pendingRefundEvent)
            <script>
            window.dataLayer.push({!! json_encode($pendingRefundEvent, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!});
            </script>
        @endforeach
    @endif
    @stack('scripts')
</body>
</html>