@extends('storefront.layouts.app')

@section('title', 'Login')
@section('robots', 'noindex, nofollow')

@push('styles')
<style>
    .vr-auth-tabs .nav-link {
        color: var(--vr-green-dark);
        font-weight: 600;
    }
    .vr-auth-tabs .nav-link:not(.active):hover {
        color: var(--vr-green);
    }
    .vr-auth-tabs .nav-link.active {
        background-color: var(--vr-green-dark);
        color: #fff;
        border-color: var(--vr-green-dark);
    }
</style>
@endpush

@section('content')
<div class="vr-auth-shell">
    <div class="card vr-auth-card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="vr-kicker justify-content-center mb-2">PURE BY NATURE</div>
                <div class="vr-brand mb-1">{{ strtoupper(store_name()) }}</div>
                <p class="text-muted small mb-0">Welcome back &mdash; pick up where you left off.</p>
            </div>

            <ul class="nav nav-pills nav-fill mb-4 vr-auth-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="vrTabEmailBtn" data-bs-toggle="pill" data-bs-target="#vrPaneEmail" type="button" role="tab" aria-controls="vrPaneEmail" aria-selected="true">Email</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="vrTabPhoneBtn" data-bs-toggle="pill" data-bs-target="#vrPanePhone" type="button" role="tab" aria-controls="vrPanePhone" aria-selected="false">Mobile OTP</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="vrPaneEmail" role="tabpanel" aria-labelledby="vrTabEmailBtn">
                    <form method="POST" action="{{ route('login.submit') }}" id="vrLoginForm" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg" autocomplete="email" autofocus>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Password</label>
                            <div class="password-wrap">
                                <input type="password" name="password" class="form-control form-control-lg" autocomplete="current-password">
                                <button type="button" class="password-toggle-btn" tabindex="-1" aria-label="Show password">
                                    <i class="ri-eye-line"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input type="checkbox" name="remember" id="remember" class="form-check-input">
                                <label for="remember" class="form-check-label small">Remember me</label>
                            </div>
                            <a href="{{ route('password.request') }}" class="small vr-link-underline">Forgot password?</a>
                        </div>
                        <button type="submit" class="btn btn-vr w-100 py-2">Login</button>
                    </form>
                </div>

                <div class="tab-pane fade" id="vrPanePhone" role="tabpanel" aria-labelledby="vrTabPhoneBtn">
                    <form method="POST" id="vrOtpLoginForm" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Mobile</label>
                            <div class="d-flex gap-2">
                                <input type="tel" name="phone" value="{{ old('phone') }}" class="form-control form-control-lg" autocomplete="tel" inputmode="tel">
                                <button type="button" class="btn btn-vr-outline text-nowrap" data-vr-send-otp>Send OTP</button>
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3 d-none" data-vr-otp-panel>
                            <label class="form-label small fw-semibold">One-time password</label>
                            <input type="text" name="code" value="{{ old('code') }}" class="form-control form-control-lg" inputmode="numeric" autocomplete="one-time-code" maxlength="6" aria-label="One-time password">
                            <div class="invalid-feedback"></div>
                        </div>
                        <small class="text-muted d-block mb-1" data-vr-otp-status></small>
                        <small class="text-muted d-block mb-3 d-none" data-vr-otp-timer></small>
                        <button type="submit" class="btn btn-vr w-100 py-2">Login with OTP</button>
                        <p class="text-muted small mt-3 mb-0">We will text a one-time code to your mobile number.</p>
                    </form>
                </div>
            </div>

            <p class="text-center small mt-4 mb-0">
                New here? <a href="{{ route('register') }}" class="fw-semibold vr-link-underline">Create an account</a>
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('vrLoginForm');
        if (form && typeof window.vrLiveServerValidation === 'function') {
            window.vrLiveServerValidation(form, '{{ route('auth.validate') }}', {
                context: 'login',
                live: ['email']
            });
        }
    });
</script>
@endpush