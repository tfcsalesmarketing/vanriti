@php
    $freeThreshold = (float) (setting('free_shipping_threshold', 499) ?: 499);
    $subtotal = (float) ($subtotal ?? 0);
    $count = (int) ($count ?? 0);
    $remainingForFree = max(0.0, $freeThreshold - $subtotal);
    $progressPercent = $freeThreshold > 0 ? min(100, round(($subtotal / $freeThreshold) * 100)) : 100;
@endphp

{{-- Free Delivery Goal Bar --}}
<div class="vr-drawer-goal p-3 border-bottom">
    @if ($remainingForFree > 0)
        <div class="d-flex align-items-center justify-content-between small mb-1.5">
            <span class="text-dark fw-medium">
                <i class="bi bi-truck text-success me-1"></i> Add <strong>{{ format_price($remainingForFree) }}</strong> for <strong>FREE Delivery</strong>
            </span>
            <span class="text-muted small">{{ $progressPercent }}%</span>
        </div>
        <div class="progress vr-drawer-progress" style="height: 6px; background-color: #E8ECE0; border-radius: 99px;">
            <div class="progress-bar" role="progressbar" style="width: {{ $progressPercent }}%; background: linear-gradient(90deg, #6F8736 0%, #263D25 100%); border-radius: 99px;" aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    @else
        <div class="d-flex align-items-center gap-2 text-success small fw-semibold">
            <i class="bi bi-check-circle-fill fs-6"></i>
            <span>✨ Congratulations! You've unlocked <strong>FREE Delivery</strong></span>
        </div>
    @endif
</div>

{{-- Cart Items Scrollable List --}}
<div class="vr-drawer-items flex-grow-1 overflow-y-auto p-3">
    @if ($cartItems->isEmpty())
        <div class="text-center py-5">
            <div class="vr-drawer-empty-icon mb-3">
                <i class="bi bi-bag-x text-muted" style="font-size: 3rem; opacity: 0.4;"></i>
            </div>
            <h6 class="fw-semibold text-dark mb-1">Your Luxury Bag is Empty</h6>
            <p class="text-muted small mb-4">Discover our curated herbal wellness rituals crafted with pure botanical extracts.</p>
            <a href="{{ route('shop.index') }}" class="btn btn-vr btn-sm px-4 py-2" onclick="window.vrCartDrawer && window.vrCartDrawer.close()">
                Explore Collection
            </a>
        </div>
    @else
        <div class="d-flex flex-column gap-3">
            @foreach ($cartItems as $item)
                @php
                    $prod = $item->product;
                    $variant = $item->variant;
                    $img = $variant?->image_path ?: $prod?->getPrimaryImage()?->image_path;
                    $stockLimit = (int) ($variant?->stock ?? $prod?->getAvailableStock() ?? 5);
                    $stockLimit = min(5, max(1, $stockLimit));
                @endphp
                <div class="vr-drawer-item d-flex gap-3 pb-3 border-bottom position-relative" data-item-id="{{ $item->id }}">
                    <a href="{{ $prod ? route('product.show', $prod) : '#' }}" class="vr-drawer-thumb flex-shrink-0" style="width: 72px; height: 72px; background: #F7F4EA; border-radius: 10px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                        <img src="{{ image_url($img) }}" alt="{{ $prod?->name ?? 'Product' }}" class="img-fluid" style="object-fit: contain; max-height: 100%; max-width: 100%;">
                    </a>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex justify-content-between align-items-start gap-1">
                            <a href="{{ $prod ? route('product.show', $prod) : '#' }}" class="text-dark fw-semibold small text-truncate d-block text-decoration-none" title="{{ $prod?->name ?? 'Product' }}">
                                {{ $prod?->name ?? 'Product' }}
                            </a>
                            <button type="button" class="btn btn-link text-muted p-0 border-0 ms-1 js-drawer-remove" data-item-id="{{ $item->id }}" data-remove-url="{{ route('cart.remove', $item->id) }}" aria-label="Remove item" title="Remove">
                                <i class="bi bi-trash3 small"></i>
                            </button>
                        </div>
                        @if ($variant)
                            <div class="text-muted" style="font-size: 0.75rem;">
                                {{ $variant->name ?: $variant->title }}
                            </div>
                        @endif
                        <div class="d-flex align-items-center justify-content-between mt-2">
                            {{-- Stepper --}}
                            <div class="vr-drawer-qty-stepper d-inline-flex align-items-center border rounded-pill px-2 py-0.5 bg-white">
                                <button type="button" class="btn btn-link text-dark p-0 border-0 js-drawer-qty-minus" data-item-id="{{ $item->id }}" data-update-url="{{ route('cart.update', $item->id) }}" aria-label="Decrease">
                                    <i class="bi bi-dash" style="font-size: 0.85rem;"></i>
                                </button>
                                <span class="px-2 fw-semibold small js-drawer-qty-val">{{ $item->quantity }}</span>
                                <button type="button" class="btn btn-link text-dark p-0 border-0 js-drawer-qty-plus" data-item-id="{{ $item->id }}" data-update-url="{{ route('cart.update', $item->id) }}" data-max="{{ $stockLimit }}" aria-label="Increase">
                                    <i class="bi bi-plus" style="font-size: 0.85rem;"></i>
                                </button>
                            </div>
                            <div class="text-end">
                                <span class="fw-semibold text-dark small">{{ format_price($item->subtotal()) }}</span>
                                @if ($item->mrp && $item->mrp > $item->unit_price)
                                    <span class="text-muted text-decoration-line-through d-block" style="font-size: 0.70rem;">{{ format_price($item->mrp * $item->quantity) }}</span>
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
    <div class="vr-drawer-footer p-3 bg-light-subtle border-top mt-auto">
        <div class="d-flex justify-content-between align-items-center mb-1.5">
            <span class="text-muted small">Subtotal</span>
            <span class="fw-bold text-dark fs-6">{{ format_price($subtotal) }}</span>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted small">Shipping</span>
            @if ($remainingForFree <= 0)
                <span class="text-success fw-semibold small">FREE</span>
            @else
                <span class="text-muted small">{{ format_price(setting('shipping_charge', 49)) }}</span>
            @endif
        </div>

        <a href="{{ route('checkout.index') }}" class="btn btn-vr btn-lg w-100 mb-2 d-flex align-items-center justify-content-center gap-2 py-2.5 shadow-sm">
            <span>Proceed to Checkout</span>
            <i class="bi bi-arrow-right"></i>
        </a>
        <div class="text-center">
            <a href="{{ route('cart.index') }}" class="text-muted small text-decoration-underline" onclick="window.vrCartDrawer && window.vrCartDrawer.close()">
                View Full Bag &amp; Apply Coupon
            </a>
        </div>

        <div class="d-flex align-items-center justify-content-center gap-3 mt-3 pt-2 border-top text-muted" style="font-size: 0.70rem;">
            <span><i class="bi bi-shield-check text-success me-1"></i>Secure Payment</span>
            <span><i class="bi bi-arrow-repeat text-success me-1"></i>7-Day Returns</span>
            <span><i class="bi bi-patch-check text-success me-1"></i>100% Authentic</span>
        </div>
    </div>
@endif
