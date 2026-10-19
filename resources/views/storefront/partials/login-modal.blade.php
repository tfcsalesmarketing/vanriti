{{--
    storefront/partials/login-modal.blade.php
    ─────────────────────────────────────────────────────────────────────────────
    Luxury Vanriti Login & Fast Checkout Modal
    Dual-mode: Instant WhatsApp OTP (default) + Traditional Password
--}}
@php
    $loginLogo = setting('store_logo');
    if (! $loginLogo) {
        foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
            if (file_exists(public_path('images/logo.' . $ext))) {
                $loginLogo = 'images/logo.' . $ext;
                break;
            }
        }
    }
@endphp

<div class="modal fade vr-modal-backdrop" id="vrLoginModal" tabindex="-1" role="dialog" aria-labelledby="vrLoginModalTitle" aria-modal="true" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content vr-login-dialog">

            {{-- Close Button --}}
            <button type="button" class="vr-modal-close-btn" data-bs-dismiss="modal" aria-label="Close" data-vr-login-close>
                <i class="ri-close-line"></i>
            </button>

            <div class="vr-modal-body-split">

                {{-- Left Brand Showcase (Desktop only >= 768px) --}}
                <div class="vr-modal-brand-col">
                    {{-- Decorative Botanical Leaf --}}
                    <div class="vr-brand-leaf-art" aria-hidden="true">
                        <svg viewBox="0 0 200 240" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M100 10 C50 60 20 120 20 170 C20 210 50 230 100 230 C150 230 180 210 180 170 C180 120 150 60 100 10 Z"
                                  fill="rgba(111,135,54,0.12)" stroke="rgba(181,138,58,0.3)" stroke-width="1.2"/>
                            <path d="M100 25 V220" stroke="rgba(181,138,58,0.25)" stroke-width="1.2"/>
                            <path d="M100 70 C75 95 45 115 35 125" stroke="rgba(181,138,58,0.2)" stroke-width="1"/>
                            <path d="M100 95 C125 120 155 140 165 150" stroke="rgba(181,138,58,0.2)" stroke-width="1"/>
                            <path d="M100 130 C75 155 50 175 40 185" stroke="rgba(181,138,58,0.2)" stroke-width="1"/>
                            <path d="M100 155 C125 180 150 195 160 205" stroke="rgba(181,138,58,0.2)" stroke-width="1"/>
                        </svg>
                    </div>

                    <div class="vr-brand-content-wrap">
                        <div class="vr-brand-badge-circle">
                            @if ($loginLogo)
                                <img src="{{ image_url($loginLogo, 'favicon.ico') }}" alt="{{ store_name() }}" class="vr-brand-logo-img">
                            @else
                                <i class="ri-leaf-line vr-brand-fallback-icon"></i>
                            @endif
                        </div>

                        <div class="vr-brand-name">{{ strtoupper(store_name()) }}</div>
                        <div class="vr-brand-tagline">{{ setting('store_tagline', 'Pure by Nature • Botanical Wellness') }}</div>

                        <div class="vr-brand-perks-list">
                            <div class="vr-perk-item">
                                <div class="vr-perk-icon-circle"><i class="ri-whatsapp-fill"></i></div>
                                <div class="vr-perk-text">
                                    <strong>Instant 1-Click Login</strong>
                                    <span>Code sent direct to WhatsApp</span>
                                </div>
                            </div>
                            <div class="vr-perk-item">
                                <div class="vr-perk-icon-circle"><i class="ri-shield-check-fill"></i></div>
                                <div class="vr-perk-text">
                                    <strong>100% Safe &amp; Verified</strong>
                                    <span>Encrypted customer checkout</span>
                                </div>
                            </div>
                            <div class="vr-perk-item">
                                <div class="vr-perk-icon-circle"><i class="ri-magic-line"></i></div>
                                <div class="vr-perk-text">
                                    <strong>Zero Password Required</strong>
                                    <span>Auto-created account for new users</span>
                                </div>
                            </div>
                        </div>

                        <div class="vr-brand-trust-pill">
                            <i class="ri-check-double-line"></i> Authentic Ayurvedic Care
                        </div>
                    </div>
                </div>

                {{-- Right Interaction / Form Column --}}
                <div class="vr-modal-form-col">
                    {{-- Header --}}
                    <div class="vr-form-headline-wrap">
                        <div class="vr-form-kicker">
                            <span class="vr-kicker-sparkle">✨</span> FAST SIGN-IN
                        </div>
                        <h4 class="vr-form-heading" id="vrLoginModalTitle">Welcome to {{ store_name() }}</h4>
                        <p class="vr-login-heading" id="vrLoginModalSubtitle">Quick WhatsApp verification or standard account login</p>
                    </div>

                    {{-- Mode Toggle Tabs --}}
                    <div class="vr-tab-selector" role="tablist">
                        <button type="button" class="vr-tab-btn active" data-vr-login-mode="otp" role="tab" aria-selected="true">
                            <i class="ri-whatsapp-line"></i>
                            <span>WhatsApp OTP</span>
                        </button>
                        <button type="button" class="vr-tab-btn" data-vr-login-mode="password" role="tab" aria-selected="false">
                            <i class="ri-lock-line"></i>
                            <span>Password</span>
                        </button>
                    </div>

                    {{-- ── PANEL 1: WhatsApp OTP (Default & Recommended) ── --}}
                    <div class="vr-form-panel" data-vr-login-panel="otp">
                        {{-- Error Alert --}}
                        <div class="vr-modal-alert d-none" data-vr-login-otp-error role="alert">
                            <i class="ri-error-warning-fill"></i>
                            <span class="vr-alert-msg"></span>
                        </div>

                        {{-- Step 1: Phone input view --}}
                        <div class="vr-step-container" data-vr-otp-step="phone">
                            <div class="vr-input-block">
                                <label class="vr-label" for="vrLoginModalOtpPhone">
                                    <span>WhatsApp Mobile Number</span>
                                    <span class="vr-label-badge"><i class="ri-whatsapp-fill text-success"></i> India (+91)</span>
                                </label>
                                <div class="vr-phone-field">
                                    <div class="vr-prefix-tag">
                                        <span class="vr-flag-emoji">🇮🇳</span>
                                        <span class="vr-prefix-num">+91</span>
                                    </div>
                                    <input type="tel" name="phone" id="vrLoginModalOtpPhone"
                                           class="vr-phone-control"
                                           placeholder="10-digit mobile number"
                                           autocomplete="tel"
                                           inputmode="numeric"
                                           maxlength="10"
                                           autofocus>
                                    <div class="vr-field-trailing-icon">
                                        <i class="ri-whatsapp-line"></i>
                                    </div>
                                </div>
                                <div class="vr-input-note">
                                    <i class="ri-shield-keyhole-line"></i> We will send an instant 6-digit verification code to WhatsApp.
                                </div>
                            </div>

                            <button type="button" class="vr-modal-btn vr-btn-primary" data-vr-login-otp-send>
                                <span class="vr-btn-label">
                                    <i class="ri-send-plane-2-fill"></i>
                                    <span>Send WhatsApp Code</span>
                                </span>
                            </button>

                            <div class="vr-auto-signup-banner">
                                <div class="vr-auto-signup-icon">
                                    <i class="ri-sparkling-fill"></i>
                                </div>
                                <div class="vr-auto-signup-text">
                                    <strong>New to {{ store_name() }}?</strong> No registration needed. Your account will be created automatically upon verification!
                                </div>
                            </div>
                        </div>

                        {{-- Step 2: OTP Code input view (Revealed after code sent) --}}
                        <div class="vr-step-container d-none vr-login-otp-verify-wrap" data-vr-otp-step="code">
                            <div class="vr-phone-confirmed-card">
                                <div class="vr-confirmed-icon">
                                    <i class="ri-whatsapp-fill"></i>
                                </div>
                                <div class="vr-confirmed-info">
                                    <div class="vr-confirmed-label">OTP Sent to WhatsApp</div>
                                    <div class="vr-confirmed-phone" id="vrConfirmedPhoneDisplay">+91 ••••••••••</div>
                                </div>
                                <button type="button" class="vr-change-phone-link" id="vrChangePhoneBtn" title="Change phone number">
                                    <i class="ri-edit-line"></i> Change
                                </button>
                            </div>

                            <div class="vr-input-block">
                                <label class="vr-label text-center d-block" for="vrLoginModalOtpCode">
                                    Enter 6-Digit Verification Code
                                </label>
                                <div class="vr-otp-code-box">
                                    <input type="tel" name="code" id="vrLoginModalOtpCode"
                                           class="vr-otp-input-control"
                                           placeholder="••••••"
                                           maxlength="6"
                                           inputmode="numeric"
                                           autocomplete="one-time-code">
                                </div>
                            </div>

                            <button type="button" class="vr-modal-btn vr-btn-primary" data-vr-login-otp-verify>
                                <span class="vr-btn-label">
                                    <span>Verify &amp; Continue</span>
                                    <i class="ri-arrow-right-line"></i>
                                </span>
                            </button>

                            <div class="vr-resend-row">
                                <span class="vr-resend-prompt">Didn't get the code?</span>
                                <button type="button" class="vr-resend-action" id="vrResendOtpActionBtn">
                                    <i class="ri-refresh-line"></i> Resend on WhatsApp
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- ── PANEL 2: Password Login ── --}}
                    <div class="vr-form-panel d-none" data-vr-login-panel="password">
                        <form method="POST" action="{{ route('login.submit') }}" id="vrLoginModalForm" novalidate>
                            @csrf
                            <div class="vr-modal-alert d-none" data-vr-login-error role="alert">
                                <i class="ri-error-warning-fill"></i>
                                <span class="vr-alert-msg"></span>
                            </div>

                            <div class="vr-input-block">
                                <label class="vr-label" for="vrLoginModalLogin">Email or Mobile Number</label>
                                <div class="vr-standard-field">
                                    <i class="ri-user-3-line vr-field-leading-icon"></i>
                                    <input type="text" name="login" id="vrLoginModalLogin"
                                           class="vr-standard-control"
                                           placeholder="e.g. name@domain.com or 9876543210"
                                           autocomplete="username">
                                </div>
                            </div>

                            <div class="vr-input-block">
                                <label class="vr-label" for="vrLoginModalPassword">Password</label>
                                <div class="vr-standard-field">
                                    <i class="ri-lock-2-line vr-field-leading-icon"></i>
                                    <input type="password" name="password" id="vrLoginModalPassword"
                                           class="vr-standard-control"
                                           placeholder="Enter your password"
                                           autocomplete="current-password">
                                    <button type="button" class="vr-toggle-eye-btn" data-vr-login-eye aria-label="Show password">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="vr-options-row">
                                <label class="vr-checkbox-wrap">
                                    <input type="checkbox" class="vr-checkbox" name="remember" id="vrLoginModalRemember" checked>
                                    <span class="vr-checkbox-text">Remember me</span>
                                </label>
                                <a href="{{ route('password.request') }}" class="vr-forgot-pass-link">Forgot password?</a>
                            </div>

                            <button type="submit" class="vr-modal-btn vr-btn-primary" data-vr-login-submit>
                                <span class="vr-btn-label">
                                    <span>Sign In</span>
                                    <i class="ri-arrow-right-line"></i>
                                </span>
                            </button>
                        </form>
                    </div>

                    {{-- Footer: Full registration link --}}
                    <div class="vr-modal-footer-cta">
                        <span>Prefer standard registration?</span>
                        <a href="{{ route('register') }}" data-vr-login-register>
                            Create an account <i class="ri-arrow-right-s-line"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}">
window.vrOtpConfig = window.vrOtpConfig || {
    sendUrl:   '{{ route('otp.send') }}',
    verifyUrl: '{{ route('otp.verify') }}'
};
window.vrLoginModal = window.vrLoginModal || {};
window.vrLoginModal.phone = 'vrLoginModalOtpPhone';
window.vrLoginModal.code = 'vrLoginModalOtpCode';
window.vrLoginModal.sendBtn = 'vrLoginModalOtpSend';
window.vrLoginModal.verifyBtn = 'vrLoginModalOtpVerify';
window.vrLoginModal.passwordUrl = '{{ route('login.submit') }}';
window.vrLoginModal.registerUrl = '{{ route('register') }}';
</script>
