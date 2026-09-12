@php
    use App\Services\CartService;
    $cartService = app(CartService::class);
    $cartCount = $cartService->count();

    $barCategories = App\Models\Category::with(['children'])
        ->whereNull('parent_id')
        ->where('status', 'active')
        ->orderBy('sort_order')
        ->get();

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

{{-- ========== APP DRAWER #1: MENU PANEL ========== --}}
<div class="offcanvas offcanvas-start vr-app-drawer" tabindex="-1" id="vrMenuPanel" aria-labelledby="vrMenuPanelLabel">
    <div class="offcanvas-header vr-drawer-header">
        <a class="vr-drawer-brand" href="{{ route('home') }}">
            @if ($logo)
                <img src="{{ image_url($logo, 'favicon.ico') }}" alt="{{ store_name() }}" height="36" class="vr-logo">
            @else
                <span class="vr-brand-name">{{ store_name() }}</span>
            @endif
            <span class="vr-drawer-badge">Official Store</span>
        </a>
        <button type="button" class="vr-drawer-close" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="ri-close-line"></i>
        </button>
    </div>

    <div class="offcanvas-body vr-drawer-body">
        {{-- Section 1: Main App Navigation --}}
        <div class="vr-drawer-section">
            <div class="vr-drawer-section-title">Discover</div>
            <ul class="list-unstyled vr-drawer-nav">
                <li class="vr-drawer-item">
                    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
                        <div class="vr-nav-icon"><i class="ri-home-5-line"></i></div>
                        <span>Home</span>
                        <i class="ri-arrow-right-s-line vr-nav-arrow"></i>
                    </a>
                </li>
                <li class="vr-drawer-item">
                    <a href="{{ route('shop.index') }}" class="{{ request()->routeIs('shop.index') ? 'active' : '' }}">
                        <div class="vr-nav-icon"><i class="ri-shopping-bag-3-line"></i></div>
                        <span>Shop All Products</span>
                        <i class="ri-arrow-right-s-line vr-nav-arrow"></i>
                    </a>
                </li>
                <li class="vr-drawer-item">
                    <a href="{{ route('blog.index') }}" class="{{ request()->routeIs('blog.*') ? 'active' : '' }}">
                        <div class="vr-nav-icon"><i class="ri-book-read-line"></i></div>
                        <span>Journal & Stories</span>
                        <i class="ri-arrow-right-s-line vr-nav-arrow"></i>
                    </a>
                </li>
                <li class="vr-drawer-item">
                    <a href="{{ route('track') }}" class="{{ request()->routeIs('track') ? 'active' : '' }}">
                        <div class="vr-nav-icon"><i class="ri-truck-line"></i></div>
                        <span>Track Order</span>
                        <span class="vr-badge-pill ms-auto me-2">Live</span>
                        <i class="ri-arrow-right-s-line vr-nav-arrow"></i>
                    </a>
                </li>
                {{-- DADI AI paused: menu entry hidden (route still live at /dadi). --}}
                {{-- <li class="vr-drawer-item">
                    <a href="{{ route('dadi.index') }}" class="{{ request()->routeIs('dadi.*') ? 'active' : '' }}">
                        <div class="vr-nav-icon"><i class="ri-chat-smile-3-line"></i></div>
                        <span>Dadi — Baat Karein</span>
                        <i class="ri-arrow-right-s-line vr-nav-arrow"></i>
                    </a>
                </li> --}}
            </ul>
        </div>

        {{-- Section 2: Help & Support --}}
        <div class="vr-drawer-section">
            <div class="vr-drawer-section-title">Customer Care</div>
            <ul class="list-unstyled vr-drawer-nav">
                <li class="vr-drawer-item">
                    <a href="{{ route('contact.index') }}" class="{{ request()->routeIs('contact.*') ? 'active' : '' }}">
                        <div class="vr-nav-icon"><i class="ri-customer-service-2-line"></i></div>
                        <span>Contact Support</span>
                        <i class="ri-arrow-right-s-line vr-nav-arrow"></i>
                    </a>
                </li>
                <li class="vr-drawer-item">
                    <a href="{{ route('info.about') }}" class="{{ request()->routeIs('info.about') ? 'active' : '' }}">
                        <div class="vr-nav-icon"><i class="ri-information-line"></i></div>
                        <span>About Our Brand</span>
                        <i class="ri-arrow-right-s-line vr-nav-arrow"></i>
                    </a>
                </li>
                @if (App\Models\Faq::where('status', 'active')->exists())
                    <li class="vr-drawer-item">
                        <a href="{{ route('faq.index') }}" class="{{ request()->routeIs('faq.*') ? 'active' : '' }}">
                            <div class="vr-nav-icon"><i class="ri-questionnaire-line"></i></div>
                            <span>FAQs & Help</span>
                            <i class="ri-arrow-right-s-line vr-nav-arrow"></i>
                        </a>
                    </li>
                @endif
            </ul>
        </div>

        {{-- Section 3: Legal & Information --}}
        <div class="vr-drawer-section">
            <div class="vr-drawer-section-title">Store Policies</div>
            <ul class="list-unstyled vr-drawer-nav">
                <li class="vr-drawer-item">
                    <a href="{{ route('info.shipping') }}" class="{{ request()->routeIs('info.shipping') ? 'active' : '' }}">
                        <div class="vr-nav-icon"><i class="ri-box-3-line"></i></div>
                        <span>Shipping & Delivery</span>
                        <i class="ri-arrow-right-s-line vr-nav-arrow"></i>
                    </a>
                </li>
                <li class="vr-drawer-item">
                    <a href="{{ route('info.privacy') }}" class="{{ request()->routeIs('info.privacy') ? 'active' : '' }}">
                        <div class="vr-nav-icon"><i class="ri-shield-user-line"></i></div>
                        <span>Privacy Policy</span>
                        <i class="ri-arrow-right-s-line vr-nav-arrow"></i>
                    </a>
                </li>
                <li class="vr-drawer-item">
                    <a href="{{ route('info.terms') }}" class="{{ request()->routeIs('info.terms') ? 'active' : '' }}">
                        <div class="vr-nav-icon"><i class="ri-file-list-3-line"></i></div>
                        <span>Terms & Conditions</span>
                        <i class="ri-arrow-right-s-line vr-nav-arrow"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="vr-drawer-footer">
        @auth('web')
            <a href="{{ route('account.dashboard') }}" class="btn vr-app-btn w-100">
                <i class="ri-user-3-fill me-2"></i> My Account Dashboard
            </a>
        @else
            <a href="{{ route('login') }}" class="btn vr-app-btn w-100">
                <i class="ri-login-box-line me-2"></i> Sign In to Account
            </a>
        @endauth
    </div>
</div>

{{-- ========== APP DRAWER #2: CATEGORY PANEL ========== --}}
<div class="offcanvas offcanvas-start vr-app-drawer" tabindex="-1" id="vrCategoryPanel" aria-labelledby="vrCategoryPanelLabel">
    <div class="offcanvas-header vr-drawer-header">
        <div class="d-flex align-items-center gap-2">
            <div class="vr-header-icon-box"><i class="ri-leaf-fill"></i></div>
            <div>
                <h6 class="offcanvas-title fw-bold mb-0" id="vrCategoryPanelLabel">Explore Categories</h6>
                <span class="small text-muted">Browse items by category</span>
            </div>
        </div>
        <button type="button" class="vr-drawer-close" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="ri-close-line"></i>
        </button>
    </div>

    <div class="offcanvas-body vr-drawer-body">
        {{-- Featured Category Chips --}}
        <div class="vr-category-chips mb-3">
            <a href="{{ route('shop.index') }}" class="vr-chip active">All Products</a>
            @foreach ($barCategories->take(4) as $topCat)
                <a href="{{ route('shop.category', $topCat->slug) }}" class="vr-chip">{{ $topCat->name }}</a>
            @endforeach
        </div>

        {{-- Accordion Categories List --}}
        <div class="accordion vr-cat-accordion" id="vrCatPanelAcc">
            @foreach ($barCategories as $cat)
                <div class="vr-cat-card mb-2">
                    @if ($cat->children->count())
                        <div class="vr-cat-header" id="headingCat{{ $cat->id }}">
                            <button class="vr-cat-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#vrCatPanel{{ $cat->id }}" aria-expanded="false" aria-controls="vrCatPanel{{ $cat->id }}">
                                <div class="vr-cat-icon"><i class="ri-price-tag-3-fill"></i></div>
                                <span class="vr-cat-title">{{ $cat->name }}</span>
                                <span class="vr-cat-badge">{{ $cat->children->count() }} subcategories</span>
                                <i class="ri-arrow-down-s-line vr-cat-chevron ms-2"></i>
                            </button>
                        </div>
                        <div id="vrCatPanel{{ $cat->id }}" class="collapse" aria-labelledby="headingCat{{ $cat->id }}" data-bs-parent="#vrCatPanelAcc">
                            <div class="vr-cat-body">
                                <a class="vr-subcat-link vr-subcat-all" href="{{ route('shop.category', $cat->slug) }}">
                                    <span>All {{ $cat->name }}</span>
                                    <i class="ri-arrow-right-line"></i>
                                </a>
                                @foreach ($cat->children as $child)
                                    <a class="vr-subcat-link" href="{{ route('shop.category', $child->slug) }}">
                                        <span>{{ $child->name }}</span>
                                        <i class="ri-arrow-right-s-line"></i>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a class="vr-cat-direct" href="{{ route('shop.category', $cat->slug) }}">
                            <div class="vr-cat-icon"><i class="ri-price-tag-3-line"></i></div>
                            <span class="vr-cat-title">{{ $cat->name }}</span>
                            <i class="ri-arrow-right-s-line vr-nav-arrow"></i>
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ========== APP DRAWER #3: USER ACCOUNT PANEL ========== --}}
<div class="offcanvas offcanvas-start vr-app-drawer" tabindex="-1" id="vrUserPanel" aria-labelledby="vrUserPanelLabel">
    <div class="offcanvas-header vr-drawer-header">
        <div class="d-flex align-items-center gap-2">
            <div class="vr-header-icon-box"><i class="ri-user-3-fill"></i></div>
            <div>
                <h6 class="offcanvas-title fw-bold mb-0" id="vrUserPanelLabel">My Account</h6>
                <span class="small text-muted">Personal hub</span>
            </div>
        </div>
        <button type="button" class="vr-drawer-close" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="ri-close-line"></i>
        </button>
    </div>

    <div class="offcanvas-body vr-drawer-body d-flex flex-column">
        @auth('web')
            {{-- Auth User Profile Hero Card --}}
            <div class="vr-profile-hero mb-3">
                <div class="vr-hero-avatar-wrap">
                    <div class="vr-hero-avatar">{{ strtoupper(substr(auth('web')->user()->name, 0, 1)) }}</div>
                    <span class="vr-avatar-status"></span>
                </div>
                <div class="vr-hero-info">
                    <div class="vr-hero-name">{{ auth('web')->user()->name }}</div>
                    <div class="vr-hero-email">{{ auth('web')->user()->email }}</div>
                    <div class="vr-hero-tag"><i class="ri-checkbox-circle-fill me-1"></i> Verified Member</div>
                </div>
            </div>

            {{-- 2x2 Quick Action Tiles --}}
            <div class="vr-user-grid mb-3">
                <a href="{{ route('account.dashboard') }}" class="vr-grid-tile">
                    <div class="vr-tile-icon"><i class="ri-dashboard-3-line"></i></div>
                    <div class="vr-tile-label">Dashboard</div>
                </a>
                <a href="{{ route('account.orders') }}" class="vr-grid-tile">
                    <div class="vr-tile-icon"><i class="ri-shopping-basket-2-line"></i></div>
                    <div class="vr-tile-label">My Orders</div>
                </a>
                <a href="{{ route('account.addresses') }}" class="vr-grid-tile">
                    <div class="vr-tile-icon"><i class="ri-map-pin-2-line"></i></div>
                    <div class="vr-tile-label">Addresses</div>
                </a>
                <a href="{{ route('wishlist.index') }}" class="vr-grid-tile">
                    <div class="vr-tile-icon"><i class="ri-heart-3-line"></i></div>
                    <div class="vr-tile-label">Wishlist</div>
                </a>
            </div>

            <div class="mt-auto pt-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn vr-app-outline-btn w-100">
                        <i class="ri-logout-box-r-line me-2"></i> Log Out
                    </button>
                </form>
            </div>
        @else
            {{-- Guest Welcome Card --}}
            <div class="vr-guest-card text-center my-auto">
                <div class="vr-guest-icon-box mb-3">
                    <i class="ri-user-smile-line"></i>
                </div>
                <h5 class="fw-bold mb-1">Welcome to Store</h5>
                <p class="text-muted small mb-4">Sign in to track orders, manage addresses, and save items to your wishlist.</p>
                <a href="{{ route('login') }}" class="btn vr-app-btn w-100 mb-2">
                    <i class="ri-login-box-line me-2"></i> Sign In
                </a>
                <a href="{{ route('register') }}" class="btn vr-app-outline-btn w-100">
                    <i class="ri-user-add-line me-2"></i> Create New Account
                </a>
            </div>
        @endauth
    </div>
</div>

{{-- ========== MODERN MOBILE DOCK FOOTER (REMIX ICONS) ========== --}}
<div class="vr-mobile-dock d-lg-none" id="vrBottomBar">
    <div class="vr-dock-container">
        {{-- Item 1: Menu (Hamburger Icon) --}}
        <button type="button" class="vr-dock-item" data-bs-toggle="offcanvas" data-bs-target="#vrMenuPanel" aria-controls="vrMenuPanel" aria-label="Menu">
            <div class="vr-dock-icon">
                <i class="ri-menu-line"></i>
            </div>
            <span class="vr-dock-label">Menu</span>
        </button>

        {{-- Item 2: Category (Leaf Icon) --}}
        <button type="button" class="vr-dock-item" data-bs-toggle="offcanvas" data-bs-target="#vrCategoryPanel" aria-controls="vrCategoryPanel" aria-label="Category">
            <div class="vr-dock-icon">
                <i class="ri-leaf-line"></i>
            </div>
            <span class="vr-dock-label">Category</span>
        </button>

        {{-- Item 3: Home (Home Icon - Clean Uniform Styling) --}}
        <a href="{{ route('home') }}" class="vr-dock-item {{ request()->routeIs('home') ? 'active' : '' }}" aria-label="Home">
            <div class="vr-dock-icon">
                <i class="{{ request()->routeIs('home') ? 'ri-home-5-fill' : 'ri-home-5-line' }}"></i>
            </div>
            <span class="vr-dock-label">Home</span>
        </a>

        {{-- Item 4: Account (User Icon) --}}
        @auth('web')
            <button type="button" class="vr-dock-item {{ request()->routeIs('account.*') ? 'active' : '' }}" data-bs-toggle="offcanvas" data-bs-target="#vrUserPanel" aria-controls="vrUserPanel" aria-label="Account">
                <div class="vr-dock-icon">
                    <i class="{{ request()->routeIs('account.*') ? 'ri-user-3-fill' : 'ri-user-3-line' }}"></i>
                </div>
                <span class="vr-dock-label">Account</span>
            </button>
        @else
            <a href="{{ route('login') }}" class="vr-dock-item {{ request()->routeIs('login') ? 'active' : '' }}" aria-label="Sign in">
                <div class="vr-dock-icon">
                    <i class="{{ request()->routeIs('login') ? 'ri-user-3-fill' : 'ri-user-3-line' }}"></i>
                </div>
                <span class="vr-dock-label">Sign In</span>
            </a>
        @endauth

        {{-- Item 5: Cart (Bag Icon) --}}
        @auth('web')
            <a href="{{ route('cart.index') }}" class="vr-dock-item {{ request()->routeIs('cart.*') ? 'active' : '' }}" aria-label="Cart">
                <div class="vr-dock-icon position-relative">
                    <i class="{{ request()->routeIs('cart.*') ? 'ri-shopping-bag-3-fill' : 'ri-shopping-bag-3-line' }}"></i>
                    @if ($cartCount > 0)
                        <span class="vr-dock-badge js-cart-count">{{ $cartCount }}</span>
                    @else
                        <span class="vr-dock-badge js-cart-count d-none">0</span>
                    @endif
                </div>
                <span class="vr-dock-label">Cart</span>
            </a>
        @else
            <a href="{{ route('login') }}" class="vr-dock-item" aria-label="Cart">
                <div class="vr-dock-icon position-relative">
                    <i class="ri-shopping-bag-3-line"></i>
                </div>
                <span class="vr-dock-label">Cart</span>
            </a>
        @endauth
    </div>
</div>
