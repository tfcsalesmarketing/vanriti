@php
    $headerMenus = \App\Models\Tfc\TfcMenu::active()->where('location', 'header')->get();
@endphp

{{-- Top Information Bar --}}
<div class="tfc-topbar d-none d-lg-block">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <span class="d-inline-flex align-items-center">
                <i class="bi bi-shield-check text-warning me-1.5"></i>
                <span class="fw-semibold text-white">Official Direct-Selling Partner for VANRITI</span>
            </span>
            <span class="tfc-topbar-divider">|</span>
            <span class="text-warning fw-semibold" style="letter-spacing: 0.5px;">{{ tfc_setting('company_tagline', 'CONNECTING PRODUCTS. CREATING POSSIBILITIES.') }}</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            @if (tfc_setting('support_phone'))
                <a href="tel:{{ tfc_setting('support_phone') }}" class="d-inline-flex align-items-center">
                    <i class="bi bi-telephone text-warning me-1"></i>{{ tfc_setting('support_phone') }}
                </a>
            @endif
            @if (tfc_setting('contact_email'))
                <span class="tfc-topbar-divider">|</span>
                <a href="mailto:{{ tfc_setting('contact_email') }}" class="d-inline-flex align-items-center">
                    <i class="bi bi-envelope text-warning me-1"></i>{{ tfc_setting('contact_email') }}
                </a>
            @endif
            <span class="tfc-topbar-divider">|</span>
            <a href="{{ tfc_route('grievance') }}" class="text-warning fw-semibold d-inline-flex align-items-center">
                <i class="bi bi-ticket-detailed me-1"></i>Consumer Grievance
            </a>
        </div>
    </div>
</div>

{{-- Main Sticky Navbar --}}
<nav class="navbar navbar-expand-lg tfc-navbar">
    <div class="container">
        {{-- TFC Logo --}}
        <a class="navbar-brand d-flex align-items-center py-0" href="{{ tfc_route('home') }}">
            <img src="{{ tfc_logo_url() }}" alt="{{ tfc_setting('company_name', 'TFC Sales & Marketing') }}" class="tfc-logo-img">
        </a>

        {{-- Mobile Hamburger Toggler --}}
        <button class="navbar-toggler border-0 p-2" type="button" data-bs-toggle="collapse" data-bs-target="#tfcNavbarNav" aria-controls="tfcNavbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list fs-2 text-dark"></i>
        </button>

        {{-- Navigation Menu --}}
        <div class="collapse navbar-collapse" id="tfcNavbarNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 py-2 py-lg-0">
                @forelse ($headerMenus as $item)
                    <li class="nav-item">
                        @php
                            $targetUrl = str_starts_with($item->url, 'http') ? $item->url : tfc_route(ltrim($item->url, '/'));
                            $cleanPath = trim(ltrim($item->url, '/'), '/');
                            $isActive = ($cleanPath === '' && (request()->is('/') || request()->is('tfc')))
                                || ($cleanPath !== '' && (request()->is($cleanPath) || request()->is('tfc/' . $cleanPath) || request()->is($cleanPath . '/*') || request()->is('tfc/' . $cleanPath . '/*')));
                        @endphp
                        <a class="nav-link tfc-nav-link {{ $isActive ? 'active' : '' }}" href="{{ $targetUrl }}" {{ $item->open_new_tab ? 'target="_blank" rel="noopener"' : '' }}>
                            {{ $item->title }}
                        </a>
                    </li>
                @empty
                    <li class="nav-item"><a class="nav-link tfc-nav-link" href="{{ tfc_route('home') }}">Home</a></li>
                    <li class="nav-item"><a class="nav-link tfc-nav-link" href="{{ tfc_route('about') }}">About TFC</a></li>
                    <li class="nav-item"><a class="nav-link tfc-nav-link" href="{{ tfc_route('brand') }}">VANRITI Brand</a></li>
                    <li class="nav-item"><a class="nav-link tfc-nav-link" href="{{ tfc_route('products') }}">Products</a></li>
                    <li class="nav-item"><a class="nav-link tfc-nav-link" href="{{ tfc_route('opportunity') }}">Opportunity</a></li>
                    <li class="nav-item"><a class="nav-link tfc-nav-link" href="{{ tfc_route('how-it-works') }}">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link tfc-nav-link" href="{{ tfc_route('grievance') }}">Grievance</a></li>
                    <li class="nav-item"><a class="nav-link tfc-nav-link" href="{{ tfc_route('contact') }}">Contact</a></li>
                @endforelse
            </ul>

            {{-- Right CTA --}}
            <div class="d-flex align-items-center gap-2 pt-2 pt-lg-0 border-top border-lg-0">
                @if (auth()->check() && auth()->user()->isTfcMember())
                    <a href="{{ route('tfc.preview.member.dashboard') }}" class="btn btn-sm btn-warning text-dark fw-bold d-inline-flex align-items-center gap-1 shadow-sm px-3 py-2">
                        <i class="bi bi-speedometer2"></i>
                        <span>Portal ({{ auth()->user()->directSellingMember->tfc_member_id }})</span>
                    </a>
                @else
                    <a href="{{ route('tfc.preview.member.login') }}" class="btn btn-sm btn-outline-dark fw-medium d-inline-flex align-items-center gap-1 px-3 py-2">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Member Login</span>
                    </a>
                    <a href="{{ route('tfc.preview.member.register') }}" class="btn btn-tfc-primary w-100 w-lg-auto py-2">
                        <i class="bi bi-person-plus-fill me-1.5"></i>Join TFC
                    </a>
                @endif
            </div>
        </div>
    </div>
</nav>
