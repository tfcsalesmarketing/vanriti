@extends('storefront.layouts.app')
@section('title', 'My Wishlist')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-section py-4 py-lg-5">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="vr-section-title mb-1">My Wishlist</h1>
                <p class="text-muted small mb-0">Items you've saved for later</p>
            </div>
            @if ($wishlistItems->isNotEmpty())
                <span class="vr-type-chip">{{ $wishlistItems->count() }} {{ Str::plural('item', $wishlistItems->count()) }}</span>
            @endif
        </div>

        @if ($wishlistItems->isEmpty())
            <div class="vr-guest-card text-center py-5 max-w-lg mx-auto" style="max-width: 500px;">
                <div class="vr-guest-icon-box mb-3" style="width: 72px; height: 72px; font-size: 2.5rem;">
                    <i class="ri-heart-line"></i>
                </div>
                <h4 class="fw-bold mb-2">Your Wishlist is Empty</h4>
                <p class="text-muted small mb-4">Save products you love to your wishlist and revisit them anytime.</p>
                <a href="{{ route('shop.index') }}" class="btn vr-app-btn px-4 py-2">
                    <i class="ri-compass-line me-2"></i> Discover Products
                </a>
            </div>
        @else
            <div class="row g-3 g-md-4">
                @foreach ($wishlistItems as $product)
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="vr-wishlist-card h-100 d-flex flex-column">
                            <div class="vr-wishlist-img">
                                <a href="{{ route('product.show', $product->slug) }}">
                                    <img src="{{ image_url($product->getPrimaryImage()?->image_path, 'images/placeholder.png') }}"
                                         alt="{{ $product->name }}">
                                </a>
                                @if ($product->mrp > $product->selling_price)
                                    @php
                                        $discountPct = round((($product->mrp - $product->selling_price) / $product->mrp) * 100);
                                    @endphp
                                    <span class="vr-dock-badge position-absolute top-2 start-2 bg-danger" style="animation:none;">-{{ $discountPct }}%</span>
                                @endif
                            </div>
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <a href="{{ route('product.show', $product->slug) }}" class="fw-semibold text-dark text-truncate d-block mb-1">
                                    {{ $product->name }}
                                </a>
                                
                                <div class="vr-price mb-3">
                                    <span class="now fw-bold text-dark fs-6">{{ format_price($product->selling_price) }}</span>
                                    @if ($product->mrp > $product->selling_price)
                                        <span class="mrp text-muted small text-decoration-line-through ms-1">{{ format_price($product->mrp) }}</span>
                                    @endif
                                </div>

                                <div class="mt-auto d-flex gap-2">
                                    <form action="{{ route('cart.add', $product) }}" method="POST" class="flex-grow-1">
                                        @csrf
                                        <input type="hidden" name="quantity" value="1">
                                        <input type="hidden" name="remove_from_wishlist" value="1">
                                        <button type="submit" class="btn vr-app-btn btn-sm w-100 py-2">
                                            <i class="ri-shopping-bag-3-line me-1"></i> Add
                                        </button>
                                    </form>
                                    <form action="{{ route('wishlist.toggle', $product) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn vr-app-outline-btn btn-sm py-2 px-2" title="Remove from wishlist">
                                            <i class="ri-delete-bin-line text-danger"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
