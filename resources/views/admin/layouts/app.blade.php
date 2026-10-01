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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=1.4">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar" id="adminSidebar">
            @php
                $_adminLogo = brand_logo_path();
            @endphp
            <div class="sidebar-brand">
                @if ($_adminLogo)
                    <img src="{{ image_url($_adminLogo) }}" alt="{{ store_name() }}" class="brand-logo">
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
                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#adminChangePasswordModal">
                                    <i class="bi bi-shield-lock me-2"></i>Change Password
                                </button>
                            </li>
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

    <script nonce="{{ $cspNonce }}" src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script nonce="{{ $cspNonce }}" src="{{ asset('js/admin.js') }}?v=1.4"></script>
    @stack('scripts')
</body>
</html>