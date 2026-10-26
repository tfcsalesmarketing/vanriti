@php
    $freeThreshold = (float) (setting('free_shipping_threshold', 499) ?: 499);
    $subtotal = (float) ($subtotal ?? 0);
    $count = (int) ($count ?? 0);
    $remainingForFree = max(0.0, $freeThreshold - $subtotal);
    $progressPercent = $freeThreshold > 0 ? min(100, round(($subtotal / $freeThreshold) * 100)) : 100;
    $shippingCharge = (float) (setting('shipping_charge', 79) ?: 79);
@endphp

{{-- Free Delivery Goal Bar --}}
<div class="vr-drawer-goal">
    @if ($remainingForFree > 0)
        <div class="vr-goal-header">
            <div class="vr-goal-msg">
                <svg class="vr-goal-truck" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="3" width="15" height="13"></rect>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                    <circle cx="5.5" cy="18.5" r="2.5"></circle>
                    <circle cx="18.5" cy="18.5" r="2.5"></circle>
                </svg>
                <span>Add <strong>{{ format_price($remainingForFree) }}</strong> for <strong class="vr-text-green">FREE Delivery</strong></span>
            </div>
            <span class="vr-goal-percent">{{ $progressPercent }}%</span>
        </div>
        <div class="vr-goal-track">
            <div class="vr-goal-fill" style="width: {{ $progressPercent }}%;"></div>
        </div>
    @else
        <div class="vr-goal-unlocked">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#264a25" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span>✨ You've unlocked <strong class="vr-text-green">FREE Delivery</strong></span>
        </div>
    @endif
</div>

{{-- Cart Items Scrollable List --}}
<div class="vr-drawer-items">
    @if ($cartItems->isEmpty())
        <div class="vr-drawer-empty">
            <div class="vr-drawer-empty-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#8e9a8f" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
            </div>
            <h6 class="vr-empty-title">Your Shopping Bag is Empty</h6>
            <p class="vr-empty-desc">Discover our pure Ayurvedic &amp; herbal care for hair, skin and health crafted with amla, beetroot &amp; natural botanicals.</p>
            <a href="{{ route('shop.index') }}" class="vr-btn-explore" onclick="window.vrCartDrawer && window.vrCartDrawer.close()">
                Explore Collection
            </a>
        </div>
    @else
        <div class="vr-items-list">
            @foreach ($cartItems as $item)
                @php
                    $prod = $item->product;
                    $variant = $item->variant;
                    $img = $variant?->image_path ?: $prod?->getPrimaryImage()?->image_path;
                    $stockLimit = (int) ($variant?->stock ?? $prod?->getAvailableStock() ?? 5);
                    $stockLimit = min(5, max(1, $stockLimit));
                @endphp
                <div class="vr-drawer-item vr-drawer-card" data-item-id="{{ $item->id }}">
                    <a href="{{ $prod ? route('product.show', $prod) : '#' }}" class="vr-drawer-thumb">
                        <img src="{{ image_url($img) }}" alt="{{ $prod?->name ?? 'Product' }}" class="vr-thumb-img">
                    </a>
                    <div class="vr-card-info">
                        <div class="vr-card-top">
                            <a href="{{ $prod ? route('product.show', $prod) : '#' }}" class="vr-item-title" title="{{ $prod?->name ?? 'Product' }}">
                                {{ $prod?->name ?? 'Product' }}
                            </a>
                            <button type="button" class="vr-item-delete js-drawer-remove" data-item-id="{{ $item->id }}" data-remove-url="{{ route('cart.remove', $item->id) }}" aria-label="Remove item" title="Remove">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                            </button>
                        </div>
                        @if ($variant)
                            <div class="vr-item-variant">
                                {{ $variant->name ?: $variant->title }}
                            </div>
                        @endif
                        <div class="vr-card-bottom">
                            {{-- Stepper Capsule --}}
                            <div class="vr-stepper-capsule">
                                <button type="button" class="vr-stepper-btn js-drawer-qty-minus" data-item-id="{{ $item->id }}" data-update-url="{{ route('cart.update', $item->id) }}" aria-label="Decrease">
                                    <i class="ri-subtract-line"></i>
                                </button>
                                <span class="vr-stepper-qty js-drawer-qty-val">{{ $item->quantity }}</span>
                                <button type="button" class="vr-stepper-btn js-drawer-qty-plus" data-item-id="{{ $item->id }}" data-update-url="{{ route('cart.update', $item->id) }}" data-max="{{ $stockLimit }}" aria-label="Increase">
                                    <i class="ri-add-line"></i>
                                </button>
                            </div>
                            <div class="vr-pricing-block">
                                <span class="vr-price-current">{{ format_price($item->subtotal()) }}</span>
                                @if ($item->mrp && $item->mrp > $item->unit_price)
                                    <span class="vr-price-mrp">{{ format_price($item->mrp * $item->quantity) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Footer Summary & Checkout Action --}}
@if ($cartItems->isNotEmpty())
    <div class="vr-drawer-footer">
        {{-- Summary Rounded Card --}}
        <div class="vr-summary-card">
            <div class="vr-summary-row">
                <span class="vr-summary-label">Subtotal</span>
                <span class="vr-summary-val fw-bold">{{ format_price($subtotal) }}</span>
            </div>
            <div class="vr-summary-row">
                <span class="vr-summary-label">Shipping</span>
                @if ($remainingForFree <= 0)
                    <span class="vr-summary-val vr-text-free">FREE</span>
                @else
                    <span class="vr-summary-val">{{ format_price($shippingCharge) }}</span>
                @endif
            </div>
        </div>

        {{-- Proceed to Checkout Button --}}
        <a href="{{ route('checkout.index') }}" class="vr-btn-checkout js-cart-drawer-checkout" id="vrDrawerProceedCheckoutBtn">
            <span class="vr-btn-leaf-motif" aria-hidden="true">
                <svg width="38" height="38" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2.25-13 3.25S2 11.5 2 13.5s1.75 3.75 1.75 3.75C7 8 17 8 17 8z"/>
                </svg>
            </span>
            <span class="vr-btn-text">Proceed to Checkout</span>
            <svg class="vr-btn-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
        </a>

        {{-- View Full Bag & Apply Coupon Button --}}
        <a href="{{ route('cart.index') }}" class="vr-btn-view-bag" onclick="window.vrCartDrawer && window.vrCartDrawer.close()">
            <svg class="vr-btn-tag-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                <line x1="7" y1="7" x2="7.01" y2="7"></line>
            </svg>
            <span>View Full Bag &amp; Apply Coupon</span>
        </a>

        {{-- Trust Badges --}}
        <div class="vr-trust-badges">
            <div class="vr-trust-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#264a25" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    <polyline points="9 12 11 14 15 10"></polyline>
                </svg>
                <span>Secure Payment</span>
            </div>
            <div class="vr-trust-divider"></div>
            <div class="vr-trust-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#264a25" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                <span>7-Day Returns</span>
            </div>
            <div class="vr-trust-divider"></div>
            <div class="vr-trust-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#264a25" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 20A7 7 0 0 1 4 13c0-4.5 4.5-9 8-11 3.5 2 8 6.5 8 11a7 7 0 0 1-7 7z"></path>
                    <path d="M12 2v20"></path>
                </svg>
                <span>100% Authentic</span>
            </div>
        </div>
    </div>
@endif
