@extends('storefront.layouts.app')

@section('title', 'Create Account')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-auth-shell">
    <div class="vr-auth-container">
        <div class="card vr-auth-card">
            <div class="vr-auth-grid">
                {{-- ── Left Hero Panel (Desktop Showcase) ── --}}
                <div class="vr-auth-hero">
                    <div class="vr-auth-hero-brand">
                        <div class="vr-auth-hero-kicker">JOIN OUR COMMUNITY</div>
                        <div class="vr-auth-hero-title">{{ strtoupper(store_name()) }}</div>
                    </div>
                    <div class="vr-auth-hero-body">
                        <ul class="vr-auth-feature-list">
                            <li class="vr-auth-feature-item">
                                <div class="vr-auth-feature-icon"><i class="ri-gift-line"></i></div>
                                <div><strong>Exclusive Member Perks</strong><br><small style="opacity: 0.8">Earn reward points &amp; early access to sales</small></div>
                            </li>
                            <li class="vr-auth-feature-item">
                                <div class="vr-auth-feature-icon"><i class="ri-flashlight-line"></i></div>
                                <div><strong>Faster Checkout</strong><br><small style="opacity: 0.8">Saved shipping addresses &amp; instant tracking</small></div>
                            </li>
                            <li class="vr-auth-feature-item">
                                <div class="vr-auth-feature-icon"><i class="ri-shield-check-line"></i></div>
                                <div><strong>Verified Security</strong><br><small style="opacity: 0.8">Instant mobile verification via WhatsApp OTP</small></div>
                            </li>
                        </ul>
                    </div>
                    <div class="vr-auth-hero-footer">
                        <i class="ri-shield-user-fill"></i>
                        <span>Protected by 256-Bit SSL Encryption</span>
                    </div>
                </div>

                {{-- ── Right Form Panel ── --}}
                <div class="vr-auth-form-panel">
                    <div class="vr-auth-header mb-3">
                        <div class="vr-kicker mb-1">GET STARTED</div>
                        <div class="vr-brand mb-1">{{ strtoupper(store_name()) }}</div>
                        <p class="vr-auth-subtitle mb-0">Create your account for faster checkouts &amp; rewards.</p>
                    </div>

                    {{-- ── Step Progress Indicator ── --}}
                    @if (app(\App\Services\WhatsAppOtpService::class)->isEnabled())
                    <div class="vr-step-indicator">
                        <div class="vr-step-item active" id="vrStep1Node">
                            <div class="vr-step-num">1</div>
                            <span class="vr-step-label">Details</span>
                        </div>
                        <div class="vr-step-connector" id="vrStepConnector"></div>
                        <div class="vr-step-item" id="vrStep2Node">
                            <div class="vr-step-num">2</div>
                            <span class="vr-step-label">Verification</span>
                        </div>
                    </div>
                    @endif

                    <form method="POST" action="{{ route('register.submit') }}" id="vrRegisterForm" novalidate>
                        @csrf

                        {{-- ════════════════════════════════════════════════════════════
                             STEP 1 — Registration fields
                        ═══════════════════════════════════════════════════════════════ --}}
                        <div id="vrRegFields">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold" for="reg_name">Full Name <span class="text-danger">*</span></label>
                                <div class="vr-input-wrap">
                                    <input type="text" id="reg_name" name="name" value="{{ old('name') }}"
                                           class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                           placeholder="e.g. John Doe"
                                           autocomplete="name" autofocus>
                                    <i class="ri-user-line vr-input-icon"></i>
                                </div>
                                <div class="invalid-feedback d-block mt-1 small">{{ $errors->first('name') }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold" for="reg_phone">Mobile Number <span class="text-danger">*</span></label>
                                <div class="vr-input-wrap">
                                    <input type="tel" id="reg_phone" name="phone" value="{{ old('phone') }}"
                                           class="form-control {{ $errors->has('phone') ? 'is-invalid' : '' }}"
                                           autocomplete="tel" inputmode="numeric" maxlength="10"
                                           placeholder="10-digit mobile number">
                                    <i class="ri-phone-line vr-input-icon"></i>
                                </div>
                                <div class="invalid-feedback d-block mt-1 small">{{ $errors->first('phone') }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold" for="reg_email">
                                    Email Address <span class="fw-normal text-muted">(optional)</span>
                                </label>
                                <div class="vr-input-wrap">
                                    <input type="email" id="reg_email" name="email" value="{{ old('email') }}"
                                           class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                                           placeholder="e.g. john@example.com"
                                           autocomplete="email">
                                    <i class="ri-mail-line vr-input-icon"></i>
                                </div>
                                <div class="invalid-feedback d-block mt-1 small">{{ $errors->first('email') }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold" for="reg_password">Password <span class="text-danger">*</span></label>
                                <div class="password-wrap vr-input-wrap">
                                    <input type="password" id="reg_password" name="password"
                                           class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                           placeholder="Min 8 characters"
                                           autocomplete="new-password">
                                    <i class="ri-lock-2-line vr-input-icon"></i>
                                    <button type="button" class="password-toggle-btn" tabindex="-1" aria-label="Show password">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback d-block mt-1 small">{{ $errors->first('password') }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-semibold" for="reg_password_confirmation">Confirm Password <span class="text-danger">*</span></label>
                                <div class="password-wrap vr-input-wrap">
                                    <input type="password" id="reg_password_confirmation" name="password_confirmation"
                                           class="form-control {{ $errors->has('password_confirmation') ? 'is-invalid' : '' }}"
                                           placeholder="Repeat password"
                                           autocomplete="new-password">
                                    <i class="ri-lock-check-line vr-input-icon"></i>
                                    <button type="button" class="password-toggle-btn" tabindex="-1" aria-label="Show password">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback d-block mt-1 small">{{ $errors->first('password_confirmation') }}</div>
                            </div>

                            <button type="button" id="vrRegProceedBtn" class="btn btn-vr w-100 py-2.5">
                                Continue &rarr;
                            </button>

                            {{-- Server-side validation errors --}}
                            @if ($errors->any())
                                <div class="alert alert-danger small mt-3 mb-0 py-2 rounded-3">
                                    <ul class="mb-0 ps-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>{{-- /#vrRegFields --}}

                        {{-- ════════════════════════════════════════════════════════
                             STEP 2 — OTP verification
                        ════════════════════════════════════════════════════════════ --}}
                        <div id="vrRegOtpStep" class="d-none">
                            <div class="vr-otp-panel">
                                <div class="vr-otp-step-header text-center mb-4">
                                    <div class="vr-otp-icon mb-2">
                                        <i class="ri-whatsapp-line"></i>
                                    </div>
                                    <div class="mb-2">
                                        <span class="vr-whatsapp-badge"><i class="ri-shield-check-fill"></i> WhatsApp Verification</span>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">Verify Mobile Number</h6>
                                    <p class="small text-muted mb-0">
                                        A 6-digit code has been sent via WhatsApp to<br>
                                        <strong id="vrRegPhoneSummary" class="text-dark"></strong>
                                    </p>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-center w-100" for="vrRegOtpCode">
                                        Enter 6-digit Verification Code
                                    </label>
                                    <input type="tel" id="vrRegOtpCode" name="otp_code"
                                           class="form-control vr-otp-input text-center"
                                           placeholder="— — — — — —" maxlength="6" inputmode="numeric"
                                           autocomplete="one-time-code">
                                    <small id="vrRegOtpCodeMsg" class="text-danger d-block mt-1 text-center"></small>
                                </div>

                                <button id="vrRegOtpVerify" type="button" class="btn btn-vr w-100 py-2.5 mb-3">
                                    Verify &amp; Create Account
                                </button>

                                <div class="text-center mb-2">
                                    <span class="small text-muted">Didn't receive code?&nbsp;</span>
                                    <button id="vrRegOtpResend" type="button"
                                            class="btn btn-link btn-sm p-0 small fw-bold vr-link-underline"
                                            disabled>
                                        Resend OTP
                                    </button>
                                </div>

                                <div class="text-center mt-3">
                                    <button id="vrRegBack" type="button" class="btn btn-link btn-sm small text-muted p-0 text-decoration-none">
                                        &larr; Edit my details
                                    </button>
                                </div>
                            </div>
                        </div>{{-- /#vrRegOtpStep --}}

                    </form>

                    <p class="text-center small text-muted mt-4 mb-0">
                        Already registered? <a href="{{ route('login') }}" class="fw-bold vr-link-underline">Login here</a>
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

        /* ── DOM refs ── */
        var form          = document.getElementById('vrRegisterForm');
        var fieldsStep    = document.getElementById('vrRegFields');
        var otpStep       = document.getElementById('vrRegOtpStep');
        var proceedBtn    = document.getElementById('vrRegProceedBtn');
        var phoneInput    = document.getElementById('reg_phone');
        var phoneSummary  = document.getElementById('vrRegPhoneSummary');
        var otpCode       = document.getElementById('vrRegOtpCode');
        var otpCodeMsg    = document.getElementById('vrRegOtpCodeMsg');
        var verifyBtn     = document.getElementById('vrRegOtpVerify');
        var resendBtn     = document.getElementById('vrRegOtpResend');
        var backBtn       = document.getElementById('vrRegBack');
        var step1Node     = document.getElementById('vrStep1Node');
        var step2Node     = document.getElementById('vrStep2Node');
        var stepConnector = document.getElementById('vrStepConnector');
        var otp           = window.vrWhatsAppOtp;

        /* ── Live server validation ── */
        if (form && typeof window.vrLiveServerValidation === 'function') {
            window.vrLiveServerValidation(form, '{{ route('auth.validate') }}', {
                context: 'register'
            });
        }

        /* ── Helper: basic client-side pre-check before OTP dispatch ── */
        function validateFieldsLocally() {
            var name    = (document.getElementById('reg_name').value || '').trim();
            var phone   = (phoneInput.value || '').replace(/\D/g, '');
            var pass    = (document.getElementById('reg_password').value || '');
            var confirm = (document.getElementById('reg_password_confirmation').value || '');

            if (!name) { showFieldError('reg_name', 'Please enter your full name.'); return false; }
            if (phone.length < 10) { showFieldError('reg_phone', 'Please enter a valid 10-digit mobile number.'); return false; }
            if (pass.length < 8) { showFieldError('reg_password', 'Password must be at least 8 characters.'); return false; }
            if (pass !== confirm) { showFieldError('reg_password_confirmation', 'Passwords do not match.'); return false; }
            return true;
        }

        function showFieldError(fieldId, msg) {
            var el = document.getElementById(fieldId);
            var wrap = el ? el.closest('.mb-3, .mb-4') : null;
            var div = wrap ? wrap.querySelector('.invalid-feedback') : null;
            if (el) el.classList.add('is-invalid');
            if (div) div.textContent = msg;
            if (el) el.focus();
        }

        /* Clear inline errors when user edits a field */
        ['reg_name', 'reg_phone', 'reg_email', 'reg_password', 'reg_password_confirmation'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('input', function () {
                el.classList.remove('is-invalid');
            });
        });

        /* ── Step transition helpers ── */
        function showOtpStep(phone) {
            phoneSummary.textContent = phone.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
            
            // Update step progress indicator
            if (step1Node) { step1Node.classList.remove('active'); step1Node.classList.add('completed'); }
            if (stepConnector) { stepConnector.classList.add('active'); }
            if (step2Node) { step2Node.classList.add('active'); }

            fieldsStep.classList.add('vr-reg-step-out');
            setTimeout(function () {
                fieldsStep.classList.add('d-none');
                fieldsStep.classList.remove('vr-reg-step-out');
                otpStep.classList.remove('d-none');
                otpStep.classList.add('vr-reg-step-in');
                setTimeout(function () { otpStep.classList.remove('vr-reg-step-in'); }, 350);
                if (otpCode) otpCode.focus();
            }, 200);
        }

        function showFieldsStep() {
            otpStep.classList.add('d-none');
            otpCodeMsg.textContent = '';
            if (otpCode) otpCode.value = '';

            // Revert step progress indicator
            if (step1Node) { step1Node.classList.add('active'); step1Node.classList.remove('completed'); }
            if (stepConnector) { stepConnector.classList.remove('active'); }
            if (step2Node) { step2Node.classList.remove('active'); }

            fieldsStep.classList.remove('d-none');
            if (phoneInput) phoneInput.focus();
        }

        /* ── "Continue" button: validate → send OTP → reveal step 2 ── */
        if (proceedBtn) {
            proceedBtn.addEventListener('click', function () {
                if (!validateFieldsLocally()) return;

                @if (app(\App\Services\WhatsAppOtpService::class)->isEnabled())
                    sendOtp();
                @else
                    form.submit();
                @endif
            });
        }

        function sendOtp() {
            if (!otp) { form.submit(); return; }

            otp.send({
                phoneEl : phoneInput,
                btn     : proceedBtn,
                purpose : 'register',
                onSent  : function () {
                    showOtpStep(phoneInput.value.replace(/\D/g, ''));
                    startResendCooldown();
                },
                onError : function (msg) {
                    showFieldError('reg_phone', msg);
                }
            });
        }

        /* ── Resend button cooldown ── */
        function startResendCooldown() {
            if (!resendBtn) return;
            var remaining = 120;
            resendBtn.disabled = true;
            resendBtn.textContent = 'Resend in ' + remaining + 's';
            var t = setInterval(function () {
                remaining -= 1;
                if (remaining <= 0) {
                    clearInterval(t);
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'Resend OTP';
                } else {
                    resendBtn.textContent = 'Resend in ' + remaining + 's';
                }
            }, 1000);
        }

        if (resendBtn) {
            resendBtn.addEventListener('click', function () {
                if (!otp) return;
                otpCodeMsg.textContent = '';
                if (otpCode) otpCode.value = '';
                otp.send({
                    phoneEl : phoneInput,
                    btn     : proceedBtn,
                    purpose : 'register',
                    onSent  : function () {
                        startResendCooldown();
                        otpCodeMsg.textContent = '';
                    },
                    onError : function (msg) {
                        otpCodeMsg.textContent = msg;
                    }
                });
            });
        }

        /* ── "Edit my details" back link ── */
        if (backBtn) {
            backBtn.addEventListener('click', showFieldsStep);
        }

        /* ── Verify OTP → submit the form ── */
        if (verifyBtn) {
            verifyBtn.addEventListener('click', function () {
                otpCodeMsg.textContent = '';
                if (!otp) return;
                otp.verify({
                    phoneEl   : phoneInput,
                    codeEl    : otpCode,
                    btn       : verifyBtn,
                    purpose   : 'register',
                    onVerified: function () {
                        form.submit();
                    },
                    onError: function (msg) {
                        otpCodeMsg.textContent = msg;
                    }
                });
            });
        }

        /* ── OTP input: auto-clear error on edit ── */
        if (otpCode) {
            otpCode.addEventListener('input', function () {
                otpCodeMsg.textContent = '';
            });
        }

        @if (!app(\App\Services\WhatsAppOtpService::class)->isEnabled())
        if (form) {
            form.addEventListener('submit', function (e) {
                if (!validateFieldsLocally()) { e.preventDefault(); }
            });
        }
        @endif

    });
</script>
@endpush