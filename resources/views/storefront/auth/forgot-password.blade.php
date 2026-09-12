@extends('storefront.layouts.app')

@section('title', 'Forgot Password')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-auth-shell">
    <div class="card vr-auth-card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="vr-kicker justify-content-center mb-2">PURE BY NATURE</div>
                <div class="vr-brand mb-1">{{ strtoupper(store_name()) }}</div>
                <p class="text-muted small mb-0">We'll email you a password reset link.</p>
            </div>

            <form method="POST" action="{{ route('password.email') }}" id="vrForgotForm" novalidate>
                @csrf
                <div class="mb-4">
                    <label class="form-label small fw-semibold">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg" autocomplete="email" autofocus>
                    <div class="invalid-feedback"></div>
                </div>
                <button type="submit" class="btn btn-vr w-100 py-2">Send Reset Link</button>
            </form>

            <p class="text-center small mt-4 mb-0">
                <a href="{{ route('login') }}" class="vr-link-underline">Back to login</a>
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('vrForgotForm');
        if (form && typeof window.vrLiveServerValidation === 'function') {
            window.vrLiveServerValidation(form, '{{ route('auth.validate') }}', {
                context: 'forgot',
                live: ['email']
            });
        }
    });
</script>
@endpush