@php
    $_adErrBrand = 'VANRITI ADMIN';
    $_adErrLogo = null;
    try {
        $_adErrBrand = strtoupper((string) store_name()) . ' ADMIN';
        $_adErrLogo = setting('store_logo');
        if (! $_adErrLogo) {
            foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
                if (file_exists(public_path('images/logo.' . $ext))) {
                    $_adErrLogo = 'images/logo.' . $ext;
                    break;
                }
            }
        }
    } catch (\Throwable $__e) {
        $_adErrBrand = 'VANRITI ADMIN';
        $_adErrLogo = null;
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
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=1.4">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--vanriti-cream, #F7F4EA);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 1.5rem;
            color: #2b2b2b;
        }
        .ad-error-card {
            width: 100%;
            max-width: 430px;
            border: 0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(38, 61, 37, .06), 0 18px 40px -18px rgba(38, 61, 37, .35);
        }
        .ad-error-top {
            height: 6px;
            background: var(--vanriti-green, #263D25);
        }
        .ad-error-body { padding: 2.5rem 2rem 2.25rem; text-align: center; }
        .ad-error-brand {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1.75rem;
            font-weight: 800;
            letter-spacing: .14em;
            font-size: .95rem;
            color: var(--vanriti-green, #263D25);
            text-decoration: none;
        }
        .ad-error-brand:hover { color: var(--vanriti-green, #263D25); }
        .ad-error-brand img { max-height: 26px; }
        .ad-error-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 74px;
            height: 74px;
            border-radius: 50%;
            background: #EEF2E3;
            color: var(--vanriti-green-light, #6F8736);
            font-size: 2rem;
            margin-bottom: 1.25rem;
        }
        .ad-error-code {
            font-size: clamp(3.5rem, 12vw, 6.5rem);
            line-height: 1;
            font-weight: 800;
            color: var(--vanriti-green, #263D25);
            letter-spacing: .02em;
        }
        .ad-error-msg {
            color: #6b7280;
            max-width: 380px;
            margin-inline: auto;
            font-size: 1rem;
        }
        .ad-btn-back {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            background: var(--vanriti-green, #263D25);
            color: #fff;
            border: 0;
            padding: .6rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: .95rem;
            text-decoration: none;
            transition: background .15s ease;
        }
        .ad-btn-back:hover { background: #314a30; color: #fff; }
        .ad-btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            background: transparent;
            color: var(--vanriti-green, #263D25);
            border: 1px solid #d3dcc4;
            padding: .6rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: .95rem;
            text-decoration: none;
            transition: background .15s ease;
        }
        .ad-btn-ghost:hover { background: #EEF2E3; color: var(--vanriti-green, #263D25); }
    </style>
</head>
<body>
    <div class="card ad-error-card">
        <div class="ad-error-top"></div>
        <div class="ad-error-body">
            <a class="ad-error-brand" href="{{ route('admin.dashboard') }}">
                @if ($_adErrLogo)
                    <img src="{{ image_url($_adErrLogo, 'favicon.ico') }}" alt="{{ e($store_name ?? 'admin') }}">
                @endif
                <span>{{ e($_adErrBrand) }}</span>
            </a>

            <div class="ad-error-icon mx-auto"><i class="@yield('icon', 'bi bi-exclamation-triangle')"></i></div>
            <div class="ad-error-code">@yield('code', 'Error')</div>
            <h1 class="h5 mt-3 mb-1 fw-bold">@yield('title')</h1>
            <p class="ad-error-msg mt-2 mb-4">@yield('message')</p>

            <div class="d-inline-flex flex-wrap gap-2 justify-content-center align-items-center">
                @hasSection('buttons')
                    @yield('buttons')
                @else
                    <a href="{{ route('admin.dashboard') }}" class="ad-btn-back"><i class="bi bi-speedometer2"></i> Back to Dashboard</a>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
