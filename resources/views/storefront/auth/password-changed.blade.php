@extends('storefront.layouts.app')

@section('title', 'Password Changed')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-auth-shell">
    <div class="vr-auth-container vr-auth-container-narrow">
        <div class="card vr-auth-card">
            <div class="vr-auth-form-panel p-4 p-md-5 text-center">
                <div class="vr-otp-icon mx-auto mb-3" style="background: rgba(38, 61, 37, 0.1); color: var(--vr-auth-primary); border-color: rgba(38, 61, 37, 0.2);">
                    <i class="ri-check-line"></i>
                </div>
                <div class="vr-kicker mb-1">ALL DONE</div>
                <h5 class="fw-bold text-dark mb-2">Password Changed</h5>
                <p class="vr-auth-subtitle mb-4">
                    Your password has been changed successfully. Sign in with your new password to continue.
                </p>

                <a href="{{ route('login') }}" class="btn btn-vr w-100 py-2.5">
                    Go to Login
                </a>

                <p class="text-center small text-muted mt-4 mb-0">
                    <i class="ri-shield-check-line me-1"></i>
                    You have been signed out of your account on all devices.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/storefront-auth.css') }}?v={{ @filemtime(public_path('css/storefront-auth.css')) ?: time() }}">
@endpush
