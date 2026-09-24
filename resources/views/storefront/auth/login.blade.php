@extends('storefront.layouts.app')

@section('title', 'Login')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-auth-shell">
    <div class="vr-auth-container">
        <div class="card vr-auth-card">
            <div class="vr-auth-grid">
                {{-- ── Left Hero Panel (Desktop Showcase) ── --}}
                <div class="vr-auth-hero">
                    <div class="vr-auth-hero-brand">
                        <div class="vr-auth-hero-kicker">PURE BY NATURE</div>
                        <div class="vr-auth-hero-title">{{ strtoupper(store_name()) }}</div>
                    </div>
                    <div class="vr-auth-hero-body">
                        <ul class="vr-auth-feature-list">
                            <li class="vr-auth-feature-item">
                                <div class="vr-auth-feature-icon"><i class="ri-leaf-line"></i></div>
                                <div><strong>100% Pure &amp; Organic</strong><br><small style="opacity: 0.8">Sustainably sourced natural wellness &amp; care</small></div>
                            </li>
                            <li class="vr-auth-feature-item">
                                <div class="vr-auth-feature-icon"><i class="ri-shield-flash-line"></i></div>
                                <div><strong>Instant WhatsApp OTP</strong><br><small style="opacity: 0.8">One-click secure login without typing passwords</small></div>
                            </li>
                            <li class="vr-auth-feature-item">
                                <div class="vr-auth-feature-icon"><i class="ri-truck-line"></i></div>
                                <div><strong>Fast Express Delivery</strong><br><small style="opacity: 0.8">Track your orders live directly from your account</small></div>
                            </li>
                        </ul>
                    </div>
                    <div class="vr-auth-hero-footer">
                        <i class="ri-whatsapp-fill"></i>
                        <span>Seamless WhatsApp Authentication Active</span>
                    </div>
                </div>

                {{-- ── Right Form Panel ── --}}
                <div class="vr-auth-form-panel">
                    <div class="vr-auth-header">
                        <div class="vr-kicker mb-1">WELCOME BACK</div>
                        <div class="vr-brand mb-1">{{ strtoupper(store_name()) }}</div>
                        <p class="vr-auth-subtitle mb-0">Sign in to manage your orders &amp; account details.</p>
                    </div>

                    {{-- Standard password login form --}}
                    <div id="vrPasswordLogin">
                        <form method="POST" action="{{ route('login.submit') }}" id="vrLoginForm" novalidate>
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small fw-semibold" for="login_identifier">Email or Mobile</label>
                                <div class="vr-input-wrap">
                                    <input type="text" id="login_identifier" name="login"
                                           value="{{ old('login') }}"
                                           class="form-control {{ $errors->has('login') ? 'is-invalid' : '' }}"
                                           placeholder="e.g. user@domain.com or 9876543210"
                                           autocomplete="username" autofocus>
                                    <i class="ri-user-3-line vr-input-icon"></i>
                                </div>
                                <div class="invalid-feedback d-block mt-1 small">{{ $errors->first('login') }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold" for="login_password">Password</label>
                                <div class="password-wrap vr-input-wrap">
                                    <input type="password" id="login_password" name="password"
                                           class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                           placeholder="Enter your password"
                                           autocomplete="current-password">
                                    <i class="ri-lock-2-line vr-input-icon"></i>
                                    <button type="button" class="password-toggle-btn" tabindex="-1" aria-label="Show password">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback d-block mt-1 small">{{ $errors->first('password') }}</div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input type="checkbox" name="remember" id="remember" class="form-check-input">
                                    <label for="remember" class="form-check-label small text-muted">Remember me</label>
                                </div>
                                <a href="{{ route('password.request') }}" class="small vr-link-underline">Forgot password?</a>
                            </div>

                            <button type="submit" class="btn btn-vr w-100 py-2.5" id="vrLoginSubmitBtn">
                                Login to Account
                            </button>
                        </form>

                        {{-- ── OTP login alternative ── --}}
                        @if (app(\App\Services\WhatsAppOtpService::class)->isEnabled())
                        <div class="vr-divider">
                            <span class="vr-divider-label">or</span>
                        </div>
                        <button id="vrOtpTrigger" type="button" class="btn btn-outline-vr w-100 py-2.5">
                            <i class="ri-whatsapp-line me-1 text-success"></i> Login with WhatsApp OTP
                        </button>
                        @endif
                    </div>{{-- /#vrPasswordLogin --}}

                    {{-- ── OTP login panel — shown when user clicks "Login with OTP" ── --}}
                    @if (app(\App\Services\WhatsAppOtpService::class)->isEnabled())
                    <div id="vrOtpLoginPanel" class="d-none">
                        <div class="vr-otp-panel">
                            <div class="vr-otp-step-header text-center mb-4">
                                <div class="vr-otp-icon mb-2">
                                    <i class="ri-whatsapp-line"></i>
                                </div>
                                <div class="mb-2">
                                    <span class="vr-whatsapp-badge"><i class="ri-shield-check-fill"></i> Instant WhatsApp OTP</span>
                                </div>
                                <h6 class="fw-bold text-dark mb-1">WhatsApp Login</h6>
                                <p class="small text-muted mb-0">Enter your registered 10-digit mobile number.</p>
                            </div>

                            <div class="mb-3" id="vrOtpPhoneGroup">
                                <label class="form-label small fw-semibold" for="vrOtpPhone">Mobile Number</label>
                                <div class="vr-input-wrap">
                                    <input type="tel" id="vrOtpPhone"
                                           class="form-control"
                                           placeholder="10-digit mobile number"
                                           maxlength="10" inputmode="numeric">
                                    <i class="ri-phone-line vr-input-icon"></i>
                                </div>
                                <small id="vrOtpPhoneMsg" class="text-danger d-block mt-1"></small>
                            </div>

                            <button id="vrOtpSend" type="button" class="btn btn-vr w-100 py-2.5" disabled>
                                Send OTP Code
                            </button>

                            <div id="vrOtpVerifyStep" class="d-none mt-4 pt-3 border-top border-light">
                                <p class="small text-muted text-center mb-3">
                                    Code sent via WhatsApp to <strong id="vrOtpPhoneSummary" class="text-dark"></strong>
                                </p>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-center w-100" for="vrOtpCode">
                                        Enter 6-digit Code
                                    </label>
                                    <input type="tel" id="vrOtpCode"
                                           class="form-control vr-otp-input text-center"
                                           placeholder="— — — — — —" maxlength="6" inputmode="numeric"
                                           autocomplete="one-time-code">
                                    <small id="vrOtpCodeMsg" class="text-danger d-block mt-1 text-center"></small>
                                </div>
                                <button id="vrOtpVerify" type="button" class="btn btn-vr w-100 py-2.5">
                                    Verify &amp; Login
                                </button>
                            </div>

                            <div class="text-center mt-3">
                                <button id="vrOtpBack" type="button" class="btn btn-link btn-sm small text-muted p-0 text-decoration-none">
                                    &larr; Login with password instead
                                </button>
                            </div>
                        </div>
                    </div>{{-- /#vrOtpLoginPanel --}}
                    @endif

                    <p class="text-center small text-muted mt-4 mb-0">
                        New to {{ store_name() }}? <a href="{{ route('register') }}" class="fw-bold vr-link-underline">Create an account</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/storefront-auth.css') }}?v={{ @filemtime(public_path('css/storefront-auth.css')) ?: time() }}">
@endpush

@push('scripts')
<script src="{{ asset('js/vr-whatsapp-otp.js') }}?v={{ @filemtime(public_path('js/vr-whatsapp-otp.js')) ?: time() }}"></script>
<script>
    /* ── Route config injected once; shared by the OTP helper ── */
    window.vrOtpConfig = {
        sendUrl:   '{{ route('otp.send') }}',
        verifyUrl: '{{ route('otp.verify') }}'
    };

    document.addEventListener('DOMContentLoaded', function () {

        /* ── Live server validation on the password form ── */
        var form = document.getElementById('vrLoginForm');
        if (form && typeof window.vrLiveServerValidation === 'function') {
            window.vrLiveServerValidation(form, '{{ route('auth.validate') }}', {
                context: 'login'
            });
        }

        @if (app(\App\Services\WhatsAppOtpService::class)->isEnabled())

        /* ── DOM refs ── */
        var otp              = window.vrWhatsAppOtp;
        var passwordPanel    = document.getElementById('vrPasswordLogin');
        var otpPanel         = document.getElementById('vrOtpLoginPanel');
        var trigger          = document.getElementById('vrOtpTrigger');
        var backBtn          = document.getElementById('vrOtpBack');
        var phoneEl          = document.getElementById('vrOtpPhone');
        var phoneMsg         = document.getElementById('vrOtpPhoneMsg');
        var phoneSummary     = document.getElementById('vrOtpPhoneSummary');
        var sendBtn          = document.getElementById('vrOtpSend');
        var verifyStep       = document.getElementById('vrOtpVerifyStep');
        var codeEl           = document.getElementById('vrOtpCode');
        var codeMsg          = document.getElementById('vrOtpCodeMsg');
        var verifyBtn        = document.getElementById('vrOtpVerify');

        if (!otp || !trigger || !otpPanel) return;

        /* ── Toggle panels ── */
        function showOtpPanel() {
            passwordPanel.classList.add('d-none');
            otpPanel.classList.remove('d-none');
            otpPanel.classList.add('vr-reg-step-in');
            setTimeout(function () { otpPanel.classList.remove('vr-reg-step-in'); }, 350);
            if (phoneEl) phoneEl.focus();
        }

        function showPasswordPanel() {
            otpPanel.classList.add('d-none');
            passwordPanel.classList.remove('d-none');
            // Reset OTP panel state
            if (phoneEl)     phoneEl.value = '';
            if (codeEl)      codeEl.value = '';
            if (phoneMsg)    phoneMsg.textContent = '';
            if (codeMsg)     codeMsg.textContent = '';
            if (verifyStep)  verifyStep.classList.add('d-none');
            if (sendBtn)     { sendBtn.disabled = true; sendBtn.textContent = 'Send OTP Code'; }
        }

        trigger.addEventListener('click', showOtpPanel);
        if (backBtn) backBtn.addEventListener('click', showPasswordPanel);

        /* ── Enable "Send OTP" only when 10 digits are entered ── */
        if (phoneEl) {
            phoneEl.addEventListener('input', function () {
                var digits = phoneEl.value.replace(/\D/g, '');
                sendBtn.disabled = digits.length < 10;
                if (phoneMsg) phoneMsg.textContent = '';
            });
        }

        /* ── Send OTP ── */
        if (sendBtn) {
            sendBtn.addEventListener('click', function () {
                phoneMsg.textContent = '';
                codeMsg.textContent  = '';

                otp.send({
                    phoneEl : phoneEl,
                    btn     : sendBtn,
                    purpose : 'login',
                    onSent  : function () {
                        // Show summary and reveal verify step
                        var digits = phoneEl.value.replace(/\D/g, '');
                        if (phoneSummary) {
                            phoneSummary.textContent = digits.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
                        }
                        if (verifyStep) {
                            verifyStep.classList.remove('d-none');
                        }
                        if (codeEl) codeEl.focus();
                        // Lock the phone field so user can't change it after send
                        if (phoneEl) phoneEl.readOnly = true;
                    },
                    onError : function (msg) {
                        if (phoneMsg) phoneMsg.textContent = msg;
                    }
                });
            });
        }

        /* ── Verify OTP → logged in ── */
        if (verifyBtn) {
            verifyBtn.addEventListener('click', function () {
                codeMsg.textContent = '';
                otp.verify({
                    phoneEl   : phoneEl,
                    codeEl    : codeEl,
                    btn       : verifyBtn,
                    purpose   : 'login',
                    onVerified: function (data) {
                        if (data && data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            window.location.href = '{{ route('account.dashboard') }}';
                        }
                    },
                    onError: function (msg) {
                        if (codeMsg) codeMsg.textContent = msg;
                    }
                });
            });
        }

        /* ── OTP input: clear error on edit ── */
        if (codeEl) {
            codeEl.addEventListener('input', function () {
                if (codeMsg) codeMsg.textContent = '';
            });
        }

        @endif

    });
</script>
@endpush