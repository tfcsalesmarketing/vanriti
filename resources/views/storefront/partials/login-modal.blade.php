{{--
    storefront/partials/login-modal.blade.php
    ─────────────────────────────────────────────────────────────────────────────
    "Login to continue" gate modal.

    Shown the moment a guest attempts add-to-cart / wishlist. Supports BOTH
    login modes — classic password and WhatsApp OTP — plus a shortcut to the
    registration page.
--}}
<div class="modal fade" id="vrLoginModal" tabindex="-1" role="dialog" aria-labelledby="vrLoginModalTitle" aria-modal="true" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content vr-login-modal">
            <div class="vr-login-grid">
                {{-- Left Brand Panel (hidden on mobile) --}}
                <div class="vr-login-brand">
                    <div class="vr-login-brand-logo">
                        <i class="ri-leaf-line"></i>
                    </div>
                    <h3>VANRITI</h3>
                    <p>Pure, Handcrafted &amp; Natural Skincare Essentials</p>
                    <div class="vr-login-brand-leaf">
                        <i class="ri-plant-line"></i>
                    </div>
                </div>

                {{-- Right Form Panel --}}
                <div class="vr-login-form">
                    <div class="vr-login-header">
                        <div>
                            <h4 class="vr-login-heading" id="vrLoginModalTitle">Welcome Back</h4>
                            <p class="vr-login-subtitle mb-0">Sign in to save favorites, add items &amp; checkout.</p>
                        </div>
                        <button type="button" class="vr-login-close" data-bs-dismiss="modal" aria-label="Close" data-vr-login-close>
                            <i class="ri-close-line"></i>
                        </button>
                    </div>

                    {{-- Mode Switcher Tabs --}}
                    <div class="vr-login-tabs">
                        <button type="button" class="vr-login-tab active" data-vr-login-mode="password">
                            <i class="ri-lock-password-line"></i> Password
                        </button>
                        <button type="button" class="vr-login-tab" data-vr-login-mode="otp">
                            <i class="ri-whatsapp-line"></i> WhatsApp OTP
                        </button>
                    </div>

                    {{-- Password Panel --}}
                    <div class="vr-login-panel" data-vr-login-panel="password">
                        <form method="POST" action="{{ route('login.submit') }}" id="vrLoginModalForm" novalidate>
                            @csrf

                            <div class="vr-login-input-group">
                                <label for="vrLoginModalLogin">Email or Mobile Number</label>
                                <div class="vr-login-input-wrap">
                                    <input type="text" name="login" id="vrLoginModalLogin"
                                           placeholder="name@example.com or 9876543210"
                                           autocomplete="username" autofocus>
                                    <i class="ri-user-3-line vr-login-input-icon"></i>
                                </div>
                            </div>

                            <div class="vr-login-input-group">
                                <label for="vrLoginModalPassword">Password</label>
                                <div class="vr-login-input-wrap">
                                    <input type="password" name="password" id="vrLoginModalPassword"
                                           placeholder="Enter your password" autocomplete="current-password">
                                    <i class="ri-lock-2-line vr-login-input-icon"></i>
                                </div>
                            </div>

                            <div class="vr-login-remember">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="remember" id="vrLoginModalRemember" checked>
                                    <label class="form-check-label" for="vrLoginModalRemember">Remember me</label>
                                </div>
                                <a href="{{ route('password.request') }}">Forgot password?</a>
                            </div>

                            <div class="vr-login-error" data-vr-login-error role="alert">
                                <i class="ri-error-warning-line"></i>
                                <span></span>
                            </div>

                            <button type="submit" class="vr-login-submit mb-3" data-vr-login-submit>
                                <span>Sign In</span>
                                <i class="ri-arrow-right-line"></i>
                            </button>
                        </form>
                    </div>

                    {{-- WhatsApp OTP Panel --}}
                    <div class="vr-login-panel d-none" data-vr-login-panel="otp">
                        <div class="vr-login-error" data-vr-login-otp-error role="alert">
                            <i class="ri-error-warning-line"></i>
                            <span></span>
                        </div>

                        <div class="vr-login-input-group">
                            <label for="vrLoginModalOtpPhone">WhatsApp Mobile Number</label>
                            <div class="vr-login-input-wrap">
                                <input type="tel" name="phone" id="vrLoginModalOtpPhone"
                                       placeholder="98765 43210" autocomplete="tel" inputmode="tel">
                                <i class="ri-whatsapp-line vr-login-input-icon"></i>
                            </div>
                        </div>

                        <button type="button" class="vr-login-otp-send" data-vr-login-otp-send>
                            <i class="ri-send-plane-line"></i> Send WhatsApp Code
                        </button>

                        <div class="vr-login-otp-verify-wrap vr-login-otp-verify d-none">
                            <div class="vr-login-input-group mt-3">
                                <label for="vrLoginModalOtpCode">Verification Code</label>
                                <div class="vr-login-input-wrap">
                                    <input type="text" name="code" inputmode="numeric" id="vrLoginModalOtpCode"
                                           placeholder="6-digit verification code" autocomplete="one-time-code" maxlength="6">
                                    <i class="ri-key-2-line vr-login-input-icon"></i>
                                </div>
                            </div>

                            <button type="button" class="vr-login-submit" data-vr-login-otp-verify>
                                <span>Verify &amp; Sign In</span>
                                <i class="ri-arrow-right-line"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Register CTA Band --}}
                    <div class="vr-login-register-band">
                        <span>New to Vanriti?</span>
                        <a href="{{ route('register') }}" data-vr-login-register>
                            Create Account <i class="ri-arrow-right-line"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.vrOtpConfig = window.vrOtpConfig || {
    sendUrl:   '{{ route('otp.send') }}',
    verifyUrl: '{{ route('otp.verify') }}'
};
window.vrLoginModal = window.vrLoginModal || {};
window.vrLoginModal.phone = 'vrLoginModalOtpPhone';
window.vrLoginModal.code = 'vrLoginModalOtpCode';
window.vrLoginModal.sendBtn = 'vrLoginModalOtpSend';
window.vrLoginModal.verifyBtn = 'vrLoginModalOtpVerify';
window.vrLoginModal.submitBtn = 'vrLoginModalOtpVerify';
window.vrLoginModal.passwordUrl = '{{ route('login.submit') }}';
window.vrLoginModal.registerUrl = '{{ route('register') }}';
</script>

