@extends('storefront.layouts.app')
@section('title', 'Checkout')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="vr-cart-item p-5 text-center">
                    <i class="bi bi-person-lock d-block mb-3" style="font-size:3rem;color:var(--vr-border);"></i>
                    <h4 class="fw-bold mb-2">Please log in to checkout</h4>
                    <p class="text-muted mb-4">You need an account to complete your purchase. It only takes a minute.</p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ route('login') }}" class="btn btn-vr">Log In</a>
                        <a href="{{ route('register') }}" class="btn btn-vr-outline">Create Account</a>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('cart.index') }}" class="small text-muted vr-link-underline">
                            <i class="bi bi-arrow-left me-1"></i> Back to Cart
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
