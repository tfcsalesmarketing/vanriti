@php
    $_brandName = store_name();
    $_brandLogo = brand_logo_path();
    $_logoUrl = $_brandLogo ? image_url($_brandLogo, 'favicon.ico') : asset('images/logo.webp');
    $_whatsappNum = setting('whatsapp_number', '916392543052');
    $_whatsappClean = preg_replace('/\D+/', '', (string) $_whatsappNum) ?: '916392543052';
    $_supportEmail = setting('support_email', 'support@vanriti.com');
    $_amazonUrl = setting('amazon_store_url', 'https://www.amazon.in/s?k=VANRITI');
    $_flipkartUrl = setting('flipkart_store_url', 'https://www.flipkart.com/search?q=VANRITI');
    $_cspNonce = function_exists('csp_nonce') ? csp_nonce() : '';
    $_customMsg = $maintenanceData['message'] ?? "We're currently enhancing our storefront to bring you a refreshed Ayurvedic wellness experience. We'll be back shortly!";
@endphp
<!DOCTYPE html>
<html lang="en-IN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#1e3a24">
    <title>{{ $_brandName }} &middot; We're Brewing Fresh Formulations</title>
    
    <link rel="icon" href="{{ brand_favicon_url() }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ brand_favicon_url() }}">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --vr-forest: #1e3a24;
            --vr-forest-deep: #142818;
            --vr-sage: #5c782b;
            --vr-sage-light: #e8eee0;
            --vr-gold: #c5a059;
            --vr-gold-light: #fdfaf3;
            --vr-cream: #fbfaf7;
            --vr-card-bg: #ffffff;
            --vr-text-main: #1f2937;
            --vr-text-muted: #64748b;
            --vr-border: rgba(38, 61, 37, 0.12);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: radial-gradient(circle at 50% 12%, #f5f8f3 0%, #ebeee7 55%, #e1e6dd 100%);
            color: var(--vr-text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            padding: 24px 16px;
            position: relative;
            overflow-x: hidden;
        }

        /* Subtle Ayurvedic Botanical Background Accents */
        body::before,
        body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            filter: blur(80px);
            opacity: 0.35;
        }
        body::before {
            width: 480px;
            height: 480px;
            background: #cbd8c2;
            top: -120px;
            left: -120px;
        }
        body::after {
            width: 420px;
            height: 420px;
            background: #ebd8b5;
            bottom: -100px;
            right: -100px;
        }

        .vr-maint-wrapper {
            width: 100%;
            max-width: 620px;
            margin: auto;
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Brand Header */
        .vr-maint-header {
            text-align: center;
            margin-bottom: 22px;
        }
        .vr-maint-logo {
            max-height: 52px;
            width: auto;
            object-fit: contain;
            margin-bottom: 8px;
            filter: drop-shadow(0 2px 8px rgba(30, 58, 36, 0.08));
        }
        .vr-maint-tagline {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: var(--vr-forest);
            opacity: 0.85;
        }

        /* Main Card */
        .vr-maint-card {
            background: var(--vr-card-bg);
            border: 1px solid var(--vr-border);
            border-radius: 24px;
            padding: 38px 32px 32px;
            box-shadow: 0 16px 40px rgba(30, 58, 36, 0.07), 0 1px 3px rgba(0, 0, 0, 0.04);
            text-align: center;
            width: 100%;
            backdrop-filter: blur(8px);
        }

        /* Animated Herbal Emblem */
        .vr-emblem-wrap {
            position: relative;
            width: 88px;
            height: 88px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .vr-emblem-ring {
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            border: 2px dashed rgba(92, 120, 43, 0.35);
            animation: spinRing 24s linear infinite;
        }
        .vr-emblem-glow {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: rgba(92, 120, 43, 0.15);
            animation: pulseGlow 2.8s ease-in-out infinite;
        }
        .vr-emblem-icon {
            position: relative;
            width: 74px;
            height: 74px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--vr-forest) 0%, #2f5436 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f7eedb;
            font-size: 2.1rem;
            box-shadow: 0 8px 24px rgba(30, 58, 36, 0.25);
            border: 2px solid rgba(197, 160, 89, 0.4);
        }

        @keyframes pulseGlow {
            0%, 100% { transform: scale(1); opacity: 0.4; }
            50% { transform: scale(1.18); opacity: 0.8; }
        }
        @keyframes spinRing {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Status Badge */
        .vr-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            padding: 5px 14px;
            font-size: 0.73rem;
            font-weight: 700;
            color: #166534;
            letter-spacing: 0.02em;
            margin-bottom: 16px;
            text-transform: uppercase;
        }
        .vr-pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            position: relative;
        }
        .vr-pulse-dot::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            background: rgba(34, 197, 94, 0.4);
            animation: ping 1.8s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
        @keyframes ping {
            0% { transform: scale(1); opacity: 1; }
            75%, 100% { transform: scale(2.4); opacity: 0; }
        }

        /* Headline & Paragraph */
        .vr-maint-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: clamp(1.65rem, 4.2vw, 2.15rem);
            font-weight: 700;
            color: var(--vr-forest);
            line-height: 1.25;
            margin-bottom: 12px;
            letter-spacing: -0.01em;
        }
        .vr-maint-desc {
            font-size: 0.94rem;
            line-height: 1.6;
            color: var(--vr-text-muted);
            max-width: 490px;
            margin: 0 auto 24px;
        }

        /* Marketplace Section (Amazon & Flipkart) */
        .vr-mkt-box {
            background: #fbfcf9;
            border: 1px solid rgba(38, 61, 37, 0.12);
            border-radius: 16px;
            padding: 18px 18px 16px;
            margin-bottom: 24px;
            text-align: center;
        }
        .vr-mkt-box-title {
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--vr-forest);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 5px;
        }
        .vr-mkt-box-subtitle {
            font-size: 0.81rem;
            color: var(--vr-text-muted);
            margin-bottom: 14px;
        }

        .vr-mkt-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .vr-mkt-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 12px;
            border-radius: 12px;
            text-decoration: none !important;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            min-height: 46px;
            cursor: pointer;
        }
        .vr-mkt-btn-label {
            font-size: 0.73rem;
            font-weight: 600;
            color: #475569;
            letter-spacing: -0.01em;
            line-height: 1;
        }
        .vr-mkt-brand-img {
            display: inline-block;
            vertical-align: middle;
            object-fit: contain;
            transition: transform 0.2s ease;
        }
        .vr-mkt-amazon-img {
            height: 19px;
            width: auto;
            max-width: 74px;
            margin-top: 2px;
        }
        .vr-mkt-flipkart-img {
            height: 18px;
            width: auto;
            max-width: 78px;
        }
        .vr-mkt-arrow {
            font-size: 0.72rem;
            color: #94a3b8;
            transition: all 0.2s ease;
        }

        .vr-mkt-amazon {
            border-color: #fbd38d;
        }
        .vr-mkt-amazon:hover {
            background: #fffdf5;
            border-color: #ff9900;
            box-shadow: 0 4px 14px rgba(255, 153, 0, 0.18);
            transform: translateY(-2px);
        }
        .vr-mkt-amazon:hover .vr-mkt-arrow {
            color: #ff9900;
            transform: translate(2px, -2px);
        }
        .vr-mkt-amazon:hover .vr-mkt-brand-img {
            transform: scale(1.05);
        }

        .vr-mkt-flipkart {
            border-color: #bfdbfe;
        }
        .vr-mkt-flipkart:hover {
            background: #f8faff;
            border-color: #2874f0;
            box-shadow: 0 4px 14px rgba(40, 116, 240, 0.18);
            transform: translateY(-2px);
        }
        .vr-mkt-flipkart:hover .vr-mkt-arrow {
            color: #2874f0;
            transform: translate(2px, -2px);
        }
        .vr-mkt-flipkart:hover .vr-mkt-brand-img {
            transform: scale(1.05);
        }

        /* Action Buttons: WhatsApp & Refresh */
        .vr-action-row {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }
        .vr-btn-wa {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #25D366;
            color: #ffffff !important;
            padding: 10px 18px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(37, 211, 102, 0.28);
        }
        .vr-btn-wa:hover {
            background: #20bd5a;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(37, 211, 102, 0.38);
        }
        .vr-btn-refresh {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ffffff;
            color: var(--vr-forest) !important;
            border: 1.5px solid var(--vr-border);
            padding: 10px 18px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .vr-btn-refresh:hover {
            background: #f8faf6;
            border-color: var(--vr-forest);
            transform: translateY(-2px);
        }

        /* Live status counter */
        .vr-auto-refresh {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-bottom: 8px;
        }

        /* Support Links */
        .vr-support-links {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            font-size: 0.82rem;
            color: var(--vr-text-muted);
            padding-top: 18px;
            border-top: 1px dashed var(--vr-border);
        }
        .vr-support-links a {
            color: var(--vr-forest);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .vr-support-links a:hover {
            color: var(--vr-sage);
            text-decoration: underline;
        }

        /* Footer */
        .vr-maint-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 0.74rem;
            color: #64748b;
            position: relative;
            z-index: 1;
        }
        .vr-maint-footer a {
            color: var(--vr-forest);
            text-decoration: none;
            font-weight: 600;
        }
        .vr-maint-footer a:hover {
            text-decoration: underline;
        }
        .vr-admin-portal-link {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 6px;
            color: #94a3b8 !important;
            font-size: 0.7rem;
            transition: color 0.2s;
        }
        .vr-admin-portal-link:hover {
            color: var(--vr-forest) !important;
        }

        @media (max-width: 480px) {
            body {
                padding: 16px 12px;
            }
            .vr-maint-card {
                padding: 28px 18px 22px;
                border-radius: 20px;
            }
            .vr-mkt-grid {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }
            .vr-mkt-btn {
                padding: 8px 6px;
                min-height: 42px;
                gap: 4px;
            }
            .vr-mkt-btn-label {
                font-size: 0.67rem;
            }
            .vr-mkt-amazon-img {
                max-width: 60px;
                height: 16px;
            }
            .vr-mkt-flipkart-img {
                max-width: 66px;
                height: 16px;
            }
        }
        @media (max-width: 340px) {
            .vr-mkt-btn-label {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="vr-maint-wrapper">
        <!-- Brand Header -->
        <header class="vr-maint-header">
            <img src="{{ $_logoUrl }}" alt="{{ $_brandName }}" class="vr-maint-logo" onerror="this.style.display='none'">
            <div class="vr-maint-tagline">Ayurvedic Wellness &bull; Pure by Nature</div>
        </header>

        <!-- Main Card -->
        <main class="vr-maint-card">
            <!-- Animated Herbal Emblem -->
            <div class="vr-emblem-wrap">
                <div class="vr-emblem-ring"></div>
                <div class="vr-emblem-glow"></div>
                <div class="vr-emblem-icon">
                    <i class="ri-leaf-line"></i>
                </div>
            </div>

            <!-- Status Badge -->
            <div class="vr-status-pill">
                <span class="vr-pulse-dot"></span>
                <span>Mindful Upgrade in Progress</span>
            </div>

            <!-- Title & Notice -->
            <h1 class="vr-maint-title">We're Brewing Fresh Formulations</h1>
            <p class="vr-maint-desc">
                {{ $_customMsg }}
            </p>

            <!-- Marketplace Purchase Section (Amazon & Flipkart) -->
            <div class="vr-mkt-box">
                <div class="vr-mkt-box-title">
                    <i class="ri-store-2-line"></i> Need Your Formulations Immediately?
                </div>
                <div class="vr-mkt-box-subtitle">
                    Our authentic wellness blends are available for instant order on our official marketplace channels:
                </div>
                <div class="vr-mkt-grid">
                    <!-- Amazon Button -->
                    <a href="{{ $_amazonUrl }}" target="_blank" rel="noopener noreferrer nofollow" class="vr-mkt-btn vr-mkt-amazon" title="Buy on Amazon India">
                        <span class="vr-mkt-btn-label">Buy on</span>
                        <img src="{{ asset('images/marketplaces/amazon-logo.png') }}" alt="Amazon" class="vr-mkt-brand-img vr-mkt-amazon-img" width="74" height="20" loading="lazy">
                        <i class="bi bi-arrow-up-right vr-mkt-arrow"></i>
                    </a>

                    <!-- Flipkart Button -->
                    <a href="{{ $_flipkartUrl }}" target="_blank" rel="noopener noreferrer nofollow" class="vr-mkt-btn vr-mkt-flipkart" title="Buy on Flipkart">
                        <span class="vr-mkt-btn-label">Buy on</span>
                        <img src="{{ asset('images/marketplaces/flipkart-logo.svg') }}" alt="Flipkart" class="vr-mkt-brand-img vr-mkt-flipkart-img" width="78" height="20" loading="lazy">
                        <i class="bi bi-arrow-up-right vr-mkt-arrow"></i>
                    </a>
                </div>
            </div>

            <!-- Action Buttons: WhatsApp Support & Refresh -->
            <div class="vr-action-row">
                <a href="https://wa.me/{{ $_whatsappClean }}?text={{ rawurlencode('Hello ' . $_brandName . ' Team, I am reaching out regarding my order / inquiry.') }}" target="_blank" rel="noopener noreferrer" class="vr-btn-wa">
                    <i class="bi bi-whatsapp"></i> Chat on WhatsApp
                </a>
                <button type="button" class="vr-btn-refresh" id="refreshBtn" onclick="window.location.reload();">
                    <i class="ri-refresh-line"></i> Check If Live
                </button>
            </div>

            <div class="vr-auto-refresh">
                <i class="ri-time-line"></i> Auto-checking storefront status in <span id="countdown">60</span>s
            </div>

            <!-- Support Inquiries -->
            <div class="vr-support-links">
                <span>Existing order or inquiry?</span>
                <a href="mailto:{{ $_supportEmail }}"><i class="ri-mail-line"></i> {{ $_supportEmail }}</a>
            </div>
        </main>

        <!-- Footer -->
        <footer class="vr-maint-footer">
            <div>&copy; {{ date('Y') }} {{ $_brandName }}. Handcrafted Ayurvedic Wellness.</div>
            <div>
                <a href="{{ route('admin.login') }}" class="vr-admin-portal-link" title="Store Administrator Access">
                    <i class="ri-lock-2-line"></i> Admin Login
                </a>
            </div>
        </footer>
    </div>

    <script @if($_cspNonce) nonce="{{ $_cspNonce }}" @endif>
        // Auto-refresh countdown to check if maintenance is lifted
        (function() {
            var seconds = 60;
            var countdownEl = document.getElementById('countdown');
            var timer = setInterval(function() {
                seconds--;
                if (countdownEl) {
                    countdownEl.textContent = seconds;
                }
                if (seconds <= 0) {
                    clearInterval(timer);
                    window.location.reload();
                }
            }, 1000);
        })();
    </script>
</body>
</html>
