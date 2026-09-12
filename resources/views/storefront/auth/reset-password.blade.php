@extends('storefront.layouts.app')

@section('title', 'Reset Password')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-auth-shell">
    <div class="card vr-auth-card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="vr-kicker justify-content-center mb-2">PURE BY NATURE</div>
                <div class="vr-brand mb-1">{{ strtoupper(store_name()) }}</div>
                <p class="text-muted small mb-0">Choose a new password.</p>
            </div>

            <form method="POST" action="{{ route('password.store') }}" id="vrResetForm" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email</label>
                    <input type="email" name="email" value="{{ old('email', request('email')) }}" class="form-control form-control-lg" autocomplete="email" autofocus>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">New Password</label>
                    <div class="password-wrap">
                        <input type="password" name="password" class="form-control form-control-lg" autocomplete="new-password">
                        <button type="button" class="password-toggle-btn" tabindex="-1" aria-label="Show password">
                            <i class="ri-eye-line"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-semibold">Confirm New Password</label>
                    <div class="password-wrap">
                        <input type="password" name="password_confirmation" class="form-control form-control-lg" autocomplete="new-password">
                        <button type="button" class="password-toggle-btn" tabindex="-1" aria-label="Show password">
                            <i class="ri-eye-line"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback"></div>
                </div>
                <button type="submit" class="btn btn-vr w-100 py-2">Reset Password</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('vrResetForm');
        if (form && typeof window.vrLiveServerValidation === 'function') {
            window.vrLiveServerValidation(form, '{{ route('auth.validate') }}', {
                context: 'reset',
                live: ['password', 'password_confirmation']
            });
        }
    });
</script>
@endpush