<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'TFC Sales & Marketing | ' . tfc_setting('company_tagline', 'Connecting Products. Creating Possibilities.'))</title>
    <meta name="description" content="@yield('meta_description', 'TFC Sales & Marketing is an ethical direct-selling entity connecting genuine Ayurvedic and wellness products with independent entrepreneurs across India.')">
    <link rel="canonical" href="{{ canonical_url() }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- TFC Brand Styling -->
    <link rel="stylesheet" href="{{ tfc_asset('css/tfc-style.css') }}?v={{ file_exists(public_path('css/tfc-style.css')) ? filemtime(public_path('css/tfc-style.css')) : 1 }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ tfc_logo_url() }}">

    @stack('styles')
</head>
<body>
    @include('tfc.layouts.header')

    <main>
        {{-- Flash Success Alert --}}
        @if (session('success'))
            <div class="container mt-4">
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-check-circle-fill fs-5"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    @include('tfc.layouts.footer')

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
