@extends('storefront.layouts.app')
@section('title', 'Your Shopping Cart — ' . store_name())
@section('robots', 'noindex, nofollow')

@push('styles')
<style>
/* ── Vanriti Luxury Wellness Cart Page Styles ── */
.vr-cart-page {
    background: #FAF9F5;
    background-image:
        radial-gradient(circle at 0% 0%, rgba(111, 135, 54, 0.04) 0%, transparent 35%),
        radial-gradient(circle at 100% 50%, rgba(111, 135, 54, 0.03) 0%, transparent 40%);
    min-height: 85vh;
}

.vr-cart-title {
    font-family: 'Playfair Display', Georgia, 'Times New Roman', serif;
    font-size: clamp(1.85rem, 3.2vw, 2.35rem);
    font-weight: 700;
    color: #1C3C24;
    letter-spacing: -0.02em;
}

.vr-cart-count-pill {
    background: #EBF3ED;
    color: #1C3C24;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 4px 14px;
    border-radius: 50px;
    display: inline-flex;
    align-items: center;
    line-height: 1.2;
}

.vr-cart-subtitle {
    color: #6D7D70;
    font-size: 0.92rem;
}

/* ── Checkout Steps Breadcrumbs ── */
.vr-cart-steps {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
}

.vr-cart-step-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
    color: #8C9E90;
    text-decoration: none;
}

.vr-cart-step-item.active {
    color: #1C3C24;
    font-weight: 700;
}

.vr-cart-step-sep {
    color: #BDC8BF;
    font-size: 0.8rem;
}

/* ── Free Shipping Progress Card ── */
.vr-shipping-threshold-card {
    background: #F4F8F4;
    border: 1px solid #E1ECE2;
    border-radius: 16px;
    padding: 14px 22px;
    margin-bottom: 24px;
}

.vr-ship-progress-track {
    height: 6px;
    background: #DCE7DE;
    border-radius: 999px;
    overflow: hidden;
    position: relative;
    width: 100%;
}

.vr-ship-progress-fill {
    height: 100%;
    background: #1C3C24;
    border-radius: 999px;
    transition: width 0.4s ease;
}

/* ── Cart Item Cards ── */
.vr-cart-item-card {
    background: #FFFFFF;
    border: 1px solid #EAF0EB;
    border-radius: 16px;
    padding: 22px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(28, 60, 36, 0.02);
    transition: border-color 0.2s, box-shadow 0.2s;
}

.vr-cart-item-card:hover {
    border-color: #DDE7DF;
    box-shadow: 0 4px 14px rgba(28, 60, 36, 0.04);
}

.vr-item-thumb-box {
    width: 110px;
    height: 110px;
    border-radius: 12px;
    background: #F8F9F8;
    border: 1px solid #EFF3F0;
    padding: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}

.vr-item-thumb-img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transition: transform 0.25s ease;
}

.vr-item-thumb-box:hover .vr-item-thumb-img {
    transform: scale(1.05);
}

.vr-item-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1C3C24;
    line-height: 1.35;
    text-decoration: none;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.vr-item-title:hover {
    color: #2F693A;
}

.vr-item-tag {
    background: #F2F7F3;
    color: #234E2E;
    font-size: 0.74rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 50px;
    border: 1px solid #E2EFE4;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}

.vr-item-trash-btn {
    background: transparent;
    border: none;
    color: #9AA89D;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    transition: all 0.15s ease;
    padding: 0;
}

.vr-item-trash-btn:hover {
    background: #FDF2F2;
    color: #DC2626;
}

.vr-price-current {
    font-size: 1.35rem;
    font-weight: 800;
    color: #1C3C24;
}

.vr-price-mrp {
    font-size: 0.92rem;
    color: #9CA89F;
    text-decoration: line-through;
    font-weight: 500;
}

.vr-discount-pill {
    background: #E8F5E9;
    color: #2E7D32;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 6px;
    display: inline-block;
    letter-spacing: 0.3px;
}

/* ── Stepper Pill ── */
.vr-stepper-pill {
    background: #FFFFFF;
    border: 1px solid #D5DFD7;
    border-radius: 50px;
    display: inline-flex;
    align-items: center;
    padding: 2px 6px;
    height: 38px;
}

.vr-stepper-pill button {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: none;
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1C3C24;
    font-size: 0.95rem;
    transition: background 0.15s;
    cursor: pointer;
}

.vr-stepper-pill button:hover:not(:disabled) {
    background: #EFF4F0;
    color: #1C3C24;
}

.vr-stepper-pill button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.vr-stepper-pill input {
    width: 36px;
    border: none;
    text-align: center;
    font-weight: 700;
    color: #1C3C24;
    background: transparent;
    font-size: 0.95rem;
    outline: none;
    -moz-appearance: textfield;
}

.vr-stepper-pill input::-webkit-outer-spin-button,
.vr-stepper-pill input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* ── Action Buttons (Continue Shopping & Clear Cart) ── */
.vr-cart-actions-toolbar {
    margin-top: 16px;
    margin-bottom: 28px;
    padding: 4px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
}

.vr-btn-continue-shopping {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #FFFFFF;
    border: 1.5px solid #D0DCD2;
    color: #1C3C24;
    font-weight: 600;
    font-size: 0.88rem;
    padding: 9px 22px;
    border-radius: 50px;
    text-decoration: none;
    box-shadow: 0 1px 3px rgba(28, 60, 36, 0.04);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    white-space: nowrap;
}

.vr-btn-continue-shopping i {
    font-size: 1.05rem;
    color: #1C3C24;
    transition: transform 0.2s ease, color 0.2s ease;
}

.vr-btn-continue-shopping:hover {
    background: #1C3C24;
    border-color: #1C3C24;
    color: #FFFFFF;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(28, 60, 36, 0.14);
}

.vr-btn-continue-shopping:hover i {
    color: #FFFFFF;
    transform: translateX(-3px);
}

.vr-btn-continue-shopping:active {
    transform: translateY(0);
}

.vr-btn-clear-cart {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: transparent;
    border: 1px solid transparent;
    color: #DC2626;
    font-weight: 600;
    font-size: 0.88rem;
    padding: 8px 16px;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.vr-btn-clear-cart i {
    font-size: 1.05rem;
    color: #DC2626;
    transition: transform 0.2s ease;
}

.vr-btn-clear-cart:hover {
    background: #FEF2F2;
    border-color: #FECACA;
    color: #B91C1C;
    transform: translateY(-1px);
}

.vr-btn-clear-cart:hover i {
    transform: scale(1.15);
    color: #B91C1C;
}

.vr-btn-clear-cart:active {
    transform: translateY(0);
}

/* ── Order Summary Card (Sticky) ── */
.vr-summary-card {
    background: #FFFFFF;
    border: 1px solid #EAF0EB;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 18px rgba(28, 60, 36, 0.03);
}

.vr-summary-title {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.35rem;
    font-weight: 700;
    color: #1C3C24;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
}

/* Coupon Group */
.vr-coupon-input-group {
    border: 1px solid #D5DFD7;
    border-radius: 10px;
    overflow: hidden;
    display: flex;
    background: #FFFFFF;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.vr-coupon-input-group:focus-within {
    border-color: #1C3C24;
    box-shadow: 0 0 0 3px rgba(28, 60, 36, 0.08);
}

.vr-coupon-input-group .input-icon {
    display: flex;
    align-items: center;
    padding-left: 12px;
    color: #8C9E90;
    font-size: 1rem;
}

.vr-coupon-input-group input {
    border: none;
    padding: 10px 12px;
    font-size: 0.88rem;
    width: 100%;
    outline: none;
    font-weight: 600;
    text-transform: uppercase;
}

.vr-coupon-input-group input::placeholder {
    text-transform: none;
    color: #9AA89D;
    font-weight: 400;
}

.vr-coupon-apply-btn {
    background: #1C3C24;
    color: #FFFFFF;
    border: none;
    padding: 0 20px;
    font-weight: 700;
    font-size: 0.88rem;
    letter-spacing: 0.3px;
    transition: background 0.15s;
    white-space: nowrap;
}

.vr-coupon-apply-btn:hover {
    background: #142C1A;
    color: #FFFFFF;
}

/* Primary Checkout Button */
.vr-btn-checkout {
    background: #1C3C24;
    color: #FFFFFF;
    font-weight: 700;
    font-size: 1.02rem;
    border-radius: 50px;
    padding: 14px 24px;
    width: 100%;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 14px rgba(28, 60, 36, 0.2);
    transition: all 0.2s ease;
    text-decoration: none;
    cursor: pointer;
}

.vr-btn-checkout:hover {
    background: #142C1A;
    color: #FFFFFF;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(28, 60, 36, 0.28);
}

/* Trust Badges Strip */
.vr-trust-strip {
    background: #F8FAF8;
    border-radius: 12px;
    padding: 12px 14px;
    margin-top: 18px;
}

.vr-trust-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    font-size: 0.74rem;
    font-weight: 600;
    color: #4A5B4D;
    gap: 4px;
}

.vr-trust-item i {
    font-size: 1.15rem;
    color: #1C3C24;
}

/* ── Why Choose VANRITI Card ── */
.vr-why-card {
    background: #F8FAF8;
    border: 1px solid #E3ECE4;
    border-radius: 16px;
    padding: 22px;
    margin-top: 18px;
}

.vr-why-title {
    font-size: 0.98rem;
    font-weight: 700;
    color: #1C3C24;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 16px;
}

.vr-why-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.vr-why-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 6px;
}

.vr-why-icon-circle {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: #FFFFFF;
    border: 1px solid #E2EFE4;
    color: #1C3C24;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}

.vr-why-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #2D3D30;
    line-height: 1.25;
}

/* ── "You May Also Like" Recommendation Section ── */
.vr-rec-section {
    background: #FFFFFF;
    border: 1px solid #EAF0EB;
    border-radius: 16px;
    padding: 24px;
    margin-top: 32px;
}

.vr-rec-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #1C3C24;
}

.vr-rec-subtitle {
    font-size: 0.82rem;
    color: #7A8A7C;
}

.vr-rec-card {
    background: #FFFFFF;
    border: 1px solid #EAF0EB;
    border-radius: 14px;
    padding: 12px;
    position: relative;
    display: flex;
    flex-direction: column;
    height: 100%;
    transition: transform 0.2s, box-shadow 0.2s;
}

.vr-rec-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(28, 60, 36, 0.06);
    border-color: #DDE7DF;
}

.vr-rec-wish-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #FFFFFF;
    border: 1px solid #EAEFEA;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #8C9E90;
    font-size: 0.85rem;
    z-index: 2;
    transition: all 0.15s ease;
    padding: 0;
}

.vr-rec-wish-btn:hover,
.vr-rec-wish-btn.liked {
    color: #DC2626;
    border-color: #FECACA;
    background: #FEF2F2;
}

.vr-rec-thumb-box {
    height: 125px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #F8F9F8;
    border-radius: 10px;
    overflow: hidden;
    padding: 8px;
    margin-bottom: 10px;
}

.vr-rec-thumb-img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.vr-rec-name {
    font-size: 0.82rem;
    font-weight: 600;
    color: #1C3C24;
    line-height: 1.35;
    text-decoration: none;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 6px;
    min-height: 2.2em;
}

.vr-rec-name:hover {
    color: #2F693A;
}

.vr-rec-price {
    font-weight: 700;
    color: #1C3C24;
    font-size: 0.95rem;
    margin-bottom: 10px;
}

.vr-btn-rec-add {
    background: transparent;
    border: 1.5px solid #1C3C24;
    color: #1C3C24;
    font-weight: 600;
    font-size: 0.78rem;
    border-radius: 50px;
    padding: 6px 12px;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s ease;
    margin-top: auto;
}

.vr-btn-rec-add:hover {
    background: #1C3C24;
    color: #FFFFFF;
}

@media (max-width: 767.98px) {
    .vr-item-thumb-box {
        width: 85px;
        height: 85px;
    }
    .vr-cart-item-card {
        padding: 16px;
    }
    .vr-price-current {
        font-size: 1.15rem;
    }
}
</style>
@endpush

@section('content')
<div class="vr-cart-page py-4 py-lg-5">
    <div class="container">

        {{-- Header Bar: Title, Items Count Pill, and Breadcrumb Steps --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2.5 flex-wrap">
                    <h1 class="vr-cart-title mb-0">Your Shopping Cart</h1>
                    @if ($cartItems->isNotEmpty())
                        <span class="vr-cart-count-pill">{{ $count }} {{ Str::plural('Item', $count) }}</span>
                    @endif
                </div>
                <p class="vr-cart-subtitle mb-0 mt-1">Review your items before checkout</p>
            </div>

            {{-- Checkout Steps Breadcrumb (Right Side) --}}
            <div class="vr-cart-steps d-none d-md-flex align-items-center">
                <span class="vr-cart-step-item active">
                    <i class="ri-shopping-cart-2-line"></i> Shopping Cart
                </span>
                <span class="vr-cart-step-sep"><i class="ri-arrow-right-s-line"></i></span>
                <span class="vr-cart-step-item">
                    <i class="ri-map-pin-line"></i> Shipping
                </span>
                <span class="vr-cart-step-sep"><i class="ri-arrow-right-s-line"></i></span>
                <span class="vr-cart-step-item">
                    <i class="ri-bank-card-line"></i> Payment
                </span>
            </div>
        </div>

        @if ($cartItems->isEmpty())
            {{-- Empty Cart State --}}
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center mx-auto" style="max-width: 520px; background: #FFFFFF;">
                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                     style="width: 80px; height: 80px; background: #EBF3ED; color: #1C3C24; font-size: 2.4rem;">
                    <i class="ri-shopping-bag-line"></i>
                </div>
                <h4 class="fw-bold mb-2 text-dark" style="font-family: 'Playfair Display', Georgia, serif;">Your Cart is Empty</h4>
                <p class="text-muted small mb-4">Looks like you haven't added any natural wellness essentials to your cart yet.</p>
                <a href="{{ route('shop.index') }}" class="btn vr-btn-checkout mx-auto" style="max-width: 260px;">
                    <i class="ri-compass-line me-1"></i> Start Shopping
                </a>
            </div>
        @else
            {{-- Free Shipping Banner Card --}}
            @php
                $freeThreshold = $shipping['free_threshold'] ?? 499;
                $diff = max(0, $freeThreshold - $subtotal);
                $pct = min(100, round(($subtotal / $freeThreshold) * 100));
            @endphp
            <div class="vr-shipping-threshold-card" data-free-threshold="{{ $freeThreshold }}">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2 fw-semibold" style="font-size: 0.95rem; color: #1C3C24;">
                        <i class="ri-truck-line text-success fs-5"></i>
                        @if ($diff <= 0)
                            <span class="text-success fw-bold">🎉 Congratulations! You have unlocked FREE Shipping!</span>
                        @else
                            <span>Add <strong class="text-dark">{{ format_price($diff) }}</strong> more to get <strong class="text-dark">FREE Shipping</strong></span>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-3 ms-md-auto">
                        <span class="small fw-bold" style="color: #1C3C24;">{{ $pct }}%</span>
                        <div class="d-none d-sm-flex align-items-center gap-1.5 small border-start ps-3" style="color: #2E5A35; border-color: #D3E2D5 !important;">
                            <i class="ri-gift-line fs-5 text-success"></i>
                            <div>
                                <div class="fw-bold lh-1" style="font-size: 0.8rem;">Free Shipping</div>
                                <div class="text-muted" style="font-size: 0.72rem;">on orders above ₹{{ $freeThreshold }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="vr-ship-progress-track">
                    <div class="vr-ship-progress-fill" style="width: {{ $pct }}%;"></div>
                </div>
            </div>

            <div class="row g-4">
                {{-- Left Column: Items List & Recommendations --}}
                <div class="col-lg-8">
                    <div class="d-flex flex-column">
                        @foreach ($cartItems as $item)
                            <div class="vr-cart-item-card" data-unit-price="{{ $item->unit_price }}">
                                <div class="d-flex gap-3 align-items-start">
                                    {{-- Product Thumbnail --}}
                                    <a href="{{ route('product.show', $item->product->slug) }}" class="vr-item-thumb-box">
                                        <img src="{{ image_url($item->product->getPrimaryImage()?->image_path, 'images/placeholder.png') }}"
                                             alt="{{ $item->product->name }}"
                                             class="vr-item-thumb-img"
                                             loading="lazy">
                                    </a>

                                    {{-- Details & Actions --}}
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <a href="{{ route('product.show', $item->product->slug) }}" class="vr-item-title" title="{{ $item->product->name }}">
                                                {{ $item->product->name }}
                                            </a>

                                            {{-- Remove Button Form --}}
                                            <form action="{{ route('cart.remove', $item) }}" method="POST" class="vr-cart-item-remove-form m-0">
                                                @csrf
                                                <button type="submit" class="vr-item-trash-btn vr-cart-item-remove-btn" title="Remove {{ $item->product->name }}" aria-label="Remove item">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>

                                        {{-- Highlights / Tags Row --}}
                                        <div class="d-flex flex-wrap gap-1.5 my-2">
                                            @if ($item->variant)
                                                <span class="vr-item-tag"><i class="ri-price-tag-3-line"></i> {{ $item->variant->name }}</span>
                                            @endif
                                            <span class="vr-item-tag"><i class="ri-leaf-line"></i> Natural Ingredients</span>
                                            <span class="vr-item-tag"><i class="ri-shield-star-line"></i> Chemical Free</span>
                                            <span class="vr-item-tag d-none d-sm-inline-flex"><i class="ri-sparkling-line"></i> Suitable for All Types</span>
                                        </div>

                                        {{-- Price Row --}}
                                        <div class="d-flex align-items-baseline gap-2 mb-3">
                                            <span class="vr-price-current">{{ format_price($item->unit_price) }}</span>
                                            @php
                                                $prodMrp = $item->product->mrp;
                                                $hasDiscount = $prodMrp && $prodMrp > $item->unit_price;
                                                $discPct = $hasDiscount ? round((($prodMrp - $item->unit_price) / $prodMrp) * 100) : 0;
                                            @endphp
                                            @if ($hasDiscount)
                                                <span class="vr-price-mrp">{{ format_price($prodMrp) }}</span>
                                                <span class="vr-discount-pill">{{ $discPct }}% OFF</span>
                                            @endif
                                        </div>

                                        {{-- Quantity Stepper & Line Subtotal Row --}}
                                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-light-subtle">
                                            <form action="{{ route('cart.update', $item) }}" method="POST" data-auto-qty="1" class="vr-cart-item-qty-form m-0">
                                                @csrf
                                                <div class="vr-stepper-pill vr-qty">
                                                    <button type="button" class="qty-minus {{ $item->quantity <= 1 ? 'is-remove text-danger' : '' }}"
                                                            data-step="-1"
                                                            aria-label="{{ $item->quantity <= 1 ? 'Remove from cart' : 'Decrease quantity' }}"
                                                            title="{{ $item->quantity <= 1 ? 'Remove from cart' : 'Decrease quantity' }}">
                                                        <i class="{{ $item->quantity <= 1 ? 'ri-delete-bin-line' : 'ri-subtract-line' }}"></i>
                                                    </button>
                                                    <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="5" readonly aria-label="Quantity">
                                                    <button type="button" class="qty-plus" data-step="1" aria-label="Increase quantity" {{ $item->quantity >= 5 ? 'disabled' : '' }}>
                                                        <i class="ri-add-line"></i>
                                                    </button>
                                                </div>
                                            </form>

                                            <div class="text-end">
                                                <div class="text-muted" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px;">Subtotal</div>
                                                <div class="fw-bold text-dark fs-6 vr-line-total">{{ format_price($item->unit_price * $item->quantity) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Bottom Action Buttons --}}
                    <div class="vr-cart-actions-toolbar d-flex flex-column flex-sm-row justify-content-between align-items-stretch align-items-sm-center gap-3">
                        <a href="{{ route('shop.index') }}" class="vr-btn-continue-shopping">
                            <i class="ri-arrow-left-line"></i>
                            <span>Continue Shopping</span>
                        </a>
                        <form action="{{ route('cart.clear') }}" method="POST" data-confirm="Clear all items from your cart?" class="m-0">
                            @csrf
                            <button type="submit" class="vr-btn-clear-cart w-100 w-sm-auto">
                                <i class="ri-delete-bin-line"></i>
                                <span>Clear Cart</span>
                            </button>
                        </form>
                    </div>

                    {{-- "You May Also Like" (Frequently Bought Together) Section --}}
                    @if (isset($relatedProducts) && $relatedProducts->isNotEmpty())
                        <div class="vr-rec-section">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <div class="vr-rec-title">You May Also Like</div>
                                    <div class="vr-rec-subtitle">Frequently bought together</div>
                                </div>
                                <a href="{{ route('shop.index') }}" class="text-decoration-none fw-semibold small" style="color: #1C3C24;">
                                    View All <i class="ri-arrow-right-line ms-0.5"></i>
                                </a>
                            </div>

                            <div class="row row-cols-2 row-cols-md-4 g-2.5">
                                @foreach ($relatedProducts as $relProduct)
                                    @php
                                        $relImg = $relProduct->getPrimaryImage()?->image_path;
                                        $relWishlist = app(\App\Services\WishlistService::class);
                                        $relLiked = $relWishlist->has($relProduct);
                                    @endphp
                                    <div class="col">
                                        <div class="vr-rec-card">
                                            {{-- Wishlist Toggle --}}
                                            <form method="POST" action="{{ route('wishlist.toggle', $relProduct) }}" class="m-0 js-wishlist-form">
                                                @csrf
                                                <button type="submit" class="vr-rec-wish-btn {{ $relLiked ? 'liked' : '' }}" title="{{ $relLiked ? 'Remove from wishlist' : 'Add to wishlist' }}" aria-label="Toggle wishlist">
                                                    <i class="bi {{ $relLiked ? 'bi-heart-fill text-danger' : 'bi-heart' }}"></i>
                                                </button>
                                            </form>

                                            {{-- Thumbnail --}}
                                            <a href="{{ route('product.show', $relProduct->slug) }}" class="vr-rec-thumb-box">
                                                <img src="{{ image_url($relImg, 'images/placeholder.png') }}" alt="{{ $relProduct->name }}" class="vr-rec-thumb-img" loading="lazy">
                                            </a>

                                            {{-- Title --}}
                                            <a href="{{ route('product.show', $relProduct->slug) }}" class="vr-rec-name" title="{{ $relProduct->name }}">
                                                {{ $relProduct->name }}
                                            </a>

                                            {{-- Price --}}
                                            <div class="vr-rec-price">{{ format_price($relProduct->selling_price) }}</div>

                                            {{-- Add to Cart Button --}}
                                            <form action="{{ route('cart.add', $relProduct->slug) }}" method="POST" class="m-0 mt-auto">
                                                @csrf
                                                <button type="submit" class="btn vr-btn-rec-add">
                                                    <i class="ri-shopping-cart-line"></i> Add to Cart
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Right Column: Sticky Order Summary & Trust Badges --}}
                <div class="col-lg-4">
                    <div class="sticky-top" style="top: 90px; z-index: 10;">
                        <div class="vr-summary-card">
                            <h2 class="vr-summary-title">
                                <i class="ri-file-list-3-line text-success fs-5"></i> Order Summary
                            </h2>

                            {{-- Coupon Widget --}}
                            <div class="vr-coupon-widget mb-3" id="vrCartCouponWidget" data-url="{{ route('cart.coupon') }}">
                                {{-- Applied coupon card --}}
                                <div class="p-2.5 rounded-3 border border-success-subtle {{ $couponCode ? '' : 'd-none' }}" id="vrCartAppliedCouponCard" style="background: rgba(28, 60, 36, 0.05);">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center text-success flex-shrink-0" style="width:32px;height:32px;background:rgba(28, 60, 36, 0.12);">
                                                <i class="ri-checkbox-circle-fill fs-5"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                                    <span class="badge bg-success text-white fw-bold text-uppercase px-2 py-0.5 vr-coupon-badge" style="letter-spacing:0.5px;">{{ $couponCode }}</span>
                                                    <span class="badge bg-success-subtle text-success small">Applied</span>
                                                </div>
                                                <div class="small text-muted text-truncate mt-0.5 vr-coupon-savings" style="font-size:0.8rem;">
                                                    You save <strong class="text-success vr-coupon-discount-text">{{ format_price($couponDiscount) }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm text-danger border-0 p-1 d-flex align-items-center gap-1 js-coupon-remove-btn" title="Remove coupon" aria-label="Remove coupon">
                                            <i class="ri-delete-bin-line"></i> <span class="d-none d-sm-inline small">Remove</span>
                                        </button>
                                    </div>
                                </div>

                                {{-- Coupon input form --}}
                                <form action="{{ route('cart.coupon') }}" method="POST" class="vr-coupon-form {{ $couponCode ? 'd-none' : '' }}" id="vrCartCouponForm">
                                    @csrf
                                    <div class="vr-coupon-input-group">
                                        <span class="input-icon"><i class="ri-coupon-3-line"></i></span>
                                        <input type="text" name="code" placeholder="Enter coupon code" autocomplete="off" maxlength="50">
                                        <button type="submit" class="vr-coupon-apply-btn js-coupon-apply-btn">
                                            <span class="js-btn-label">Apply</span>
                                            <span class="spinner-border spinner-border-sm d-none js-btn-spinner" role="status" aria-hidden="true"></span>
                                        </button>
                                    </div>
                                    <div class="vr-coupon-feedback mt-1.5 small text-danger d-none" role="alert">
                                        <i class="ri-error-warning-line me-1"></i><span class="vr-coupon-feedback-text"></span>
                                    </div>
                                </form>
                            </div>

                            {{-- Price breakdown table --}}
                            <div class="d-flex justify-content-between small mb-2 text-muted">
                                <span>Subtotal ({{ $count }} {{ Str::plural('item', $count) }}, incl. GST)</span>
                                <span class="text-dark fw-semibold" id="vrSummarySubtotal">{{ format_price($subtotal) }}</span>
                            </div>

                            <div class="d-flex justify-content-between small mb-2 text-success {{ $couponDiscount > 0 ? '' : 'd-none' }}" id="vrCartDiscountRow">
                                <span><i class="ri-price-tag-3-line me-1"></i><span id="vrCartDiscountCode">{{ $couponCode }}</span></span>
                                <span id="vrCartDiscountAmount">-{{ format_price($couponDiscount) }}</span>
                            </div>

                            <div class="d-flex justify-content-between small mb-3 text-muted">
                                <span>Shipping</span>
                                <span>
                                    @if ($shipping['eligible_for_free'] || $shipping['charge'] <= 0)
                                        <span class="text-success fw-bold" id="vrSummaryShipping">FREE</span>
                                    @else
                                        <span class="text-dark fw-semibold" id="vrSummaryShipping">{{ format_price($shipping['charge']) }}</span>
                                    @endif
                                </span>
                            </div>

                            <div style="border-top: 1px dashed #DCE5DD; margin: 16px 0;"></div>

                            {{-- Grand Total --}}
                            <div class="d-flex justify-content-between align-items-baseline mb-1">
                                <span class="fw-bold fs-5 text-dark">Total</span>
                                <span class="fw-bold fs-4" style="color: #1C3C24;" id="vrSummaryTotal">{{ format_price($subtotal - $couponDiscount + ($shipping['eligible_for_free'] ? 0 : ($shipping['charge'] > 0 ? $shipping['charge'] : 0))) }}</span>
                            </div>
                            <div class="text-muted mb-4" style="font-size: 0.78rem;">Total payable is inclusive of GST.</div>

                            {{-- Primary Checkout Button --}}
                            <a href="{{ route('checkout.index') }}" class="vr-btn-checkout js-proceed-checkout" id="vrProceedCheckoutBtn">
                                <i class="ri-lock-fill"></i> Proceed to Checkout <i class="ri-arrow-right-line ms-1"></i>
                            </a>

                            {{-- Trust Badges 3-Col Strip --}}
                            <div class="vr-trust-strip">
                                <div class="row g-2 text-center">
                                    <div class="col-4">
                                        <div class="vr-trust-item">
                                            <i class="ri-shield-check-line"></i>
                                            <span>Secure Payment</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="vr-trust-item">
                                            <i class="ri-hand-coin-line"></i>
                                            <span>COD Available</span>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="vr-trust-item">
                                            <i class="ri-refresh-line"></i>
                                            <span>7-Day Returns</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Why Choose VANRITI Card --}}
                        <div class="vr-why-card">
                            <div class="vr-why-title">
                                <i class="ri-plant-line text-success"></i> Why Choose VANRITI?
                            </div>
                            <div class="vr-why-grid">
                                <div class="vr-why-box">
                                    <div class="vr-why-icon-circle"><i class="ri-leaf-line"></i></div>
                                    <div class="vr-why-label">100% Natural Ingredients</div>
                                </div>
                                <div class="vr-why-box">
                                    <div class="vr-why-icon-circle"><i class="ri-flask-line"></i></div>
                                    <div class="vr-why-label">No Harmful Chemicals</div>
                                </div>
                                <div class="vr-why-box">
                                    <div class="vr-why-icon-circle"><i class="ri-shield-star-line"></i></div>
                                    <div class="vr-why-label">India's Trusted Brand</div>
                                </div>
                                <div class="vr-why-box">
                                    <div class="vr-why-icon-circle"><i class="ri-lock-line"></i></div>
                                    <div class="vr-why-label">Safe & Secure Shopping</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
@if ($viewCartPayload)
    <script nonce="{{ $cspNonce }}">
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({!! json_encode($viewCartPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!});
    </script>
@endif
<script nonce="{{ $cspNonce }}">
(function () {
    var checkoutBtn = document.getElementById('vrProceedCheckoutBtn');
    if (!checkoutBtn) return;
    checkoutBtn.addEventListener('click', function (e) {
        var guestMeta = document.querySelector('meta[name="vr-is-guest"]');
        var isGuest = guestMeta && guestMeta.getAttribute('content') === '1';
        if (isGuest) {
            e.preventDefault();
            if (window.vrLoginModal && typeof window.vrLoginModal.open === 'function') {
                window.vrLoginModal.open({
                    title: '⚡ Fast Checkout with WhatsApp',
                    subtitle: 'Enter your mobile number to checkout in seconds.',
                    purpose: 'fast_checkout',
                    redirect: '{{ route('checkout.index') }}'
                });
            } else {
                window.location.href = checkoutBtn.href;
            }
        }
    });
})();
</script>
@endpush
