@php
    use App\Services\CartService;
    $cartService = app(CartService::class);
    $cart = $cartService->getCart();
    $items = $cartService->items();
    $subtotal = $cartService->subtotal();
    $threshold = (float) setting('free_shipping_threshold', 999);
    $amountLeft = max(0, $threshold - $subtotal);
    $progressPercent = min(100, $threshold > 0 ? round(($subtotal / $threshold) * 100) : 100);
@endphp

<div class="offcanvas offcanvas-end vr-cart-drawer" tabindex="-1" id="vrCartDrawer" aria-labelledby="vrCartDrawerLabel">
    {{-- Header --}}
    <div class="vr-cd-header">
        <div class="vr-cd-header-left">
            <div class="vr-cd-icon-box">
                <i class="ri-shopping-bag-3-line"></i>
            </div>
            <div>
                <h5 class="vr-cd-title" id="vrCartDrawerLabel">Your Shopping Bag</h5>
                <span class="text-muted x-small" style="font-size: 0.72rem;">Handcrafted Natural Skincare</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="vr-cd-count js-cart-drawer-count">{{ $items->sum('quantity') }}</span>
            <button type="button" class="vr-cd-close" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="ri-close-line"></i>
            </button>
        </div>
    </div>

    {{-- Free Shipping Bar --}}
    <div class="vr-cd-shipping">
        <div class="d-flex align-items-center justify-content-between small fw-medium mb-1.5 text-vr-dark" style="font-size: 0.82rem;">
            @if ($amountLeft <= 0)
                <span class="text-success fw-bold d-flex align-items-center gap-1.5">
                    <i class="ri-truck-fill text-success fs-6"></i>
                    <span>Unlocked <strong>FREE Shipping</strong>! 🎉</span>
                </span>
            @else
                <span class="d-flex align-items-center gap-1.5">
                    <i class="ri-truck-line text-vr-gold fs-6"></i>
                    <span>Add <strong class="text-vr-gold">{{ format_price($amountLeft) }}</strong> for <strong>FREE Shipping</strong></span>
                </span>
            @endif
            <span class="text-muted fw-semibold x-small">{{ $progressPercent }}%</span>
        </div>
        <div class="progress vr-progress-bar" style="height: 6px; background: rgba(38, 61, 37, 0.08); border-radius: 0;">
            <div class="progress-bar {{ $amountLeft <= 0 ? 'bg-success' : 'bg-vr-gold' }}"
                 role="progressbar"
                 style="width: {{ $progressPercent }}%; transition: width 0.6s cubic-bezier(0.16, 1, 0.3, 1);"
                 aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>

    {{-- Body --}}
    <div class="offcanvas-body p-3 overflow-auto">
        <div class="js-cart-drawer-items">
            @if ($items->isEmpty())
                <div class="text-center py-5 my-3">
                    <div class="mb-3 position-relative d-inline-block">
                        <div class="d-flex align-items-center justify-content-center mx-auto" style="width: 84px; height: 84px; background: rgba(38, 61, 37, 0.05); color: var(--vr-green-dark);">
                            <i class="ri-shopping-bag-line display-4" style="opacity:0.6;"></i>
                        </div>
                    </div>
                    <h5 class="vr-serif fw-bold mb-2 text-vr-dark">Your bag is empty</h5>
                    <p class="text-muted small mb-4 mx-auto" style="max-width: 260px;">Discover our pure, natural skincare and herbal essentials.</p>
                    <a href="{{ route('shop.index') }}" class="btn btn-vr-forest rounded-0 px-4 py-2.5 shadow-sm" data-bs-dismiss="offcanvas">
                        <i class="ri-store-2-line me-1.5"></i> Start Shopping
                    </a>
                </div>
            @else
                <div class="d-flex flex-column gap-3">
                    @foreach ($items as $item)
                        @php
                            $product = $item->product;
                            $variant = $item->variant;
                            $primaryImg = $product?->getPrimaryImage()?->image_path;
                        @endphp
                        @if ($product)
                            <div class="vr-cart-drawer-item d-flex gap-3 p-2.5 bg-white border position-relative shadow-2xs" style="border-color: rgba(0,0,0,0.08) !important; border-radius: 0;">
                                <div class="vr-cart-item-img flex-shrink-0 border" style="width: 76px; height: 76px; background: #FAF9F6; border-radius: 0;">
                                    @if ($primaryImg)
                                        <img src="{{ image_url($primaryImg) }}" alt="{{ $product->name }}" class="w-100 h-100 object-fit-cover">
                                    @else
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                            <i class="ri-image-line text-muted fs-5"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-grow-1 min-w-0 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                            <a href="{{ route('product.show', $product) }}" class="text-dark fw-semibold text-truncate small d-block hover-gold text-decoration-none" title="{{ $product->name }}" style="line-height: 1.3;">
                                                {{ $product->name }}
                                            </a>
                                            <form method="POST" action="{{ route('cart.remove', $item->id) }}" class="m-0 js-cart-remove-form">
                                                @csrf
                                                <button type="submit" class="btn p-0 text-muted hover-danger border-0 bg-transparent opacity-75" title="Remove item">
                                                    <i class="ri-delete-bin-line small"></i>
                                                </button>
                                            </form>
                                        </div>

                                        @if ($variant)
                                            <span class="badge bg-light text-muted border font-monospace fw-normal x-small py-0.5 px-2 mb-1" style="border-radius: 0;">{{ $variant->name }}</span>
                                        @endif
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <div class="vr-drawer-qty-wrap d-flex align-items-center border px-1 py-0.5" style="background:#F7F6F2; border-color: rgba(0,0,0,0.12) !important; border-radius: 0;">
                                            <form method="POST" action="{{ route('cart.update', $item->id) }}" class="d-flex align-items-center m-0 js-drawer-qty-form">
                                                @csrf
                                                <button type="button" class="btn btn-sm p-1 text-dark js-drawer-step" data-step="-1" style="line-height:1; min-width: 24px;"><i class="ri-subtract-line small"></i></button>
                                                <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="5" readonly class="form-control form-control-sm border-0 bg-transparent text-center px-1 py-0 fw-bold text-vr-dark" style="width: 28px; font-size:0.82rem;">
                                                <button type="button" class="btn btn-sm p-1 text-dark js-drawer-step" data-step="1" style="line-height:1; min-width: 24px;"><i class="ri-add-line small"></i></button>
                                            </form>
                                        </div>
                                        <div class="text-end">
                                            <span class="fw-bold text-vr-forest fs-6">{{ format_price($item->total_price) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Footer --}}
    @if (! $items->isEmpty())
        <div class="offcanvas-footer p-3 border-top bg-white shadow-lg" style="border-color: rgba(0,0,0,0.06) !important;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="text-muted small">Subtotal</span>
                <span class="fw-bold text-vr-dark fs-5 vr-serif js-cart-drawer-subtotal">{{ format_price($subtotal) }}</span>
            </div>
            <p class="x-small text-muted mb-3 text-end" style="font-size: 0.72rem;">Taxes & free shipping calculated at checkout.</p>
            <div class="d-grid gap-2">
                <a href="{{ route('checkout.index') }}" class="btn btn-vr-forest rounded-pill py-2.5 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-2" style="font-size: 0.95rem;">
                    <span>Proceed to Checkout</span>
                    <i class="ri-arrow-right-line"></i>
                </a>
                <a href="{{ route('cart.index') }}" class="btn btn-outline-dark rounded-pill py-2 small fw-medium text-center text-decoration-none">
                    View Full Cart
                </a>
            </div>
            <div class="d-flex align-items-center justify-content-center gap-3 mt-3 text-muted x-small pt-2 border-top" style="font-size: 0.72rem;">
                <span class="d-flex align-items-center gap-1"><i class="ri-shield-check-line text-success"></i> 100% Secure</span>
                <span>•</span>
                <span class="d-flex align-items-center gap-1"><i class="ri-leaf-line text-vr-gold"></i> Pure Ingredients</span>
            </div>
        </div>
    @endif
</div>

