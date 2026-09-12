@extends('storefront.layouts.app')

@section('title', 'Login')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-auth-shell">
    <div class="card vr-auth-card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="vr-kicker justify-content-center mb-2">PURE BY NATURE</div>
                <div class="vr-brand mb-1">{{ strtoupper(store_name()) }}</div>
                <p class="text-muted small mb-0">Welcome back &mdash; pick up where you left off.</p>
            </div>

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