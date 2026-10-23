@extends('storefront.layouts.app')
@section('title', 'Shopping Cart')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-section py-4 py-lg-5">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="vr-section-title mb-1">Shopping Cart</h1>
                <p class="text-muted small mb-0">Review your items before checkout</p>
            </div>
            @if ($cartItems->isNotEmpty())
                <span class="vr-type-chip">{{ $count }} {{ Str::plural('item', $count) }}</span>
            @endif
        </div>

        @if ($cartItems->isEmpty())
            <div class="vr-guest-card text-center py-5 mx-auto" style="max-width: 500px;">
                <div class="vr-guest-icon-box mb-3" style="width: 72px; height: 72px; font-size: 2.5rem;">
                    <i class="ri-shopping-bag-line"></i>
                </div>
                <h4 class="fw-bold mb-2">Your Cart is Empty</h4>
                <p class="text-muted small mb-4">Looks like you haven't added anything to your cart yet.</p>
                <a href="{{ route('shop.index') }}" class="btn vr-app-btn px-4 py-2">
                    <i class="ri-compass-line me-2"></i> Start Shopping
                </a>
            </div>
        @else
            {{-- Free Shipping Banner --}}
            @php
                $freeThreshold = $shipping['free_threshold'] ?? 499;
                $diff = max(0, $freeThreshold - $subtotal);
                $pct = min(100, round(($subtotal / $freeThreshold) * 100));
            @endphp
            <div class="vr-shipping-banner" data-free-threshold="{{ $freeThreshold }}">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2 small fw-semibold">
                        <i class="ri-truck-line text-success fs-5"></i>
                        @if ($diff <= 0)
                            <span class="text-success">Congratulations! You've unlocked FREE Shipping!</span>
                        @else
                            <span>Add <strong>{{ format_price($diff) }}</strong> more to get <strong>FREE Shipping</strong></span>
                        @endif
                    </div>
                    <span class="small fw-bold">{{ $pct }}%</span>
                </div>
                <div class="vr-progress-bar-wrap">
                    <div class="vr-progress-bar-fill" style="width: {{ $pct }}%;"></div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="d-flex flex-column gap-3">
                        @foreach ($cartItems as $item)
                            <div class="vr-cat-card vr-cart-item-card" data-unit-price="{{ $item->unit_price }}">
                                <div class="vr-cart-item-main">
                                    <a href="{{ route('product.show', $item->product->slug) }}" class="vr-cart-item-thumb">
                                        <img src="{{ image_url($item->product->getPrimaryImage()?->image_path, 'images/placeholder.png') }}"
                                             alt="{{ $item->product->name }}"
                                             class="vr-cart-item-img"
                                             loading="lazy">
                                    </a>

                                    <div class="vr-cart-item-info">
                                        <div class="vr-cart-item-top">
                                            <div class="vr-cart-item-heading">
                                                <a href="{{ route('product.show', $item->product->slug) }}" class="vr-cart-item-title" title="{{ $item->product->name }}">
                                                    {{ $item->product->name }}
                                                </a>
                                                @if ($item->variant)
                                                    <span class="vr-cart-item-variant">{{ $item->variant->name }}</span>
                                                @endif
                                            </div>

                                            <form action="{{ route('cart.remove', $item) }}" method="POST" class="vr-cart-item-remove-form">
                                                @csrf
                                                <button type="submit" class="vr-cart-item-remove-btn" title="Remove {{ $item->product->name }}" aria-label="Remove {{ $item->product->name }}">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <div class="vr-cart-item-price-row">
                                            <span class="vr-cart-item-unit-label">Price:</span>
                                            <span class="vr-cart-item-unit-price">{{ format_price($item->unit_price) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="vr-cart-item-footer">
                                    <form action="{{ route('cart.update', $item) }}" method="POST" data-auto-qty="1" class="vr-cart-item-qty-form">
                                        @csrf
                                        <div class="vr-qty">
                                            <button type="button" class="qty-minus {{ $item->quantity <= 1 ? 'is-remove text-danger' : '' }}" data-step="-1" aria-label="{{ $item->quantity <= 1 ? 'Remove from cart' : 'Decrease quantity' }}" title="{{ $item->quantity <= 1 ? 'Remove from cart' : 'Decrease quantity' }}">
                                                <i class="{{ $item->quantity <= 1 ? 'ri-delete-bin-line' : 'bi bi-dash' }}"></i>
                                            </button>
                                            <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="5" readonly aria-label="Quantity">
                                            <button type="button" class="qty-plus" data-step="1" aria-label="Increase quantity" {{ $item->quantity >= 5 ? 'disabled' : '' }}><i class="bi bi-plus"></i></button>
                                        </div>
                                    </form>

                                    <div class="vr-cart-item-total">
                                        <span class="vr-cart-item-total-label">Subtotal</span>
                                        <span class="vr-line-total">{{ format_price($item->unit_price * $item->quantity) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2.5 mt-4">
                        <a href="{{ route('shop.index') }}" class="vr-app-outline-btn py-2 px-3 text-decoration-none text-center w-100 w-sm-auto">
                            <i class="ri-arrow-left-line me-1"></i> Continue Shopping
                        </a>
                        <form action="{{ route('cart.clear') }}" method="POST" data-confirm="Clear all items from your cart?" class="w-100 w-sm-auto text-center">
                            @csrf
                            <button type="submit" class="btn btn-sm text-danger p-1">
                                <i class="ri-delete-bin-line me-1"></i> Clear Cart
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Summary Sidebar --}}
                <div class="col-lg-4">
                    <div class="vr-guest-card p-3 p-sm-4 sticky-top" style="top: 100px;">
                        <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                            <i class="ri-file-list-3-line text-success"></i> Order Summary
                        </h5>

                        <div class="vr-coupon-widget mb-3" id="vrCartCouponWidget" data-url="{{ route('cart.coupon') }}">
                            {{-- Applied state --}}
                            <div class="vr-applied-coupon-card p-2.5 rounded-3 border border-success-subtle {{ $couponCode ? '' : 'd-none' }}" id="vrCartAppliedCouponCard" style="background: rgba(22, 101, 52, 0.05);">
                                <div class="d-flex align-items-center justify-content-between gap-2">
                                    <div class="d-flex align-items-center gap-2 min-w-0">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-success flex-shrink-0" style="width:32px;height:32px;background:rgba(22, 101, 52, 0.12);">
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

                            {{-- Input form state --}}
                            <form action="{{ route('cart.coupon') }}" method="POST" class="vr-coupon-form {{ $couponCode ? 'd-none' : '' }}" id="vrCartCouponForm">
                                @csrf
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0"><i class="ri-coupon-3-line text-muted"></i></span>
                                    <input type="text" name="code" class="form-control form-control-sm border-start-0 text-uppercase" placeholder="Enter coupon code" autocomplete="off" maxlength="50">
                                    <button type="submit" class="btn vr-app-btn btn-sm text-nowrap js-coupon-apply-btn">
                                        <span class="js-btn-label">Apply</span>
                                        <span class="spinner-border spinner-border-sm d-none js-btn-spinner" role="status" aria-hidden="true"></span>
                                    </button>
                                </div>
                                <div class="vr-coupon-feedback mt-1.5 small text-danger d-none" role="alert">
                                    <i class="ri-error-warning-line me-1"></i><span class="vr-coupon-feedback-text"></span>
                                </div>
                            </form>
                        </div>

                        <hr class="my-3">

                        <div class="d-flex justify-content-between small mb-2 text-muted">
                            <span>Subtotal ({{ $count }} items, incl. GST)</span>
                            <span class="text-dark fw-semibold" id="vrSummarySubtotal">{{ format_price($subtotal) }}</span>
                        </div>

                        <div class="d-flex justify-content-between small mb-2 text-success {{ $couponDiscount > 0 ? '' : 'd-none' }}" id="vrCartDiscountRow">
                            <span><i class="ri-price-tag-3-line me-1"></i><span id="vrCartDiscountCode">{{ $couponCode }}</span></span>
                            <span id="vrCartDiscountAmount">-{{ format_price($couponDiscount) }}</span>
                        </div>

                        <div class="d-flex justify-content-between small mb-2 text-muted">
                            <span>Shipping</span>
                            <span>
                                @if ($shipping['eligible_for_free'] || $shipping['charge'] <= 0)
                                    <span class="text-success fw-semibold" id="vrSummaryShipping">FREE</span>
                                @else
                                    <span class="text-dark fw-semibold" id="vrSummaryShipping">{{ format_price($shipping['charge']) }}</span>
                                @endif
                            </span>
                        </div>

                        <hr class="my-3">

                        <div class="d-flex justify-content-between fw-bold fs-5 mb-1">
                            <span>Total</span>
                            <span class="text-success" id="vrSummaryTotal">{{ format_price($subtotal - $couponDiscount + ($shipping['eligible_for_free'] ? 0 : ($shipping['charge'] > 0 ? $shipping['charge'] : 0))) }}</span>
                        </div>
                        <div class="small text-muted mb-4">Total payable is inclusive of GST.</div>

                        <a href="{{ route('checkout.index') }}" class="btn vr-app-btn w-100 py-3 mb-3 js-proceed-checkout" id="vrProceedCheckoutBtn">
                            <i class="ri-flashlight-line me-2 text-warning"></i> Proceed to Checkout
                        </a>

                        <div class="p-3 bg-light rounded-3 text-center small text-muted">
                            <div class="d-flex justify-content-center gap-3 mb-1">
                                <span><i class="ri-shield-check-line text-success me-1"></i> Secure</span>
                                <span><i class="ri-hand-coin-line text-success me-1"></i> COD</span>
                                <span><i class="ri-refresh-line text-success me-1"></i> {{ setting('return_window_days', 7) }}-Day Returns</span>
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
