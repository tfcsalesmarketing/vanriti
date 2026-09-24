@extends('storefront.layouts.app')

@section('title', 'Reset Password')
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
                    <div class="vr-kicker mb-1">SECURE RESET</div>
                    <h5 class="fw-bold text-dark mb-1">Set New Password</h5>
                    <p class="vr-auth-subtitle mb-0">Choose a strong new password for your account.</p>
                </div>

                <form method="POST" action="{{ route('password.store') }}" id="vrResetForm" novalidate>
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="reset_email">Email Address</label>
                        <div class="vr-input-wrap">
                            <input type="email" id="reset_email" name="email" value="{{ old('email', request('email')) }}"
                                   class="form-control" placeholder="e.g. user@domain.com"
                                   autocomplete="email" autofocus>
                            <i class="ri-mail-line vr-input-icon"></i>
                        </div>
                        <div class="invalid-feedback d-block mt-1 small"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="reset_password">New Password</label>
                        <div class="password-wrap vr-input-wrap">
                            <input type="password" id="reset_password" name="password"
                                   class="form-control" placeholder="Min 8 characters"
                                   autocomplete="new-password">
                            <i class="ri-lock-2-line vr-input-icon"></i>
                            <button type="button" class="password-toggle-btn" tabindex="-1" aria-label="Show password">
                                <i class="ri-eye-line"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback d-block mt-1 small"></div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold" for="reset_password_confirmation">Confirm New Password</label>
                        <div class="password-wrap vr-input-wrap">
                            <input type="password" id="reset_password_confirmation" name="password_confirmation"
                                   class="form-control" placeholder="Repeat new password"
                                   autocomplete="new-password">
                            <i class="ri-lock-check-line vr-input-icon"></i>
                            <button type="button" class="password-toggle-btn" tabindex="-1" aria-label="Show password">
                                <i class="ri-eye-line"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback d-block mt-1 small"></div>
                    </div>

                    <button type="submit" class="btn btn-vr w-100 py-2.5">
                        Reset Password &amp; Login
                    </button>
                </form>

                <p class="text-center small text-muted mt-4 mb-0">
                    <a href="{{ route('login') }}" class="fw-bold vr-link-underline">&larr; Back to login</a>
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('vrResetForm');
        if (form && typeof window.vrLiveServerValidation === 'function') {
            window.vrLiveServerValidation(form, '{{ route('auth.validate') }}', {
                context: 'reset'
            });
        }
    });
</script>
@endpush