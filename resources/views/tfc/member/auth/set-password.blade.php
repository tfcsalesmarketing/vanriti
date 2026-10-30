@extends('tfc.layouts.app')

@section('title', 'Set Your Member Password | TFC Sales & Marketing')
@section('meta_description', 'Set your secure password to complete your independent TFC Direct Selling Member account setup.')

@section('content')
<div class="py-5 bg-light" style="min-height: 85vh;">
    <div class="container py-3">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">

                {{-- Step Progress Indicator --}}
                <div class="d-flex align-items-center justify-content-between mb-4 px-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px; background: #25D366; font-size: 0.85rem;">
                            <i class="bi bi-check-lg"></i>
                        </div>
                        <div>
                            <div class="fw-bold small text-success">Mobile Verified</div>
                            <div class="text-muted" style="font-size: 0.72rem;">Number Confirmed</div>
                        </div>
                    </div>
                    <div class="flex-grow-1 mx-3 border-top border-2" style="border-color: #25D366 !important;"></div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px; background: #0A1D37; font-size: 0.85rem;">
                            2
                        </div>
                        <div>
                            <div class="fw-bold small text-dark">Set Password</div>
                            <div class="text-muted" style="font-size: 0.72rem;">Secure Your Account</div>
                        </div>
                    </div>
                </div>

                {{-- Main Password Setup Card --}}
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header text-center bg-white py-4 px-4 border-bottom">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-3 mb-2 shadow-sm" style="background: rgba(10, 29, 55, 0.08); color: #0A1D37; width: 64px; height: 64px;">
                            <i class="bi bi-shield-lock-fill fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-1 brand-font text-dark">Set Your Personal Password</h4>
                        <div class="text-muted small">Choose a secure password to access your TFC Member Portal</div>
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
                                <div class="text-success small" style="font-size: 0.8rem;">
                                    <i class="bi bi-check-circle-fill me-1"></i> Mobile Verified (+91 {{ $member->mobile }})
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted small" style="font-size: 0.75rem;">MEMBER ID</div>
                                <span class="badge font-monospace px-2.5 py-1.5" style="background: #0A1D37; color: #D49B24; font-size: 0.85rem;">
                                    {{ $member->tfc_member_id }}
                                </span>
                            </div>
                        </div>

                        {{-- Password Setup Form --}}
                        <form method="POST" action="{{ function_exists('tfc_route') ? tfc_route('member.set-password.submit') : route('tfc.preview.member.set-password.submit') }}" id="setPasswordForm" novalidate>
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">

                            {{-- New Password --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-dark">
                                    New Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-key text-muted"></i></span>
                                    <input type="password" 
                                           name="password" 
                                           id="newPasswordInput" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           placeholder="At least 8 characters" 
                                           required 
                                           autofocus>
                                    <button class="btn btn-outline-secondary" type="button" id="btnTogglePassword">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror

                                {{-- Password strength indicator --}}
                                <div class="mt-2">
                                    <div class="progress" style="height: 5px;">
                                        <div id="passwordStrengthBar" class="progress-bar bg-danger" role="progressbar" style="width: 0%;"></div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span id="passwordStrengthText" class="text-muted" style="font-size: 0.72rem;">Strength: Too short</span>
                                        <span class="text-muted" style="font-size: 0.72rem;">Min 8 characters</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Confirm Password --}}
                            <div class="mb-4">
                                <label class="form-label fw-semibold small text-dark">
                                    Confirm New Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-check2-circle text-muted"></i></span>
                                    <input type="password" 
                                           name="password_confirmation" 
                                           id="confirmPasswordInput" 
                                           class="form-control" 
                                           placeholder="Re-enter same password" 
                                           required>
                                    <span class="input-group-text bg-white d-none" id="matchIndicator">
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    </span>
                                </div>
                                <div id="matchFeedback" class="small mt-1 text-danger d-none">Passwords do not match.</div>
                            </div>

                            <button type="submit" id="btnSavePassword" class="btn btn-tfc-primary w-100 py-2.5 fw-bold shadow-sm mb-2">
                                <i class="bi bi-check-circle-fill me-1.5"></i> Save Password &amp; Go to Login
                            </button>
                        </form>

                    </div>

                    <div class="card-footer bg-light py-3 px-4 border-top text-center">
                        <div class="small text-muted d-flex align-items-center justify-content-center gap-1.5">
                            <i class="bi bi-shield-check text-success"></i>
                            Your new password will be immediately active for portal login.
                        </div>
                    </div>
                </div>

                {{-- Back to login link --}}
                <div class="text-center mt-3 small text-muted">
                    Already remember your credentials? <a href="{{ function_exists('tfc_route') ? tfc_route('member.login') : route('tfc.preview.member.login') }}" class="text-decoration-none">Sign In directly</a>
                </div>

            </div>
        </div>
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('newPasswordInput');
    const confirmInput = document.getElementById('confirmPasswordInput');
    const btnToggle = document.getElementById('btnTogglePassword');
    const toggleIcon = document.getElementById('toggleIcon');
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthText = document.getElementById('passwordStrengthText');
    const matchIndicator = document.getElementById('matchIndicator');
    const matchFeedback = document.getElementById('matchFeedback');
    const form = document.getElementById('setPasswordForm');
    const btnSave = document.getElementById('btnSavePassword');

    // Toggle Password Visibility
    btnToggle.addEventListener('click', function () {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            confirmInput.type = 'text';
            toggleIcon.className = 'bi bi-eye-slash';
        } else {
            passwordInput.type = 'password';
            confirmInput.type = 'password';
            toggleIcon.className = 'bi bi-eye';
        }
    });

    // Calculate Password Strength
    function checkStrength(pw) {
        if (!pw || pw.length < 6) {
            return { width: '15%', color: 'bg-danger', text: 'Too short' };
        }
        let score = 0;
        if (pw.length >= 8) score++;
        if (/[A-Z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;

        if (score <= 1) {
            return { width: '35%', color: 'bg-danger', text: 'Weak' };
        } else if (score === 2) {
            return { width: '65%', color: 'bg-warning', text: 'Medium' };
        } else {
            return { width: '100%', color: 'bg-success', text: 'Strong' };
        }
    }

    passwordInput.addEventListener('input', function () {
        const val = this.value;
        const res = checkStrength(val);
        strengthBar.style.width = res.width;
        strengthBar.className = 'progress-bar ' + res.color;
        strengthText.textContent = 'Strength: ' + res.text;

        checkMatch();
    });

    function checkMatch() {
        const p1 = passwordInput.value;
        const p2 = confirmInput.value;

        if (p2.length === 0) {
            matchIndicator.classList.add('d-none');
            matchFeedback.classList.add('d-none');
            return;
        }

        if (p1 === p2) {
            matchIndicator.classList.remove('d-none');
            matchIndicator.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
            matchFeedback.classList.add('d-none');
        } else {
            matchIndicator.classList.remove('d-none');
            matchIndicator.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i>';
            matchFeedback.classList.remove('d-none');
        }
    }

    confirmInput.addEventListener('input', checkMatch);

    form.addEventListener('submit', function (e) {
        const p1 = passwordInput.value;
        const p2 = confirmInput.value;

        if (p1.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long.');
            passwordInput.focus();
            return false;
        }

        if (p1 !== p2) {
            e.preventDefault();
            matchFeedback.classList.remove('d-none');
            confirmInput.focus();
            return false;
        }

        btnSave.disabled = true;
        btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving Password...';
    });
});
</script>
@endsection
