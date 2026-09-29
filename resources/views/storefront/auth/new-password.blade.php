@extends('storefront.layouts.app')

@section('title', 'Set New Password')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-auth-shell">
    <div class="vr-auth-container vr-auth-container-narrow">
        <div class="card vr-auth-card">
            <div class="vr-auth-form-panel p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="vr-otp-icon mb-3" style="background: rgba(38, 61, 37, 0.1); color: var(--vr-auth-primary); border-color: rgba(38, 61, 37, 0.2);">
                        <i class="ri-key-2-line"></i>
                    </div>
                    <div class="vr-kicker mb-1">STEP 2 OF 2</div>
                    <h5 class="fw-bold text-dark mb-1">Set New Password</h5>
                    <p class="vr-auth-subtitle mb-0">Code verified. Choose a strong new password for your account.</p>
                </div>

                <form method="POST" action="{{ route('password.update') }}" id="vrNewPasswordForm" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="new_password">New Password</label>
                        <div class="password-wrap vr-input-wrap">
                            <input type="password" id="new_password" name="password"
                                   class="form-control" placeholder="Min 8 characters"
                                   autocomplete="new-password" autofocus>
                            <i class="ri-lock-2-line vr-input-icon"></i>
                            <button type="button" class="password-toggle-btn" tabindex="-1" aria-label="Show password">
                                <i class="ri-eye-line"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback d-block mt-1 small"></div>
                        <div class="form-text small">At least 8 characters, including a letter and a number.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="new_password_confirmation">Confirm New Password</label>
                        <div class="password-wrap vr-input-wrap">
                            <input type="password" id="new_password_confirmation" name="password_confirmation"
                                   class="form-control" placeholder="Repeat new password"
                                   autocomplete="new-password">
                            <i class="ri-lock-check-line vr-input-icon"></i>
                            <button type="button" class="password-toggle-btn" tabindex="-1" aria-label="Show password">
                                <i class="ri-eye-line"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback d-block mt-1 small"></div>
                    </div>

                    <button type="submit" class="btn btn-vr w-100 py-2.5 mt-1">
                        Update Password
                    </button>
                </form>

                <p class="text-center small text-muted mt-4 mb-0">
                    <a href="{{ route('password.request') }}" class="fw-bold vr-link-underline">Start over</a>
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
        var form = document.getElementById('vrNewPasswordForm');
        if (form && typeof window.vrLiveServerValidation === 'function') {
            window.vrLiveServerValidation(form, '{{ route('auth.validate') }}', {
                context: 'setpassword'
            });
        }
    });
</script>
@endpush
