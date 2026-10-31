<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'TFC Sales & Marketing | ' . tfc_setting('company_tagline', 'Connecting Products. Creating Possibilities.'))</title>
    <meta name="description" content="@yield('meta_description', 'TFC Sales & Marketing is a premier direct-selling enterprise empowering independent entrepreneurs across India with high-performance wellness, personal care, and lifestyle formulations.')">
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
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill fs-5"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif

        {{-- Flash Error Alert --}}
        @if (session('error'))
            <div class="container mt-4">
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
                    <div class="fw-semibold">{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif

        {{-- Flash Warning Alert --}}
        @if (session('warning'))
            <div class="container mt-4">
                <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-circle-fill fs-5 flex-shrink-0"></i>
                    <div>{{ session('warning') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif

        {{-- Flash Info Alert --}}
        @if (session('info'))
            <div class="container mt-4">
                <div class="alert alert-info alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
                    <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
                    <div>{{ session('info') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    @include('tfc.layouts.footer')

    {{-- Floating WhatsApp Chat Widget --}}
    @php
        $whatsappWidgetEnabled = (bool) tfc_setting('whatsapp_widget_enabled', true);
        $whatsappNumber = tfc_setting('whatsapp_phone_number') ?: tfc_setting('contact_phone', '919876543210');
        $whatsappCleanNumber = preg_replace('/[^0-9]/', '', (string) $whatsappNumber);
        $whatsappGreeting = tfc_setting('whatsapp_widget_greeting', 'Need help with our direct selling business or products? Chat with us on WhatsApp!');
        $whatsappDefaultMsg = tfc_setting('whatsapp_widget_default_msg', 'Hello TFC Sales & Marketing, I am interested in learning more about TFC products and the direct selling business opportunity.');
        $whatsappLink = 'https://wa.me/' . $whatsappCleanNumber . '?text=' . rawurlencode((string) $whatsappDefaultMsg);
    @endphp

    @if ($whatsappWidgetEnabled && ! empty($whatsappCleanNumber))
        <div class="tfc-whatsapp-widget" id="tfcWhatsappWidget">
            <div class="tfc-whatsapp-tooltip" id="tfcWhatsappTooltip">
                <div class="tfc-whatsapp-tooltip-header">
                    <span class="tfc-whatsapp-dot"></span>
                    <strong>TFC Support Team</strong>
                    <button type="button" class="btn-close btn-close-white ms-auto" style="font-size: 0.65rem;" onclick="document.getElementById('tfcWhatsappTooltip').style.display='none';" aria-label="Close"></button>
                </div>
                <div class="tfc-whatsapp-tooltip-body">
                    {{ $whatsappGreeting }}
                </div>
            </div>
            <a href="{{ $whatsappLink }}" target="_blank" rel="noopener noreferrer" class="tfc-whatsapp-btn" title="Chat on WhatsApp" aria-label="Chat with TFC Sales & Marketing on WhatsApp">
                <i class="bi bi-whatsapp"></i>
                <span class="tfc-whatsapp-ping"></span>
            </a>
        </div>
    @endif

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
