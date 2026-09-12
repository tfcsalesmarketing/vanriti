@extends('storefront.layouts.app')

@section('title', 'Create Account')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-auth-shell">
    <div class="card vr-auth-card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="vr-kicker justify-content-center mb-2">PURE BY NATURE</div>
                <div class="vr-brand mb-1">{{ strtoupper(store_name()) }}</div>
                <p class="text-muted small mb-0">Create your account for faster checkouts &amp; rewards.</p>
            </div>

            <form method="POST" action="{{ route('register.submit') }}" id="vrRegisterForm" novalidate>
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control form-control-lg" autocomplete="name" autofocus>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg" autocomplete="email">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Mobile (optional)</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" class="form-control form-control-lg" autocomplete="tel" inputmode="tel">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Password</label>
                    <div class="password-wrap">
                        <input type="password" name="password" class="form-control form-control-lg" autocomplete="new-password">
                        <button type="button" class="password-toggle-btn" tabindex="-1" aria-label="Show password">
                            <i class="ri-eye-line"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-semibold">Confirm Password</label>
                    <div class="password-wrap">
                        <input type="password" name="password_confirmation" class="form-control form-control-lg" autocomplete="new-password">
                        <button type="button" class="password-toggle-btn" tabindex="-1" aria-label="Show password">
                            <i class="ri-eye-line"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback"></div>
                </div>
                <button type="submit" class="btn btn-vr w-100 py-2">Create Account</button>
            </form>

            <p class="text-center small mt-4 mb-0">
                Already registered? <a href="{{ route('login') }}" class="fw-semibold vr-link-underline">Login</a>
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('vrRegisterForm');
        if (form && typeof window.vrLiveServerValidation === 'function') {
            window.vrLiveServerValidation(form, '{{ route('auth.validate') }}', {
                context: 'register',
                live: ['email', 'phone']
            });
        }
    });
</script>
@endpush