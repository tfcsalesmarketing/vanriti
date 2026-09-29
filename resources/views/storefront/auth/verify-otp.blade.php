@extends('storefront.layouts.app')

@section('title', 'Verify Code')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-auth-shell">
    <div class="vr-auth-container vr-auth-container-narrow">
        <div class="card vr-auth-card">
            <div class="vr-auth-form-panel p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="vr-otp-icon mb-3" style="background: rgba(38, 61, 37, 0.1); color: var(--vr-auth-primary); border-color: rgba(38, 61, 37, 0.2);">
                        <i class="ri-shield-check-line"></i>
                    </div>
                    <div class="vr-kicker mb-1">STEP 1 OF 2</div>
                    <h5 class="fw-bold text-dark mb-1">Enter Verification Code</h5>
                    <p class="vr-auth-subtitle mb-0">
                        We sent a 6-digit code to
                        <strong>{{ $channel === 'whatsapp' ? 'your WhatsApp' : 'your email address' }}</strong>.
                        Enter it below to continue.
                    </p>
                </div>

                <form method="POST" action="{{ route('password.otp.check') }}" id="vrVerifyOtpForm" novalidate>
                    @csrf
                    <div class="mb-4">
                        <label class="form-label small fw-semibold" for="verify_code">6-Digit Code</label>
                        <input type="tel" id="verify_code" name="code" value="{{ old('code') }}"
                               class="form-control vr-otp-input text-center @error('code') is-invalid @enderror"
                               placeholder="- - - - - -" maxlength="6" inputmode="numeric"
                               autocomplete="one-time-code" autofocus>
                        @error('code')
                            <div class="invalid-feedback d-block mt-1 small">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-vr w-100 py-2.5">
                        Verify &amp; Continue
                    </button>
                </form>

                <form method="POST" action="{{ route('password.email') }}" class="mt-3">
                    @csrf
                    <input type="hidden" name="identifier" value="{{ session('password_reset.identifier') }}">
                    <button type="submit" class="btn btn-link w-100 small fw-semibold vr-link-underline" id="vrResendCode">
                        Didn't get the code? Send it again
                    </button>
                </form>

                <p class="text-center small text-muted mt-3 mb-0">
                    <a href="{{ route('password.request') }}" class="fw-bold vr-link-underline">Use a different account</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/storefront-auth.css') }}?v={{ @filemtime(public_path('css/storefront-auth.css')) ?: time() }}">
@endpush

@push('scripts')
<script nonce="{{ $cspNonce }}">
    document.addEventListener('DOMContentLoaded', function () {
        var input = document.getElementById('verify_code');
        if (input) {
            input.addEventListener('input', function () {
                input.value = input.value.replace(/\D/g, '').slice(0, 6);
            });
        }
    });
</script>
@endpush
