@extends('tfc.layouts.app')

@section('title', 'Verify Mobile Number | TFC Sales & Marketing')
@section('meta_description', 'Verify your WhatsApp mobile number to complete your TFC Direct Selling Member account setup.')

@section('content')
<div class="py-5 bg-light" style="min-height: 85vh;">
    <div class="container py-3">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">

                {{-- Step Progress Indicator --}}
                <div class="d-flex align-items-center justify-content-between mb-4 px-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px; background: #0A1D37; font-size: 0.85rem;">
                            1
                        </div>
                        <div>
                            <div class="fw-bold small text-dark">Verify Mobile</div>
                            <div class="text-muted" style="font-size: 0.72rem;">Enter WhatsApp OTP</div>
                        </div>
                    </div>
                    <div class="flex-grow-1 mx-3 border-top border-2 border-dashed" style="border-color: #cbd5e1 !important;"></div>
                    <div class="d-flex align-items-center gap-2 opacity-50">
                        <div class="rounded-circle bg-white text-muted border fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.85rem;">
                            2
                        </div>
                        <div>
                            <div class="fw-bold small text-muted">Set Password</div>
                            <div class="text-muted" style="font-size: 0.72rem;">Secure Your Account</div>
                        </div>
                    </div>
                </div>

                {{-- Main Verification Card --}}
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header text-center bg-white py-4 px-4 border-bottom">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-3 mb-2 shadow-sm" style="background: rgba(37, 211, 102, 0.12); color: #25D366; width: 64px; height: 64px;">
                            <i class="bi bi-whatsapp fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-1 brand-font text-dark">Verify Your WhatsApp Number</h4>
                        <div class="text-muted small">We've sent a 6-digit verification code to your WhatsApp</div>
                    </div>

                    <div class="card-body p-4 p-md-4">
                        {{-- Flash Alerts --}}
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show d-flex align-items-start gap-2 small mb-3 py-2.5" role="alert">
                                <i class="bi bi-check-circle-fill text-success fs-6 mt-0.5"></i>
                                <div class="flex-grow-1">{{ session('success') }}</div>
                                <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('warning'))
                            <div class="alert alert-warning alert-dismissible fade show d-flex align-items-start gap-2 small mb-3 py-2.5" role="alert">
                                <i class="bi bi-exclamation-triangle-fill text-warning fs-6 mt-0.5"></i>
                                <div class="flex-grow-1">{{ session('warning') }}</div>
                                <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2 small mb-3 py-2.5" role="alert">
                                <i class="bi bi-exclamation-octagon-fill text-danger fs-6 mt-0.5"></i>
                                <div class="flex-grow-1">{{ session('error') }}</div>
                                <button type="button" class="btn-close py-2.5" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        {{-- Member Info Card --}}
                        <div class="p-3 mb-4 rounded-3 border d-flex align-items-center justify-content-between" style="background: #F8FAFC;">
                            <div>
                                <div class="text-muted small" style="font-size: 0.75rem;">DIRECT SELLER</div>
                                <div class="fw-bold text-dark">{{ $member->full_name }}</div>
                                <div class="text-muted small">
                                    <i class="bi bi-whatsapp text-success me-1"></i> {{ $maskedMobile }}
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted small" style="font-size: 0.75rem;">MEMBER ID</div>
                                <span class="badge font-monospace px-2.5 py-1.5" style="background: #0A1D37; color: #D49B24; font-size: 0.85rem;">
                                    {{ $member->tfc_member_id }}
                                </span>
                            </div>
                        </div>

                        {{-- OTP Verification Form --}}
                        <form method="POST" action="{{ function_exists('tfc_route') ? tfc_route('member.verify-mobile.submit') : route('tfc.preview.member.verify-mobile.submit') }}" id="otpForm" novalidate>
                            @csrf
                            <input type="hidden" name="token" id="verificationToken" value="{{ $token }}">
                            <input type="hidden" name="otp" id="fullOtpInput" value="">

                            <div class="mb-4 text-center">
                                <label class="form-label fw-bold text-dark small mb-3">
                                    Enter 6-Digit Verification Code
                                </label>
                                
                                {{-- 6 Digit Inputs --}}
                                <div class="d-flex justify-content-center gap-2 mb-2" id="otpDigitContainer">
                                    <input type="text" class="form-control text-center fw-bold fs-4 otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]*" style="width: 48px; height: 56px; border-radius: 10px;" autocomplete="one-time-code" data-index="0" autofocus>
                                    <input type="text" class="form-control text-center fw-bold fs-4 otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]*" style="width: 48px; height: 56px; border-radius: 10px;" autocomplete="off" data-index="1">
                                    <input type="text" class="form-control text-center fw-bold fs-4 otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]*" style="width: 48px; height: 56px; border-radius: 10px;" autocomplete="off" data-index="2">
                                    <input type="text" class="form-control text-center fw-bold fs-4 otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]*" style="width: 48px; height: 56px; border-radius: 10px;" autocomplete="off" data-index="3">
                                    <input type="text" class="form-control text-center fw-bold fs-4 otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]*" style="width: 48px; height: 56px; border-radius: 10px;" autocomplete="off" data-index="4">
                                    <input type="text" class="form-control text-center fw-bold fs-4 otp-digit" maxlength="1" inputmode="numeric" pattern="[0-9]*" style="width: 48px; height: 56px; border-radius: 10px;" autocomplete="off" data-index="5">
                                </div>
                                <div id="otpValidationMsg" class="text-danger small mt-1 d-none">Please enter all 6 digits.</div>
                            </div>

                            <button type="submit" id="btnVerifyOtp" class="btn btn-tfc-primary w-100 py-2.5 fw-bold shadow-sm mb-3">
                                <i class="bi bi-shield-check me-1.5"></i> Verify &amp; Set Password
                            </button>
                        </form>

                        {{-- Resend OTP Section with Timer --}}
                        <div class="text-center pt-2 border-top">
                            <div class="small text-muted mb-1">Didn't receive the WhatsApp message?</div>
                            <div id="resendCountdownBox" class="small text-muted">
                                Resend code in <span id="resendTimerSeconds" class="fw-bold text-dark font-monospace">45</span>s
                            </div>
                            <button type="button" id="btnResendOtp" class="btn btn-link btn-sm text-decoration-none fw-semibold text-primary d-none p-0">
                                <i class="bi bi-arrow-clockwise me-1"></i> Resend Verification Code via WhatsApp
                            </button>
                            <div id="resendStatusAlert" class="small mt-2 d-none"></div>
                        </div>

                    </div>

                    <div class="card-footer bg-light py-3 px-4 border-top text-center">
                        <div class="small text-muted d-flex align-items-center justify-content-center gap-1.5">
                            <i class="bi bi-lock-fill text-secondary"></i>
                            Your information is encrypted with bank-grade 256-bit SSL security.
                        </div>
                    </div>
                </div>

                {{-- Help link --}}
                <div class="text-center mt-3 small text-muted">
                    Need help? <a href="{{ function_exists('tfc_route') ? tfc_route('member.login') : route('tfc.preview.member.login') }}" class="text-decoration-none">Return to Portal Login</a>
                </div>

            </div>
        </div>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
document.addEventListener('DOMContentLoaded', function () {
    const otpInputs = document.querySelectorAll('.otp-digit');
    const fullOtpInput = document.getElementById('fullOtpInput');
    const otpForm = document.getElementById('otpForm');
    const btnVerify = document.getElementById('btnVerifyOtp');
    const validationMsg = document.getElementById('otpValidationMsg');
    const btnResend = document.getElementById('btnResendOtp');
    const countdownBox = document.getElementById('resendCountdownBox');
    const timerSpan = document.getElementById('resendTimerSeconds');
    const resendStatus = document.getElementById('resendStatusAlert');
    const token = document.getElementById('verificationToken').value;

    // Focus first input automatically
    if (otpInputs.length > 0) {
        otpInputs[0].focus();
    }

    // Combine input digits
    function updateFullOtp() {
        let code = '';
        otpInputs.forEach(input => {
            code += input.value.trim();
        });
        fullOtpInput.value = code;
        if (code.length === 6) {
            validationMsg.classList.add('d-none');
        }
        return code;
    }

    // Handle Digit Inputs, Navigation, and Backspace
    otpInputs.forEach((input, idx) => {
        input.addEventListener('input', function (e) {
            const val = this.value.replace(/\D/g, '');
            this.value = val ? val[val.length - 1] : '';

            const code = updateFullOtp();

            if (this.value && idx < otpInputs.length - 1) {
                otpInputs[idx + 1].focus();
                otpInputs[idx + 1].select();
            }

            // Auto submit when 6th digit entered
            if (code.length === 6) {
                btnVerify.focus();
            }
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace') {
                if (!this.value && idx > 0) {
                    otpInputs[idx - 1].focus();
                    otpInputs[idx - 1].value = '';
                    updateFullOtp();
                    e.preventDefault();
                }
            } else if (e.key === 'ArrowLeft' && idx > 0) {
                otpInputs[idx - 1].focus();
                e.preventDefault();
            } else if (e.key === 'ArrowRight' && idx < otpInputs.length - 1) {
                otpInputs[idx + 1].focus();
                e.preventDefault();
            }
        });

        // Handle Paste (e.g. user copies 123456 from WhatsApp)
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            if (pasted.length >= 1) {
                const chars = pasted.slice(0, 6).split('');
                chars.forEach((c, i) => {
                    if (otpInputs[i]) {
                        otpInputs[i].value = c;
                    }
                });
                updateFullOtp();
                const nextIdx = Math.min(chars.length, otpInputs.length - 1);
                otpInputs[nextIdx].focus();
            }
        });
    });

    // Form submission validation
    otpForm.addEventListener('submit', function (e) {
        const code = updateFullOtp();
        if (code.length !== 6) {
            e.preventDefault();
            validationMsg.classList.remove('d-none');
            // Focus first empty input
            for (let i = 0; i < otpInputs.length; i++) {
                if (!otpInputs[i].value) {
                    otpInputs[i].focus();
                    break;
                }
            }
            return false;
        }

        btnVerify.disabled = true;
        btnVerify.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying Code...';
    });

    // 45s Cooldown Timer for Resend OTP
    let countdown = 45;
    let timerInterval = null;

    function startTimer(seconds) {
        countdown = seconds;
        countdownBox.classList.remove('d-none');
        btnResend.classList.add('d-none');
        timerSpan.textContent = countdown;

        if (timerInterval) clearInterval(timerInterval);

        timerInterval = setInterval(() => {
            countdown--;
            if (countdown <= 0) {
                clearInterval(timerInterval);
                countdownBox.classList.add('d-none');
                btnResend.classList.remove('d-none');
            } else {
                timerSpan.textContent = countdown;
            }
        }, 1000);
    }

    startTimer(45);

    // Resend OTP Action
    btnResend.addEventListener('click', function () {
        btnResend.disabled = true;
        btnResend.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';
        resendStatus.classList.add('d-none');

        const resendUrl = "{{ function_exists('tfc_route') ? tfc_route('member.verify-mobile.resend') : route('tfc.preview.member.verify-mobile.resend') }}";

        fetch(resendUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ token: token })
        })
        .then(res => res.json())
        .then(data => {
            btnResend.disabled = false;
            btnResend.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Resend Verification Code via WhatsApp';

            if (data.success) {
                resendStatus.className = 'small mt-2 text-success fw-semibold';
                resendStatus.textContent = 'A new 6-digit verification code has been dispatched to your WhatsApp!';
                resendStatus.classList.remove('d-none');
                startTimer(60);
                // Clear inputs and refocus
                otpInputs.forEach(i => i.value = '');
                updateFullOtp();
                otpInputs[0].focus();
            } else {
                resendStatus.className = 'small mt-2 text-danger';
                resendStatus.textContent = data.message || 'Failed to dispatch code. Please try again.';
                resendStatus.classList.remove('d-none');
            }
        })
        .catch(err => {
            btnResend.disabled = false;
            btnResend.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Resend Verification Code via WhatsApp';
            resendStatus.className = 'small mt-2 text-danger';
            resendStatus.textContent = 'Network error. Please try again.';
            resendStatus.classList.remove('d-none');
        });
    });
});
</script>
@endsection
