@php
    $img = $product->getPrimaryImage()?->image_path;
    $discount = $product->discountPercent();
    $reviewCount = $product->review_count;
    $reviewRating = $product->review_rating;
    $available = $product->getAvailableStock();
    $hasVariants = $product->activeVariants->isNotEmpty();
    $firstVariant = $hasVariants ? $product->activeVariants->first(fn ($v) => (int) $v->stock > 0) : null;
    $out = $available <= 0 || ($hasVariants && $firstVariant === null);
    $low = ! $out && $product->isLowStock();
    $category = $product->categories->first();
    $wishlistService = app(App\Services\WishlistService::class);
    $liked = $wishlistService->has($product);
    $isNew = $product->created_at && $product->created_at->gte(now()->subDays(30));
@endphp

<div class="vr-card">
    <div class="img-wrap">
        @if ($discount > 0)
            <span class="vr-disc-badge">{{ (int) round($discount) }}% OFF</span>
        @endif
        @if ($isNew)
            <span class="vr-new-badge">NEW</span>
        @endif

        <form method="POST" action="{{ route('wishlist.toggle', $product) }}" class="m-0">
            @csrf
            <button type="submit" class="vr-wish-btn {{ $liked ? 'liked' : '' }}" title="{{ $liked ? 'Remove from wishlist' : 'Add to wishlist' }}" aria-label="Toggle wishlist">
                <i class="bi {{ $liked ? 'bi-heart-fill' : 'bi-heart' }}"></i>
            </button>
        </form>

        <a href="{{ route('product.show', $product) }}" class="d-block h-100 w-100" aria-label="{{ $product->name }}">
            @if ($img)
                <img src="{{ image_url($img) }}" alt="{{ $product->name }}" loading="lazy" decoding="async">
            @else
                <div class="d-flex align-items-center justify-content-center h-100 w-100" style="background:var(--vr-green-soft);">
                    <i class="bi bi-image text-secondary display-4"></i>
                </div>
            @endif
        </a>
    </div>

    <div class="card-body">
        @if ($category)
            <div class="cat-name">{{ $category->name }}</div>
        @endif

        <a href="{{ route('product.show', $product) }}" class="p-name d-block mb-1" title="{{ $product->name }}">{{ Str::limit($product->name, 100, '...') }}</a>

        @if ($reviewCount > 0)
            <div class="rating-stars small mb-1">
                @for ($i = 1; $i <= 5; $i++)
                    <i class="bi bi-star{{ $i <= (int) round($reviewRating) ? '-fill' : '' }} {{ $i <= (int) round($reviewRating) ? 'filled' : '' }}"></i>
                @endfor
                <span class="text-muted ms-1">({{ $reviewCount }})</span>
            </div>
        @else
            <div class="rating-stars small mb-1">
                @for ($i = 1; $i <= 5; $i++)
                    <i class="bi bi-star"></i>
                @endfor
                <span class="text-muted ms-1">New</span>
            </div>
        @endif

        <div class="vr-price mt-1">
            <span class="now">{{ format_price($product->selling_price) }}</span>
            @if ($product->mrp > $product->selling_price)
                <span class="mrp">{{ format_price($product->mrp) }}</span>
            @endif
        </div>

        @if ($out)
            <span class="vr-stock-label out mt-1">Out of stock</span>
        @elseif ($low)
            <span class="vr-stock-label in mt-1">Only {{ $available }} left</span>
        @endif

        <form method="POST" action="{{ route('cart.add', $product) }}" class="mt-2">
            @csrf
            @if ($firstVariant)
                <input type="hidden" name="variant_id" value="{{ $firstVariant->id }}">
            @endif
            <input type="hidden" name="quantity" value="1">
            <button type="submit" class="btn btn-vr-outline vr-quick-add d-block w-100 js-add-cart" {{ $out ? 'disabled' : '' }}>
                <i class="bi bi-bag-plus me-1"></i> Add to Cart
            </button>
        </form>
    </div>
</div>