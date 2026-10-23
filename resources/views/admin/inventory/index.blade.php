@extends('admin.layouts.app')

@section('title', 'Inventory')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Stock & Inventory Radar</h5>
        <div class="text-muted small mt-0.5">Real-time warehouse stock tracking across {{ $products->total() }} product(s)</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i class="bi bi-box-seam"></i><span>Product Catalogue</span>
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6 col-md-4">
        <div class="card stat-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-label">Total SKUs Monitored</span>
                <span class="stat-icon" style="background:#EEF4FF; color:#3538CD;"><i class="bi bi-box-seam"></i></span>
            </div>
            <div class="stat-value">{{ $totalSkus }}</div>
            <div class="text-muted small mt-1">Across all active items & variants</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-4">
        <div class="card stat-card h-100">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-label">Stock Warnings</span>
                <span class="stat-icon" style="background:{{ $outOfStock > 0 ? '#FEF2F2' : '#ECFDF5' }}; color:{{ $outOfStock > 0 ? '#DC2626' : '#047857' }};">
                    <i class="bi bi-exclamation-octagon"></i>
                </span>
            </div>
            <div class="stat-value {{ $outOfStock > 0 ? 'text-danger' : 'text-success' }}">{{ $outOfStock }}</div>
            <div class="text-muted small mt-1">
                @if ($outOfStock > 0)
                    <span class="text-danger fw-semibold"><i class="bi bi-arrow-up-short"></i>Requires replenishment</span>
                @else
                    <span class="text-success"><i class="bi bi-check2"></i>All SKUs healthy</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card filter-card mb-3">
    <form method="GET" action="{{ route('admin.inventory.index') }}" class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control border-start-0" placeholder="Search product name, slug, or SKU..." value="{{ request('q') }}">
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="form-check pt-1">
                <input class="form-check-input" type="checkbox" name="low_only" value="1" id="lowOnly" {{ request('low_only') ? 'checked' : '' }}>
                <label class="form-check-label small fw-semibold cursor-pointer" for="lowOnly">Low / Out of stock only</label>
            </div>
        </div>
        <div class="col-12 col-md-2 ms-auto d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-dark flex-fill" title="Apply Filter"><i class="bi bi-funnel me-1"></i>Filter</button>
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-outline-secondary" title="Clear Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU Code</th>
                    <th class="text-center">Product Type</th>
                    <th class="text-end">Available Stock</th>
                    <th class="text-end">Threshold</th>
                    <th>Inventory Status</th>
                    <th class="text-end" style="min-width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php
                        $hasVariants = $product->variants->isNotEmpty();
                        $stock = $hasVariants ? (int) $product->variants->sum('stock') : (int) $product->stock;
                        $threshold = $product->low_stock_threshold ?: 0;
                        if ($stock <= 0):
                            $statusBadge = '<span class="badge badge-soft-danger"><i class="bi bi-x-circle me-1"></i>Out of stock</span>';
                        elseif ($stock <= $threshold):
                            $statusBadge = '<span class="badge badge-soft-warning"><i class="bi bi-exclamation-triangle me-1"></i>Low Stock</span>';
                        else:
                            $statusBadge = '<span class="badge badge-soft-success"><i class="bi bi-check-circle me-1"></i>In stock</span>';
                        endif;
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2.5">
                                @php $img = $product->images->first(); @endphp
                                @if ($img)
                                    <img src="{{ image_url($img->image_path) }}" alt="{{ $product->name }}" class="rounded-2 border" width="44" height="44" style="object-fit:cover;">
                                @else
                                    <div class="rounded-2 border bg-light d-flex align-items-center justify-content-center" style="width:44px; height:44px;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-bold small text-truncate" style="max-width:240px;">
                                        <a href="{{ route('admin.products.show', $product->slug) }}" class="text-decoration-none text-dark hover-green">{{ $product->name }}</a>
                                    </div>
                                    <div class="text-muted small" style="font-size:0.75rem;">{{ $product->slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="small font-monospace text-secondary">{{ $hasVariants ? ($product->variants->first()->sku ?: $product->sku ?: '—') : ($product->sku ?: '—') }}</td>
                        <td class="text-center">
                            <span class="badge {{ $hasVariants ? 'badge-soft-info' : 'badge-soft-secondary' }}">{{ $hasVariants ? 'Variant' : 'Simple' }}</span>
                        </td>
                        <td class="text-end">
                            <span class="fw-bold small {{ $stock <= 0 ? 'text-danger' : ($stock <= $threshold ? 'text-warning' : 'text-dark') }}">{{ $stock }}</span>
                        </td>
                        <td class="text-end small text-muted">{{ $threshold }}</td>
                        <td>{!! $statusBadge !!}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.products.show', $product->slug) }}" class="btn btn-sm btn-outline-secondary p-1 px-2" title="View Product Stock Details">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state py-5">
                                <div class="empty-state-icon"><i class="bi bi-box2"></i></div>
                                <div class="empty-state-title">No Inventory Items Found</div>
                                <p class="empty-state-desc">No products matched the current stock search or threshold filters.</p>
                                <a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-outline-secondary mt-2">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Clear Filters
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($products->hasPages())
        <div class="card-footer d-flex align-items-center justify-content-between">
            <span class="small text-muted">Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} entries</span>
            <div>{{ $products->links() }}</div>
        </div>
    @endif
</div>
@endsection
