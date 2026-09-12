@php
    use App\Services\CartService;
    use App\Services\WishlistService;
    $cartService = app(CartService::class);
    $wishlistService = app(WishlistService::class);
    $cartCount = $cartService->count();
    $wishCount = $wishlistService->count();
    $navCategories = App\Models\Category::with(['children'])->whereNull('parent_id')->where('status', 'active')->orderBy('sort_order')->get();

    $logo = setting('store_logo');
    if (! $logo) {
        foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
            if (file_exists(public_path('images/logo.' . $ext))) {
                $logo = 'images/logo.' . $ext;
                break;
            }
        }
    }
@endphp

<div class="vr-announce">
    Free shipping above {{ setting('free_shipping_threshold', 499) }} &middot; <strong>WELCOME10</strong> for 10% off your first order
</div>

<nav class="vr-navbar" id="vrNavbar">
    <div class="container">
        <div class="row align-items-center py-3 g-0">
            {{-- Mobile Header: Logo centered, Left Category Menu Icon, Right User Icon --}}
            <div class="col-12 d-flex d-lg-none align-items-center justify-content-between px-2">
                <button type="button" class="vr-mobile-header-btn" data-bs-toggle="offcanvas" data-bs-target="#vrCategoryPanel" aria-controls="vrCategoryPanel" aria-label="Open Categories">
                    <i class="ri-menu-line"></i>
                </button>

                <a class="vr-brand mx-auto text-center" href="{{ route('home') }}">
                    @if ($logo)
                        <img src="{{ image_url($logo, 'favicon.ico') }}" alt="{{ store_name() }}" height="50" class="vr-logo">
                    @else
                        {{ strtoupper(store_name()) }}<span class="dot">.</span>
                    @endif
                </a>

                @auth('web')
                    <button type="button" class="vr-mobile-header-btn {{ request()->routeIs('account.*') ? 'active' : '' }}" data-bs-toggle="offcanvas" data-bs-target="#vrUserPanel" aria-controls="vrUserPanel" aria-label="Account">
                        <i class="ri-user-3-line"></i>
                    </button>
                @else
                    <button type="button" class="vr-mobile-header-btn {{ request()->routeIs('login') ? 'active' : '' }}" data-bs-toggle="offcanvas" data-bs-target="#vrUserPanel" aria-controls="vrUserPanel" aria-label="Sign In">
                        <i class="ri-user-3-line"></i>
                    </button>
                @endauth
            </div>

            {{-- Desktop: logo + category nav --}}
            <div class="col-lg-3 d-none d-lg-flex align-items-center">
                <a class="vr-brand me-lg-0" href="{{ route('home') }}">
                    @if ($logo)
                        <img src="{{ image_url($logo, 'favicon.ico') }}" alt="{{ store_name() }}" height="55" class="vr-logo">
                    @else
                        {{ strtoupper(store_name()) }}<span class="dot">.</span>
                    @endif
                </a>
            </div>

            <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center gap-3">
                <ul class="nav align-items-center mb-0">
                    <li class="nav-item">
                        <a class="vr-nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a>
                    </li>
                    @foreach ($navCategories as $cat)
                        <li class="nav-item dropdown">
                            <a class="vr-nav-link dropdown-toggle {{ request('category', '') == $cat->slug ? 'active' : '' }}"
                               href="{{ route('shop.category', $cat->slug) }}"
                               @if ($cat->children->count()) data-bs-toggle="dropdown" @endif>
                                {{ $cat->name }}
                            </a>
                            @if ($cat->children->count())
                                <ul class="dropdown-menu shadow">
                                    @foreach ($cat->children as $child)
                                        <li><a class="dropdown-item small" href="{{ route('shop.category', $child->slug) }}">{{ $child->name }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                    {{-- DADI AI paused: nav entry hidden (route still live at /dadi). --}}
                    {{-- <li class="nav-item">
                        <a class="vr-nav-link {{ request()->routeIs('dadi.*') ? 'active' : '' }}" href="{{ route('dadi.index') }}">Dadi</a>
                    </li> --}}
                    <li class="nav-item">
                        <a class="vr-nav-link" href="{{ route('contact.index') }}">Contact</a>
                    </li>
                </ul>
            </div>

            <div class="col-lg-3 d-none d-lg-flex align-items-center justify-content-end gap-3">
                @auth('web')
                    <a href="{{ route('account.dashboard') }}" class="vr-icon-link" title="Account" aria-label="Account">
                        <i class="bi bi-person"></i>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="vr-icon-link" title="Sign in" aria-label="Sign in">
                        <i class="bi bi-person"></i>
                    </a>
                @endauth
                <a href="{{ route('wishlist.index') }}" class="vr-icon-link" title="Wishlist" aria-label="Wishlist">
                    <i class="bi bi-heart"></i>
                    @if ($wishCount > 0)
                        <span class="vr-badge">{{ $wishCount }}</span>
                    @endif
                </a>
                <a href="{{ route('cart.index') }}" class="vr-icon-link position-relative" title="Cart" aria-label="Cart">
                    <i class="bi bi-bag"></i>
                    @if ($cartCount > 0)
                        <span class="vr-badge js-cart-count">{{ $cartCount }}</span>
                    @else
                        <span class="vr-badge js-cart-count d-none">0</span>
                    @endif
                </a>
            </div>
        </div>
    </div>
</nav>

@push('styles')
<style>
.vr-navbar{transition:box-shadow .3s ease,background .3s ease,backdrop-filter .3s ease}
</style>
@endpush
