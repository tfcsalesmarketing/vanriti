@extends('storefront.layouts.app')

@section('title', $category->meta_title ?: $category->name)
@section('meta_description', $category->meta_description ?: setting('meta_description'))
@section('meta_keywords', $category->meta_keywords ?: setting('meta_keywords'))
@section('og_title', $category->meta_title ?: $category->name)
@section('og_description', $category->meta_description ?: setting('meta_description'))

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
        @if ($category->parent)
        {
            "@@type": "ListItem",
            "position": 2,
            "name": {!! json_encode($category->parent->name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
            "item": {!! json_encode(route('shop.category', $category->parent->slug), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        },
        @endif
        {
            "@@type": "ListItem",
            "position": {{ $category->parent ? 3 : 2 }},
            "name": {!! json_encode($category->name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
            "item": {!! json_encode(route('shop.category', $category->slug), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        }
    ]
}
</script>
@if ($products->isNotEmpty())
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "ItemList",
    "name": {!! json_encode($category->name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
    "url": {!! json_encode(route('shop.category', $category->slug), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
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
    <nav class="vr-breadcrumb mb-3" aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
        </ol>
    </nav>

    <div class="mb-4">
        <h1 class="h4 fw-bold mb-1" style="color:var(--vr-green-dark);">{{ $category->name }}</h1>
        @if ($category->description)
            <p class="text-muted small mt-2 mb-0" style="max-width:720px;">{{ $category->description }}</p>
        @endif
    </div>

    @if ($category->children->count())
        <div class="d-flex overflow-auto pb-2 mb-4 gap-2" style="scrollbar-width:thin;">
            @foreach ($category->children as $child)
                <a href="{{ route('shop.category', $child->slug) }}" class="vr-chip text-nowrap">
                    <i class="bi bi-tag"></i>
                    {{ $child->name }}
                </a>
            @endforeach
        </div>
    @endif

    <div class="d-flex align-items-center justify-content-between mb-3">
        <p class="small text-muted mb-0">{{ $products->total() }} product{{ $products->total() === 1 ? '' : 's' }}</p>
    </div>

    @if ($products->isEmpty())
        <div class="vr-empty bg-white rounded-4" style="box-shadow:var(--vr-shadow-xs);">
            <i class="bi bi-box"></i>
            <p class="mt-2 mb-0">No products in this category yet.</p>
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
</div>

@endsection