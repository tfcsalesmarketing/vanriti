<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Member Dashboard') | TFC Sales & Marketing</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --tfc-navy: #0A1D37;
            --tfc-navy-dark: #061122;
            --tfc-gold: #D49B24;
            --tfc-gold-light: #F7E7C4;
            --tfc-orange: #F97316;
            --tfc-blue: #0284C7;
            --tfc-bg: #F8FAFC;
            --tfc-card-border: #E2E8F0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--tfc-bg);
            color: #1E293B;
            min-height: 100vh;
        }

        h1, h2, h3, h4, h5, h6, .brand-font {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* Sidebar Styling */
        .member-sidebar {
            width: 270px;
            background: linear-gradient(180deg, var(--tfc-navy) 0%, var(--tfc-navy-dark) 100%);
            color: #FFFFFF;
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1040;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .member-sidebar-brand {
            padding: 1.5rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .member-nav {
            padding: 1rem 0.75rem;
            flex-grow: 1;
            overflow-y: auto;
        }

        .member-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #CBD5E1;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.92rem;
            margin-bottom: 0.25rem;
            transition: all 0.2s ease;
        }

        .member-nav-link:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(3px);
        }

        .member-nav-link.active {
            color: #FFFFFF;
            background: linear-gradient(135deg, rgba(212, 155, 36, 0.35) 0%, rgba(212, 155, 36, 0.15) 100%);
            border-left: 3px solid var(--tfc-gold);
            font-weight: 600;
        }

        .member-nav-link i {
            font-size: 1.15rem;
        }

        .member-main-wrapper {
            margin-left: 270px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease;
        }

        .member-topbar {
            height: 70px;
            background: #FFFFFF;
            border-bottom: 1px solid var(--tfc-card-border);
            padding: 0 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .member-content {
            padding: 1.75rem;
            flex-grow: 1;
        }

        .member-card {
            background: #FFFFFF;
            border: 1px solid var(--tfc-card-border);
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: box-shadow 0.2s ease;
        }

        .member-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .leg-badge-orange {
            background-color: rgba(249, 115, 22, 0.12);
            color: #C2410C;
            border: 1px solid rgba(249, 115, 22, 0.3);
        }

        .leg-badge-blue {
            background-color: rgba(2, 132, 199, 0.12);
            color: #0369A1;
            border: 1px solid rgba(2, 132, 199, 0.3);
        }

        @media (max-width: 991.98px) {
            .member-sidebar {
                transform: translateX(-100%);
            }
            .member-sidebar.show {
                transform: translateX(0);
            }
            .member-main-wrapper {
                margin-left: 0;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    @php
        $currentMember = $currentMember ?? auth()->user()->directSellingMember;
    @endphp

    <!-- Mobile Backdrop -->
    <div class="offcanvas-backdrop fade d-none" id="sidebarBackdrop"></div>

    <!-- Sidebar -->
    <aside class="member-sidebar" id="memberSidebar">
        <!-- Brand Header -->
        <div class="member-sidebar-brand d-flex align-items-center justify-content-between">
            <a href="{{ route('tfc.preview.member.dashboard') }}" class="text-decoration-none d-flex align-items-center gap-2">
                <span class="d-inline-flex align-items-center justify-content-center rounded-3 p-1.5" style="background: var(--tfc-gold);">
                    <i class="bi bi-diagram-3-fill text-dark fs-5"></i>
                </span>
                <div>
                    <div class="fw-bold text-white small" style="letter-spacing: 0.5px;">TFC SALES &amp; MKTG</div>
                    <div class="text-secondary" style="font-size: 0.7rem;">Member Portal</div>
                </div>
            </a>
            <button class="btn btn-sm btn-link text-white d-lg-none p-0" id="closeSidebarBtn">
                <i class="bi bi-x-lg fs-5"></i>
            </button>
        </div>

        <!-- Member Mini Profile Box -->
        <div class="px-3 py-3 mx-3 my-2 rounded-3" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-warning text-dark fw-bold d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 0.95rem;">
                    {{ strtoupper(substr($currentMember->full_name, 0, 1)) }}
                </div>
                <div class="overflow-hidden">
                    <div class="fw-bold text-white small text-truncate">{{ $currentMember->full_name }}</div>
                    <div class="text-warning small fw-semibold font-monospace" style="font-size: 0.78rem;">{{ $currentMember->tfc_member_id }}</div>
                </div>
            </div>
            <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center" style="font-size: 0.72rem;">
                <span class="badge bg-success bg-opacity-75 text-white">Active</span>
                <span class="text-light opacity-75">
                    @if ($currentMember->kyc_status === 'verified')
                        <i class="bi bi-patch-check-fill text-success"></i> KYC Verified
                    @elseif ($currentMember->kyc_status === 'pending')
                        <i class="bi bi-hourglass-split text-warning"></i> KYC Pending
                    @else
                        <i class="bi bi-shield-slash text-secondary"></i> KYC Optional
                    @endif
                </span>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="member-nav">
            <a href="{{ route('tfc.preview.member.dashboard') }}" class="member-nav-link {{ request()->routeIs('tfc.preview.member.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>

            <div class="text-uppercase text-secondary fw-bold px-3 pt-3 pb-1" style="font-size: 0.68rem; letter-spacing: 0.8px;">Network Genealogy</div>

            <a href="{{ route('tfc.preview.member.team.tree') }}" class="member-nav-link {{ request()->routeIs('tfc.preview.member.team.tree') ? 'active' : '' }}">
                <i class="bi bi-diagram-3"></i>
                <span>Visual Tree</span>
            </a>

            <a href="{{ route('tfc.preview.member.team.direct') }}" class="member-nav-link {{ request()->routeIs('tfc.preview.member.team.direct') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                <span>Direct Referrals</span>
            </a>

            <a href="{{ route('tfc.preview.member.team.group', 'orange') }}" class="member-nav-link {{ request()->is('*/team/group/orange*') ? 'active' : '' }}">
                <i class="bi bi-circle-fill text-warning" style="font-size: 0.75rem;"></i>
                <span>Orange Group</span>
            </a>

            <a href="{{ route('tfc.preview.member.team.group', 'blue') }}" class="member-nav-link {{ request()->is('*/team/group/blue*') ? 'active' : '' }}">
                <i class="bi bi-circle-fill text-info" style="font-size: 0.75rem;"></i>
                <span>Blue Group</span>
            </a>

            <div class="text-uppercase text-secondary fw-bold px-3 pt-3 pb-1" style="font-size: 0.68rem; letter-spacing: 0.8px;">Account &amp; Compliance</div>

            <a href="{{ route('tfc.preview.member.kyc.index') }}" class="member-nav-link {{ request()->routeIs('tfc.preview.member.kyc.*') ? 'active' : '' }}">
                <i class="bi bi-shield-check"></i>
                <span>KYC Verification</span>
            </a>

            <a href="{{ route('tfc.preview.member.profile') }}" class="member-nav-link {{ request()->routeIs('tfc.preview.member.profile') ? 'active' : '' }}">
                <i class="bi bi-person-gear"></i>
                <span>Profile &amp; Password</span>
            </a>

            <div class="text-uppercase text-secondary fw-bold px-3 pt-3 pb-1" style="font-size: 0.68rem; letter-spacing: 0.8px;">Business &amp; Support</div>

            <a href="{{ route('shop.index') }}" target="_blank" class="member-nav-link">
                <i class="bi bi-bag-check"></i>
                <span>VANRITI Store <i class="bi bi-box-arrow-up-right ms-auto" style="font-size: 0.75rem;"></i></span>
            </a>

            <a href="{{ route('tfc.preview.grievance') }}" target="_blank" class="member-nav-link">
                <i class="bi bi-ticket-detailed"></i>
                <span>Consumer Grievance <i class="bi bi-box-arrow-up-right ms-auto" style="font-size: 0.75rem;"></i></span>
            </a>
        </nav>

        <!-- Logout Form Footer -->
        <div class="p-3 border-top border-white border-opacity-10">
            <form action="{{ route('tfc.preview.member.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-light w-100 btn-sm d-flex align-items-center justify-content-center gap-2 py-2">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="member-main-wrapper">
        <!-- Top Navbar -->
        <header class="member-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none p-1.5" id="toggleSidebarBtn">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-none d-sm-block">
                    <span class="text-muted small">TFC Member ID:</span>
                    <span class="font-monospace fw-bold text-dark px-2 py-1 rounded bg-light border ms-1">{{ $currentMember->tfc_member_id }}</span>
                </div>
            </div>

            <!-- Topbar Referral Quick Share -->
            @php
                $refLink = route('tfc.preview.member.register', ['ref' => $currentMember->tfc_member_id]);
            @endphp
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-outline-warning text-dark d-flex align-items-center gap-1.5 fw-semibold" 
                        onclick="copyReferralLink('{{ $refLink }}', this)">
                    <i class="bi bi-share-fill text-warning"></i>
                    <span class="d-none d-md-inline">Copy Referral Link</span>
                </button>

                <div class="vr mx-2 d-none d-sm-block"></div>

                <a href="{{ route('tfc.preview.home') }}" target="_blank" class="btn btn-sm btn-outline-secondary d-none d-md-flex align-items-center gap-1">
                    <i class="bi bi-globe"></i>
                    <span>TFC Website</span>
                </a>
            </div>
        </header>

        <!-- Body Content -->
        <main class="member-content">
            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm border-0 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm border-0 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 text-warning"></i>
                    <div>{{ session('warning') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm border-0 mb-4" role="alert">
                    <i class="bi bi-exclamation-octagon-fill fs-5 text-danger"></i>
                    <div>{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('info'))
                <div class="alert alert-info alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm border-0 mb-4" role="alert">
                    <i class="bi bi-info-circle-fill fs-5 text-info"></i>
                    <div>{{ session('info') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle for mobile devices
        const sidebar = document.getElementById('memberSidebar');
        const toggleBtn = document.getElementById('toggleSidebarBtn');
        const closeBtn = document.getElementById('closeSidebarBtn');
        const backdrop = document.getElementById('sidebarBackdrop');

        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.add('show');
                backdrop.classList.remove('d-none');
                backdrop.classList.add('show');
            });
        }

        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('show');
            if (backdrop) {
                backdrop.classList.remove('show');
                backdrop.classList.add('d-none');
            }
        }

        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (backdrop) backdrop.addEventListener('click', closeSidebar);

        // Copy referral link helper
        function copyReferralLink(link, btn) {
            navigator.clipboard.writeText(link).then(() => {
                const origHtml = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check-lg text-success"></i> Copied!';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-outline-warning');
                setTimeout(() => {
                    btn.innerHTML = origHtml;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-warning');
                }, 2500);
            }).catch(err => {
                prompt("Copy your referral link:", link);
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
