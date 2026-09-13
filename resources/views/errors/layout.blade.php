@php
    $_vrBrand = config('app.name', 'VANRITI');
    $_vrLogo = null;
    try {
        $_vrBrand = store_name();
        $_vrLogo = setting('store_logo');
        if (! $_vrLogo) {
            foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
                if (file_exists(public_path('images/logo.' . $ext))) {
                    $_vrLogo = 'images/logo.' . $ext;
                    break;
                }
            }
        }
    } catch (\Throwable $__e) {
        $_vrBrand = config('app.name', 'VANRITI');
        $_vrLogo = null;
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#263D25">
    <title>@yield('code', 'Error') &middot; @yield('title', 'Something went wrong')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/storefront.css') }}?v={{ time() }}">
    <style>
        .vr-error-brand {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            margin-bottom: 1.75rem;
            font-weight: 800;
            letter-spacing: .16em;
            color: var(--vr-green-dark);
            text-decoration: none;
        }
        .vr-error-brand:hover { color: var(--vr-green-dark); }
        .vr-error-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 74px;
            height: 74px;
            border-radius: 50%;
            background: var(--vr-green-soft);
            color: var(--vr-green);
            font-size: 2rem;
            margin-bottom: 1.25rem;
        }
        .vr-error-code {
            font-size: clamp(3.5rem, 12vw, 6.5rem);
            line-height: 1;
            font-weight: 800;
            color: var(--vr-green-dark);
            letter-spacing: .02em;
        }
        .vr-error-msg {
            color: var(--vr-muted);
            max-width: 380px;
            margin-inline: auto;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <div class="vr-auth-shell">
        <div class="card vr-auth-card">
            <div class="card-body text-center px-4 py-5">
                <a class="vr-error-brand" href="{{ route('home') }}">
                    @if ($_vrLogo)
                        <img src="{{ image_url($_vrLogo, 'favicon.ico') }}" alt="{{ e($_vrBrand) }}" height="42" class="vr-logo">
                    @else
                        <span>{{ strtoupper(e($_vrBrand)) }}<span class="dot">.</span></span>
                    @endif
                </a>

                <div class="vr-error-icon mx-auto"><i class="@yield('icon', 'bi bi-exclamation-triangle')"></i></div>
                <div class="vr-error-code">@yield('code', 'Error')</div>
                <h1 class="h5 mt-3 mb-1 fw-bold">@yield('title')</h1>
                <p class="vr-error-msg mt-2 mb-4">@yield('message')</p>

                <div class="d-inline-flex flex-wrap gap-2 justify-content-center align-items-center">
                    @hasSection('buttons')
                        @yield('buttons')
                    @else
                        <a href="{{ route('home') }}" class="btn btn-vr">Go back home</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>