<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ store_name() }} Admin</title>
    <meta name="theme-color" content="#263D25">
    <link rel="icon" href="{{ brand_favicon_url() }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ brand_favicon_url() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=1.7">
    <script nonce="{{ $cspNonce }}">
        try {
            if (localStorage.getItem('admin_sidebar_collapsed') === 'true') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    </script>
    @stack('styles')
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar" id="adminSidebar">
            @php
                $_adminLogo = brand_logo_path();
            @endphp
            <div class="sidebar-brand d-flex align-items-center justify-content-between">
                @if (request()->routeIs('admin.tfc.*'))
                    <a href="{{ route('admin.tfc.dashboard') }}" class="d-inline-flex align-items-center text-decoration-none">
                        <img src="{{ tfc_logo_url() }}" alt="TFC Sales & Marketing" class="brand-logo" style="max-height: 38px; object-fit: contain;">
                    </a>
                @else
                    <a href="{{ route('admin.dashboard') }}" class="d-inline-flex align-items-center text-decoration-none">
                        @if ($_adminLogo)
                            <img src="{{ image_url($_adminLogo) }}" alt="{{ store_name() }}" class="brand-logo">
                        @else
                            <span class="brand-text">{{ strtoupper(store_name()) }}</span>
                        @endif
                    </a>
                @endif
                <button class="btn btn-sm btn-link text-muted p-1 d-none d-lg-inline-flex align-items-center justify-content-center sidebar-close-btn" id="sidebarDesktopClose" type="button" title="Collapse Sidebar" style="border-radius: 8px; width: 32px; height: 32px; text-decoration: none;">
                    <i class="bi bi-layout-sidebar-inset" style="font-size: 1.15rem;"></i>
                </button>
            </div>
            <nav class="sidebar-nav">
                @if (request()->routeIs('admin.tfc.*'))
                    @include('admin.tfc.partials.sidebar-nav')
                @else
                    @include('admin.partials.sidebar-nav')
                @endif
            </nav>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <button class="btn btn-link text-dark p-1 me-2 admin-sidebar-toggle-btn d-inline-flex align-items-center justify-content-center" id="sidebarToggle" type="button" aria-label="Toggle Navigation" title="Toggle Sidebar">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div class="topbar-title me-auto">
                    <h5>@yield('title', 'Dashboard')</h5>
                </div>

                {{-- Universal Quick Jump / Search Trigger --}}
                <div class="topbar-search-trigger d-none d-md-flex" data-bs-toggle="modal" data-bs-target="#adminCommandPaletteModal" title="Quick Navigation (Ctrl+K)">
                    <i class="bi bi-search"></i>
                    <span>Quick jump to...</span>
                    <kbd class="ms-auto">Ctrl K</kbd>
                </div>

                <div class="d-flex align-items-center gap-2">
                    {{-- Dedicated Admin Switcher Button --}}
                    @if (request()->routeIs('admin.tfc.*'))
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1.5 fw-semibold shadow-sm" style="border-radius: 6px; padding: 0.35rem 0.75rem;">
                            <i class="bi bi-arrow-left-circle-fill"></i>
                            <span class="d-none d-sm-inline">VANRITI Admin</span>
                        </a>
                    @else
                        @if (auth('admin')->user()->hasPermission('manage-tfc'))
                            <a href="{{ route('admin.tfc.dashboard') }}" class="btn btn-sm d-inline-flex align-items-center gap-1.5 fw-semibold text-white shadow-sm" style="background: linear-gradient(135deg, #0A1D37, #132D54); border: 1px solid #D49B24; border-radius: 6px; padding: 0.35rem 0.75rem;" title="Switch to TFC Sales & Marketing Administration">
                                <i class="bi bi-building text-warning"></i>
                                <span class="d-none d-sm-inline">TFC Admin</span>
                            </a>
                        @endif
                    @endif

                    {{-- Live Sites Dropdown --}}
                    <div class="dropdown">
                        <button class="btn btn-light-sm dropdown-toggle d-none d-sm-inline-flex align-items-center gap-1.5" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="View Public Sites">
                            <i class="bi bi-globe text-muted"></i>
                            <span class="d-none d-md-inline">Live Sites</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="min-width: 220px;">
                            <li>
                                <a href="{{ route('home') }}" target="_blank" class="dropdown-item py-2 d-flex align-items-center justify-content-between">
                                    <span><i class="bi bi-shop text-success me-2"></i>VANRITI Store</span>
                                    <i class="bi bi-arrow-up-right small text-muted"></i>
                                </a>
                            </li>
                            <li>
                                <a href="{{ tfc_route('home') }}" target="_blank" class="dropdown-item py-2 d-flex align-items-center justify-content-between">
                                    <span><i class="bi bi-building text-warning me-2"></i>TFC Sales &amp; Mktg</span>
                                    <i class="bi bi-arrow-up-right small text-muted"></i>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="dropdown">
                        <button class="btn btn-light-sm dropdown-toggle d-flex align-items-center gap-2" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar-circle">{{ strtoupper(substr(auth('admin')->user()->name, 0, 1)) }}</span>
                            <span class="d-none d-sm-inline fw-semibold small">{{ auth('admin')->user()->name }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="min-width: 200px;">
                            <li class="px-3 py-2 border-bottom">
                                <div class="fw-semibold small">{{ auth('admin')->user()->name }}</div>
                                <div class="text-muted small" style="font-size:0.75rem;">{{ auth('admin')->user()->email }}</div>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#adminChangePasswordModal">
                                    <i class="bi bi-shield-lock me-2 text-muted"></i>Change Password
                                </button>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <form method="POST" action="{{ route('admin.logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger py-2"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <main class="admin-content">
                @if (session('success'))
                    <div class="vr-toast-container" id="vrToasts">
                        <div class="vr-toast vr-toast-success show" role="alert">
                            <div class="vr-toast-icon"><i class="bi bi-check-circle-fill"></i></div>
                            <div class="vr-toast-body">{{ session('success') }}</div>
                            <button type="button" class="vr-toast-close" data-bs-dismiss="alert" aria-label="Close">&times;</button>
                        </div>
                    </div>
                @endif
                @if (session('error'))
                    <div class="vr-toast-container" id="vrToasts">
                        <div class="vr-toast vr-toast-error show" role="alert">
                            <div class="vr-toast-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
                            <div class="vr-toast-body">{{ session('error') }}</div>
                            <button type="button" class="vr-toast-close" data-bs-dismiss="alert" aria-label="Close">&times;</button>
                        </div>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="vr-toast-container" id="vrToasts">
                        <div class="vr-toast vr-toast-error show" role="alert">
                            <div class="vr-toast-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
                            <div class="vr-toast-body">
                                <strong>Please fix the following:</strong>
                                <ul class="mb-0 mt-1 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <button type="button" class="vr-toast-close" data-bs-dismiss="alert" aria-label="Close">&times;</button>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <div class="admin-sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="modal fade" id="adminChangePasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.password.change') }}">
                    @csrf
                    <div class="modal-body">
                        @if ($errors->has('current_password') || $errors->has('new_password'))
                            <div class="alert alert-danger py-2 small">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Current Password</label>
                            <input type="password" name="current_password" class="form-control form-control-sm" required autocomplete="current-password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">New Password</label>
                            <input type="password" name="new_password" class="form-control form-control-sm" required minlength="8" autocomplete="new-password">
                            <div class="form-text">Minimum 8 characters.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Confirm New Password</label>
                            <input type="password" name="new_password_confirmation" class="form-control form-control-sm" required autocomplete="new-password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Universal Command Palette Modal --}}
    <div class="modal fade command-palette-modal" id="adminCommandPaletteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="command-palette-input-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" id="commandPaletteSearch" class="command-palette-input" placeholder="Type a destination or shortcut... (e.g. orders, products, settings)" autocomplete="off">
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="command-palette-results" id="commandPaletteResults">
                    <div class="command-palette-group-title">Primary Navigation</div>
                    <a href="{{ route('admin.dashboard') }}" class="command-palette-item" data-keywords="dashboard stats analytics home">
                        <i class="bi bi-grid-1x2"></i><span>Dashboard</span><span class="item-meta">Main Overview</span>
                    </a>
                    @if (auth('admin')->user()->hasPermission('manage-orders'))
                    <a href="{{ route('admin.orders.index') }}" class="command-palette-item" data-keywords="orders shipmojo shipments sales">
                        <i class="bi bi-cart-check"></i><span>Orders</span><span class="item-meta">Manage & Ship</span>
                    </a>
                    @endif
                    @if (auth('admin')->user()->hasPermission('manage-products'))
                    <a href="{{ route('admin.products.index') }}" class="command-palette-item" data-keywords="products catalog items sku">
                        <i class="bi bi-box-seam"></i><span>Products</span><span class="item-meta">Catalogue</span>
                    </a>
                    @endif
                    @if (auth('admin')->user()->hasPermission('manage-inventory'))
                    <a href="{{ route('admin.inventory.index') }}" class="command-palette-item" data-keywords="inventory stock alerts count">
                        <i class="bi bi-box2"></i><span>Inventory</span><span class="item-meta">Stock Alerts</span>
                    </a>
                    @endif
                    @if (auth('admin')->user()->hasPermission('manage-customers'))
                    <a href="{{ route('admin.customers.index') }}" class="command-palette-item" data-keywords="customers users shoppers">
                        <i class="bi bi-people"></i><span>Customers</span><span class="item-meta">User Directory</span>
                    </a>
                    <a href="{{ route('admin.carts.index') }}" class="command-palette-item" data-keywords="carts abandoned recovery wishlist">
                        <i class="bi bi-cart"></i><span>Abandoned Carts</span><span class="item-meta">Retention</span>
                    </a>
                    @endif
                    @if (auth('admin')->user()->hasPermission('manage-coupons'))
                    <a href="{{ route('admin.coupons.index') }}" class="command-palette-item" data-keywords="coupons discounts promo vouchers">
                        <i class="bi bi-ticket-perforated"></i><span>Coupons</span><span class="item-meta">Discounts</span>
                    </a>
                    @endif
                    @if (auth('admin')->user()->hasPermission('manage-settings'))
                    <a href="{{ route('admin.settings.index') }}" class="command-palette-item" data-keywords="settings configuration whatsapp razorpay shipmojo">
                        <i class="bi bi-gear"></i><span>Settings</span><span class="item-meta">Store Config</span>
                    </a>
                    @endif

                    <div class="command-palette-group-title mt-2">External Links</div>
                    <a href="{{ route('home') }}" target="_blank" class="command-palette-item" data-keywords="storefront shop live website home">
                        <i class="bi bi-shop"></i><span>Visit Live Storefront</span><i class="bi bi-arrow-up-right ms-auto text-muted small"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script nonce="{{ $cspNonce }}" src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script nonce="{{ $cspNonce }}" src="{{ asset('js/admin.js') }}?v=1.5"></script>
    @stack('scripts')
</body>
</html>