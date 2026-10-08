@extends('storefront.layouts.app')
@section('title', 'Checkout')
@section('robots', 'noindex, nofollow')

@section('content')
{{-- Guest checkout: same form as the authenticated page, no account required. --}}
<div class="vr-section pt-4">
    <div class="container">
        <div class="alert alert-light border d-flex flex-wrap align-items-center justify-content-between gap-2 mb-0" style="border-radius:12px;">
            <span class="small">
                <i class="ri-user-smile-line me-1 text-success"></i>
                Checking out as a <strong>guest</strong> — no account needed.
            </span>
            <span class="small text-muted">
                <a href="{{ route('login') }}" class="vr-link-underline">Log in</a>
                or
                <a href="{{ route('register') }}" class="vr-link-underline">create an account</a>
                to track orders faster next time.
            </span>
        </div>
    </div>
</div>
@include('storefront.checkout.form')
@endsection
