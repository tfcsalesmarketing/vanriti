@extends('admin.layouts.app')

@section('title', 'TFC Synced Products Catalogue')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="fw-bold mb-1">Live Synced Products</h5>
        <p class="text-muted small mb-0">Products are automatically synced live from the central VANRITI database.</p>
    </div>
    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-box-arrow-up-right me-1"></i>Manage in VANRITI Catalogue
    </a>
</div>

{{-- Filters --}}
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.tfc.products.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search product name or SKU..." value="{{ request('search') }}">
            </div>
            <div class="col-12 col-md-3">
                <select name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                <a href="{{ route('admin.tfc.products.index') }}" class="btn btn-light btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Products Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">Image</th>
                        <th>Product Info</th>
                        <th>Categories</th>
                        <th>Pricing</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>
                                @php
                                    $img = $product->getPrimaryImage()?->image_path;
                                @endphp
                                @if ($img)
                                    <img src="{{ image_url($img) }}" alt="{{ $product->name }}" class="rounded border" style="width: 50px; height: 50px; object-fit: cover;">
                                @else
                                    <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted" style="width: 50px; height: 50px;">
                                        <i class="bi bi-image"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $product->name }}</div>
                                <div class="text-muted small">SKU: {{ $product->sku ?: '—' }} | Slug: {{ $product->slug }}</div>
                            </td>
                            <td>
                                @foreach ($product->categories as $c)
                                    <span class="badge bg-light text-dark border">{{ $c->name }}</span>
                                @endforeach
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ format_price($product->selling_price) }}</div>
                                @if ($product->mrp && $product->mrp > $product->selling_price)
                                    <small class="text-muted text-decoration-line-through">{{ format_price($product->mrp) }}</small>
                                @endif
                            </td>
                            <td>
                                @if ($product->hasStock())
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        {{ $product->getAvailableStock() }} in stock
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Out of Stock</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $product->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($product->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ tfc_route('products.show', $product->slug) }}" target="_blank" class="btn btn-sm btn-light" title="View on TFC Website">
                                    <i class="bi bi-eye"></i> TFC Preview
                                </a>
                                <a href="{{ route('admin.products.show', $product) }}" class="btn btn-sm btn-outline-primary" title="Edit in VANRITI Admin">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No products found matching criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($products->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $products->links() }}
        </div>
    @endif
</div>
@endsection
