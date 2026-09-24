@extends('storefront.layouts.app')

@section('title', 'Forgot Password')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-auth-shell">
    <div class="vr-auth-container vr-auth-container-narrow">
        <div class="card vr-auth-card">
            <div class="vr-auth-form-panel p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="vr-otp-icon mb-3" style="background: rgba(38, 61, 37, 0.1); color: var(--vr-auth-primary); border-color: rgba(38, 61, 37, 0.2);">
                        <i class="ri-lock-unlock-line"></i>
                    </div>
                    <div class="vr-kicker mb-1">RECOVER ACCOUNT</div>
                    <h5 class="fw-bold text-dark mb-1">Forgot Password?</h5>
                    <p class="vr-auth-subtitle mb-0">Enter your registered email and we'll send you a password reset link.</p>
                </div>

                <form method="POST" action="{{ route('password.email') }}" id="vrForgotForm" novalidate>
                    @csrf
                    <div class="mb-4">
                        <label class="form-label small fw-semibold" for="forgot_email">Email Address</label>
                        <div class="vr-input-wrap">
                            <input type="email" id="forgot_email" name="email" value="{{ old('email') }}"
                                   class="form-control" placeholder="e.g. user@domain.com"
                                   autocomplete="email" autofocus>
                            <i class="ri-mail-send-line vr-input-icon"></i>
                        </div>
                        <div class="invalid-feedback d-block mt-1 small"></div>
                    </div>

                    <button type="submit" class="btn btn-vr w-100 py-2.5">
                        Send Reset Link
                    </button>
                </form>

                <p class="text-center small text-muted mt-4 mb-0">
                    Remember your password? <a href="{{ route('login') }}" class="fw-bold vr-link-underline">&larr; Back to login</a>
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
        var form = document.getElementById('vrForgotForm');
        if (form && typeof window.vrLiveServerValidation === 'function') {
            window.vrLiveServerValidation(form, '{{ route('auth.validate') }}', {
                context: 'forgot'
            });
        }
    });
</script>
@endpush