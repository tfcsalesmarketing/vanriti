@extends('tfc.layouts.app')

@section('title', $category->name . ' | TFC Sales & Marketing')

@section('content')
<section class="tfc-page-header py-5" style="background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%); border-bottom: 1px solid rgba(0,0,0,0.06);">
    <div class="container text-center py-2">
        <span class="badge bg-warning text-dark fw-bold mb-2 px-3 py-1.5" style="font-size: 0.8rem; letter-spacing: 0.5px;">WELLNESS CATEGORY</span>
        <h1 class="display-5 fw-bold text-dark mb-2">{{ $category->name }}</h1>
        @if ($category->description)
            <p class="text-muted lead mb-0" style="max-width: 650px; margin: 0 auto;">{{ $category->description }}</p>
        @endif
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            @forelse ($products as $product)
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card tfc-card h-100">
                        <div class="position-relative overflow-hidden">
                            @php
                                $img = $product->getPrimaryImage()?->image_path;
                            @endphp
                            <a href="{{ tfc_route('products.show', $product->slug) }}">
                                @if ($img)
                                    <img src="{{ image_url($img) }}" alt="{{ $product->name }}" class="tfc-product-img">
                                @else
                                    <div class="tfc-product-img bg-light d-flex align-items-center justify-content-center text-muted">
                                        <i class="bi bi-image fs-1"></i>
                                    </div>
                                @endif
                            </a>
                            @if ($product->discountPercent() > 0)
                                <span class="badge bg-danger position-absolute top-0 start-0 m-2 px-2 py-1">
                                    {{ round($product->discountPercent()) }}% OFF
                                </span>
                            @endif
                        </div>
                        <div class="card-body d-flex flex-column p-4">
                            <h6 class="fw-bold mb-2">
                                <a href="{{ tfc_route('products.show', $product->slug) }}" class="text-dark text-decoration-none">
                                    {{ $product->name }}
                                </a>
                            </h6>
                            <div class="mt-auto pt-3 border-top d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="tfc-price">{{ format_price($product->selling_price) }}</span>
                                    @if ($product->mrp && $product->mrp > $product->selling_price)
                                        <span class="tfc-mrp">{{ format_price($product->mrp) }}</span>
                                    @endif
                                </div>
                                <a href="{{ tfc_route('products.show', $product->slug) }}" class="btn btn-sm btn-tfc-primary">
                                    Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <p class="text-muted">No products currently in this category.</p>
                    <a href="{{ tfc_route('products') }}" class="btn btn-tfc-navy mt-2">Browse All Products</a>
                </div>
            @endforelse
        </div>

        @if ($products->hasPages())
            <div class="d-flex justify-content-center mt-5">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</section>
@endsection
