@extends('storefront.layouts.app')

@section('title', $query !== '' ? 'Search results for "'.$query.'"' : 'Shop')

@section('content')

<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@@type": "ListItem",
            "position": 1,
            "name": {!! json_encode(store_name(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
            "item": {!! json_encode(route('home'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        },
        {
            "@@type": "ListItem",
            "position": 2,
            "name": "Shop",
            "item": {!! json_encode(route('shop.index'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        }
    ]
}
</script>
@if ($products->isNotEmpty())
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "ItemList",
    "name": @if ($query !== ''){!! json_encode('Search results for "'.$query.'"', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}@else"All Products"@endif,
    "url": {!! json_encode(route('shop.index'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
    "itemListElement": [
@foreach ($products as $i => $item)
        {
            "@@type": "ListItem",
            "position": {{ $i + 1 }},
            "url": {!! json_encode(route('product.show', $item), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
            "name": {!! json_encode($item->name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        }@if (! $loop->last),@endif
@endforeach
    ]
}
</script>
@endif

<div class="container py-4">
    <div class="mb-4">
        <h1 class="h4 fw-bold mb-1" style="color:var(--vr-green-dark);">
            @if ($query !== '')
                Search results for '{{ $query }}'
            @else
                Shop
            @endif
            @if ($isFeatured)
                <span class="vr-pill-badge ps-pending ms-2" style="font-size:0.7rem;">Featured</span>
            @endif
        </h1>
        <p class="text-muted small mb-0">{{ $products->total() }} product{{ $products->total() === 1 ? '' : 's' }}</p>
    </div>

    <div class="row g-4">
        <aside class="col-lg-3 d-lg-block">
            <div class="offcanvas offcanvas-start" tabindex="-1" id="shopFilters" aria-labelledby="shopFiltersLabel">
                <div class="offcanvas-header">
                    <h6 class="offcanvas-title fw-bold" id="shopFiltersLabel">Filters</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <div class="vr-shop-sidebar">
                        <form method="GET" action="{{ route('shop.index') }}">
                            @if ($query !== '')
                                <input type="hidden" name="q" value="{{ $query }}">
                            @endif
                            @if ($isFeatured)
                                <input type="hidden" name="is_featured" value="1">
                            @endif

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Search</label>
                                <input type="text" name="q" value="{{ $query }}" class="form-control form-control-sm" placeholder="Search products...">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Categories</label>
                                @foreach ($categories as $cat)
                                    <div class="form-check small mb-1">
                                        <input class="form-check-input" type="checkbox" name="category[]" value="{{ $cat->id }}" id="cat-{{ $cat->id }}" @checked(in_array($cat->id, $selectedCategories))>
                                        <label class="form-check-label" for="cat-{{ $cat->id }}">{{ $cat->name }}</label>
                                    </div>
                                    @foreach ($cat->children as $child)
                                        <div class="form-check small mb-1 ms-3">
                                            <input class="form-check-input" type="checkbox" name="category[]" value="{{ $child->id }}" id="cat-{{ $child->id }}" @checked(in_array($child->id, $selectedCategories))>
                                            <label class="form-check-label" for="cat-{{ $child->id }}">{{ $child->name }}</label>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Price Range</label>
                                <div class="d-flex gap-2">
                                    <input type="number" name="min_price" value="{{ $minPrice }}" class="form-control form-control-sm" placeholder="Min">
                                    <input type="number" name="max_price" value="{{ $maxPrice }}" class="form-control form-control-sm" placeholder="Max">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Sort By</label>
                                <select name="sort" class="form-select form-select-sm">
                                    <option value="" @selected($sort === '' || $sort === 'newest')>Newest</option>
                                    <option value="price_asc" @selected($sort === 'price_asc')>Price: Low to High</option>
                                    <option value="price_desc" @selected($sort === 'price_desc')>Price: High to Low</option>
                                    <option value="popular" @selected($sort === 'popular')>Popular</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-vr btn-sm w-100 mb-2">Apply Filters</button>
                            <a href="{{ route('shop.index') }}" class="btn btn-vr-outline btn-sm w-100">Clear All</a>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <main class="col-lg-9">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <p class="small text-muted mb-0">
                    {{ $products->total() }} product{{ $products->total() === 1 ? '' : 's' }}
                    @if ($query !== '')
                        matching '{{ $query }}'
                    @endif
                </p>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-vr-outline btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#shopFilters" aria-controls="shopFilters">
                        Filters <i class="bi bi-sliders ms-1"></i>
                    </button>
                    <form method="GET" action="{{ route('shop.index') }}" class="d-flex align-items-center gap-2">
                    @if ($query !== '')
                        <input type="hidden" name="q" value="{{ $query }}">
                    @endif
                    @if ($isFeatured)
                        <input type="hidden" name="is_featured" value="1">
                    @endif
                    @foreach ($selectedCategories as $c)
                        <input type="hidden" name="category[]" value="{{ $c }}">
                    @endforeach
                    @if ($minPrice !== '' && $minPrice !== null)
                        <input type="hidden" name="min_price" value="{{ $minPrice }}">
                    @endif
                    @if ($maxPrice !== '' && $maxPrice !== null)
                        <input type="hidden" name="max_price" value="{{ $maxPrice }}">
                    @endif
                    <label class="small text-muted mb-0" for="sortTop">Sort:</label>
                    <select name="sort" id="sortTop" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                        <option value="" @selected($sort === '' || $sort === 'newest')>Newest</option>
                        <option value="price_asc" @selected($sort === 'price_asc')>Price: Low to High</option>
                        <option value="price_desc" @selected($sort === 'price_desc')>Price: High to Low</option>
                        <option value="popular" @selected($sort === 'popular')>Popular</option>
                    </select>
                </form>
                </div>
            </div>

            @if ($products->isEmpty())
                <div class="vr-empty bg-white rounded-4" style="box-shadow:var(--vr-shadow-xs);">
                    <i class="bi bi-search"></i>
                    <p class="mt-2 mb-0">No products found. Try adjusting your filters.</p>
                </div>
            @else
                <div class="row g-4 vr-stagger">
                    @foreach ($products as $product)
                        <div class="col-6 col-md-4 col-xl-3">
                            @include('storefront.partials.product-card', ['product' => $product])
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 d-flex justify-content-center">
                    {{ $products->links('storefront.partials.pagination') }}
                </div>
            @endif
        </main>
    </div>
</div>

@endsection