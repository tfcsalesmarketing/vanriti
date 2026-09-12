<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ store_name() }} Admin</title>
    <meta name="theme-color" content="#263D25">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar" id="adminSidebar">
            @php
                $_adminLogo = setting('store_logo');
                if (! $_adminLogo) { foreach (['svg', 'png', 'webp', 'jpg'] as $_e) { if (file_exists(public_path('images/logo.' . $_e))) { $_adminLogo = 'images/logo.' . $_e; break; } } }
            @endphp
            <div class="sidebar-brand">
                @if ($_adminLogo)
                    <img src="{{ asset($_adminLogo) }}" alt="{{ store_name() }}" class="brand-logo">
                @else
                    <span class="brand-text" style="color:var(--vanriti-green);">{{ strtoupper(store_name()) }}</span>
                @endif
            </div>
            <nav class="sidebar-nav">
                @include('admin.partials.sidebar-nav')
            </nav>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <button class="btn btn-link d-lg-none text-dark" id="sidebarToggle" type="button">
                    <i class="bi bi-list fs-3"></i>
                </button>
                <div class="topbar-title">
                    <h5 class="mb-0 fw-bold" style="font-family:'Inter',sans-serif;">@yield('title', 'Dashboard')</h5>
                </div>
                <div class="ms-auto d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <button class="btn btn-light-sm dropdown-toggle d-flex align-items-center gap-2" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar-circle">{{ strtoupper(substr(auth('admin')->user()->name, 0, 1)) }}</span>
                            <span class="d-none d-sm-inline fw-semibold small">{{ auth('admin')->user()->name }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text small text-muted">{{ auth('admin')->user()->email }}</span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('admin.logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
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

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    @stack('scripts')
</body>
</html>