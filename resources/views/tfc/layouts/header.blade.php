<header class="header-wrapper">
    {{-- Top Marquee Announcement Bar (Eduse Signature Style) --}}
    <div class="top-marquee" role="region" aria-label="Announcements">
        <div class="top-marquee__inner">
            <div class="top-marquee__track">
                <span>Welcome to {{ tfc_setting('company_name', 'TFC Sales & Marketing') }} — India's Fast Growing Direct Selling Business Opportunity &bull; {{ tfc_setting('company_tagline', 'Connecting Products. Creating Possibilities.') }}</span>
                <span aria-hidden="true">Welcome to {{ tfc_setting('company_name', 'TFC Sales & Marketing') }} — India's Fast Growing Direct Selling Business Opportunity &bull; {{ tfc_setting('company_tagline', 'Connecting Products. Creating Possibilities.') }}</span>
            </div>
        </div>
    </div>

    {{-- Sticky Main Navbar --}}
    <nav class="navbar navbar-expand-lg tfc-navbar py-2" aria-label="Main navigation">
        <div class="container d-flex align-items-center justify-content-between">
            {{-- Logo --}}
            <a class="navbar-brand logo d-flex align-items-center py-0" href="{{ tfc_route('home') }}">
                <img src="{{ tfc_logo_url() }}" alt="{{ tfc_setting('company_name', 'TFC Sales & Marketing') }}" class="tfc-logo-img" style="max-height: 48px; width: auto;">
            </a>

            {{-- Mobile Hamburger --}}
            <button class="navbar-toggler border-0 p-2" type="button" data-bs-toggle="collapse" data-bs-target="#tfcNavbarNav" aria-controls="tfcNavbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="ri-menu-line fs-2 text-dark"></i>
            </button>

            {{-- Nav Menu Items --}}
            <div class="collapse navbar-collapse" id="tfcNavbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 py-2 py-lg-0 align-items-lg-center gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link tfc-nav-link {{ request()->is('/') || request()->is('tfc') ? 'active' : '' }}" href="{{ tfc_route('home') }}">
                            HOME
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link tfc-nav-link dropdown-toggle {{ request()->is('*about*') || request()->is('*direct-selling*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            ABOUT US <i class="ri-arrow-down-s-line dropdown-icon ms-1" style="font-size: 14px;"></i>
                        </a>
                        <ul class="dropdown-menu border-0 shadow-lg p-2 rounded-4">
                            <li><a class="dropdown-item py-2 px-3 rounded-3" href="{{ tfc_route('about') }}"><i class="ri-building-line text-warning me-2"></i>About Company</a></li>
                            <li><a class="dropdown-item py-2 px-3 rounded-3" href="{{ tfc_route('direct-selling') }}"><i class="ri-community-line text-warning me-2"></i>Direct Selling Model</a></li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link tfc-nav-link {{ request()->is('*products*') || request()->is('*category*') ? 'active' : '' }}" href="{{ tfc_route('products') }}">
                            PRODUCTS
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link tfc-nav-link dropdown-toggle {{ request()->is('*opportunity*') || request()->is('*business-plan*') || request()->is('*how-it-works*') || request()->is('*become-a-seller*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            OPPORTUNITY <i class="ri-arrow-down-s-line dropdown-icon ms-1" style="font-size: 14px;"></i>
                        </a>
                        <ul class="dropdown-menu border-0 shadow-lg p-2 rounded-4">
                            <li><a class="dropdown-item py-2 px-3 rounded-3" href="{{ tfc_route('business-plan') }}"><i class="ri-line-chart-line text-warning me-2"></i>Business Plan</a></li>
                            <li><a class="dropdown-item py-2 px-3 rounded-3" href="{{ tfc_route('how-it-works') }}"><i class="ri-guide-line text-warning me-2"></i>How It Works</a></li>
                            <li><a class="dropdown-item py-2 px-3 rounded-3" href="{{ tfc_route('become-a-seller') }}"><i class="ri-user-add-line text-warning me-2"></i>Become a Direct Seller</a></li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link tfc-nav-link {{ request()->is('*downloads*') ? 'active' : '' }}" href="{{ tfc_route('downloads') }}">
                            DOWNLOADS
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link tfc-nav-link {{ request()->is('*contact*') ? 'active' : '' }}" href="{{ tfc_route('contact') }}">
                            CONTACT US
                        </a>
                    </li>
                </ul>

                {{-- Right CTAs --}}
                <div class="d-flex align-items-center gap-2 pt-2 pt-lg-0 border-top border-lg-0">
                    @if (auth()->check() && auth()->user()->isTfcMember())
                        <a href="{{ route('tfc.preview.member.dashboard') }}" class="btn btn-sm btn-warning text-dark fw-bold d-inline-flex align-items-center gap-1 shadow-sm px-3 py-2 rounded-3">
                            <i class="ri-dashboard-3-line"></i>
                            <span>Portal ({{ auth()->user()->directSellingMember->tfc_member_id }})</span>
                        </a>
                    @else
                        <a href="{{ route('tfc.preview.member.login') }}" class="btn btn-sm btn-outline-dark fw-medium d-inline-flex align-items-center gap-1 px-3 py-2 rounded-3">
                            <i class="ri-login-box-line"></i>
                            <span>Login</span>
                        </a>
                        <a href="{{ route('tfc.preview.member.register') }}" class="btn btn-tfc-primary btn-sm px-3 py-2 fw-semibold rounded-3 shadow-sm d-inline-flex align-items-center gap-1">
                            <i class="ri-user-star-line"></i>
                            <span>Sign Up</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </nav>
</header>
