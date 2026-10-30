@extends('tfc.layouts.app')

@section('title', 'Product Catalogue | TFC Sales & Marketing')

@section('content')
<section class="tfc-page-header">
    <div class="container text-center">
        <span class="tfc-badge-gold mb-2 d-inline-block">VANRITI Wellness</span>
        <h1 class="display-5 fw-bold text-white mb-2">Our Product Catalogue</h1>
        <p class="text-white-50 lead mb-0">Certified Ayurvedic Formulations &bull; Authentic Quality</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        {{-- Search & Filter Bar --}}
        <div class="card p-3 mb-5 border-0 shadow-sm rounded-3">
            <form method="GET" action="{{ tfc_route('products') }}" class="row g-3 align-items-center">
                <div class="col-12 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, herbal active, or benefit..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <select name="category" class="form-select">
                        <option value="">All Wellness Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->slug }}" {{ request('category') === $cat->slug ? 'selected' : '' }}>
                                {{ $cat->name }} ({{ $cat->products_count }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-tfc-primary w-100">Filter</button>
                    <a href="{{ tfc_route('products') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        {{-- Product Grid --}}
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
                            <div class="small text-muted mb-1">
                                {{ $product->primaryCategory()?->name ?? 'Ayurvedic Wellness' }}
                            </div>
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
                    <div class="py-5">
                        <i class="bi bi-box-seam text-muted fs-1 mb-3 d-block"></i>
                        <h5 class="fw-bold text-dark">No products found</h5>
                        <p class="text-muted">Try adjusting your search criteria or explore another category.</p>
                        <a href="{{ tfc_route('products') }}" class="btn btn-tfc-navy mt-2">View All Products</a>
                    </div>
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
