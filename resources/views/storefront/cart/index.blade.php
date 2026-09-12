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
                            <div class="vr-cat-card p-3" data-unit-price="{{ $item->unit_price }}">
                                <div class="d-flex gap-3 align-items-start">
                                    <a href="{{ route('product.show', $item->product->slug) }}" class="flex-shrink-0">
                                        <img src="{{ image_url($item->product->getPrimaryImage()?->image_path, 'images/placeholder.png') }}"
                                             alt="{{ $item->product->name }}"
                                             style="width:90px;height:90px;object-fit:cover;border-radius:14px;background:var(--vr-cream);">
                                    </a>

                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                            <div>
                                                <a href="{{ route('product.show', $item->product->slug) }}" class="fw-semibold text-dark text-decoration-none d-block text-truncate" style="max-width:280px;">
                                                    {{ $item->product->name }}
                                                </a>
                                                @if ($item->variant)
                                                    <span class="small text-muted d-block">{{ $item->variant->name }}</span>
                                                @endif
                                            </div>
                                            <div class="text-end flex-shrink-0">
                                                <div class="fw-bold fs-6">{{ format_price($item->unit_price) }}</div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                            <form action="{{ route('cart.update', $item) }}" method="POST" data-auto-qty>
                                                @csrf
                                                <div class="vr-qty">
                                                    <button type="button" class="qty-minus" data-step="-1" aria-label="Decrease quantity" {{ $item->quantity <= 1 ? 'disabled' : '' }}><i class="ri-subtract-line"></i></button>
                                                    <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="5" readonly aria-label="Quantity">
                                                    <button type="button" class="qty-plus" data-step="1" aria-label="Increase quantity" {{ $item->quantity >= 5 ? 'disabled' : '' }}><i class="ri-add-line"></i></button>
                                                </div>
                                            </form>

                                            <div class="d-flex align-items-center gap-3">
                                                <span class="fw-bold text-dark vr-line-total">{{ format_price($item->unit_price * $item->quantity) }}</span>
                                                <form action="{{ route('cart.remove', $item) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-link text-muted p-0" title="Remove" aria-label="Remove {{ $item->product->name }}">
                                                        <i class="ri-delete-bin-line fs-5 text-danger"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="{{ route('shop.index') }}" class="vr-app-outline-btn py-2 px-3 text-decoration-none">
                            <i class="ri-arrow-left-line me-1"></i> Continue Shopping
                        </a>
                        <form action="{{ route('cart.clear') }}" method="POST" onsubmit="return confirm('Clear all items from your cart?')">
                            @csrf
                            <button type="submit" class="btn btn-sm text-danger p-0">
                                <i class="ri-delete-bin-line me-1"></i> Clear Cart
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Summary Sidebar --}}
                <div class="col-lg-4">
                    <div class="vr-guest-card p-4 sticky-top" style="top: 100px;">
                        <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                            <i class="ri-file-list-3-line text-success"></i> Order Summary
                        </h5>

                        <form action="{{ route('cart.coupon') }}" method="POST" class="d-flex gap-2 mb-3">
                            @csrf
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="ri-coupon-3-line text-muted"></i></span>
                                <input type="text" name="code" class="form-control form-control-sm border-start-0" placeholder="Coupon code"
                                       value="{{ $couponCode ?? '' }}">
                            </div>
                            <button type="submit" class="btn vr-app-outline-btn btn-sm text-nowrap">Apply</button>
                        </form>

                        <hr class="my-3">

                        <div class="d-flex justify-content-between small mb-2 text-muted">
                            <span>Subtotal ({{ $count }} items, incl. GST)</span>
                            <span class="text-dark fw-semibold" id="vrSummarySubtotal">{{ format_price($subtotal) }}</span>
                        </div>

                        @if ($couponDiscount > 0)
                            <div class="d-flex justify-content-between small mb-2 text-success">
                                <span><i class="ri-price-tag-3-line me-1"></i>{{ $couponCode }}</span>
                                <span>-{{ format_price($couponDiscount) }}</span>
                            </div>
                        @endif

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

                        <a href="{{ route('checkout.index') }}" class="btn vr-app-btn w-100 py-3 mb-3">
                            <i class="ri-lock-2-line me-2"></i> Proceed to Checkout
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

@if ($viewCartPayload)
    @push('scripts')
    <script>
    window.dataLayer.push({!! json_encode($viewCartPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!});
    </script>
    @endpush
@endif
