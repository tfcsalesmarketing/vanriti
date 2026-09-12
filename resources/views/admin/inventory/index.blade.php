@extends('admin.layouts.app')

@section('title', 'Inventory')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Inventory</h5>
        <small class="text-muted">{{ $products->total() }} products listed</small>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-cream text-green"><i class="bi bi-box-seam"></i></div>
                <div>
                    <div class="text-muted small fw-semibold">Total SKUs</div>
                    <div class="stat-value">{{ $totalSkus }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-light text-danger"><i class="bi bi-x-circle"></i></div>
                <div>
                    <div class="text-muted small fw-semibold">Out of Stock SKUs</div>
                    <div class="stat-value">{{ $outOfStock }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.inventory.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search name or SKU..." value="{{ request('q') }}">
            </div>
            <div class="col-md-2">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="low_only" value="1" id="lowOnly" {{ request('low_only') ? 'checked' : '' }}>
                    <label class="form-check-label small fw-semibold" for="lowOnly">Low stock only</label>
                </div>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-dark w-100"><i class="bi bi-search"></i></button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th class="text-center">Type</th>
                    <th class="text-end">Available Stock</th>
                    <th class="text-end">Threshold</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php
                        $hasVariants = $product->variants->isNotEmpty();
                        $stock = $hasVariants ? (int) $product->variants->sum('stock') : (int) $product->stock;
                        $threshold = $product->low_stock_threshold ?: 0;
                        if ($stock <= 0):
                            $statusBadge = '<span class="badge badge-soft-danger">Out of stock</span>';
                        elseif ($stock <= $threshold):
                            $statusBadge = '<span class="badge badge-soft-warning">Low</span>';
                        else:
                            $statusBadge = '<span class="badge badge-soft-success">In stock</span>';
                        endif;
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @php $img = $product->images->first(); @endphp
                                @if ($img)
                                    <img src="{{ image_url($img->image_path) }}" alt="" class="img-thumb">
                                @else
                                    <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold small text-truncate" style="max-width:220px;">
                                        <a href="{{ route('admin.products.show', $product->slug) }}" class="text-decoration-none text-dark">{{ $product->name }}</a>
                                    </div>
                                    <div class="text-muted" style="font-size:0.75rem;">{{ $product->slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="small">{{ $hasVariants ? ($product->variants->first()->sku ?: $product->sku ?: '—') : ($product->sku ?: '—') }}</td>
                        <td class="text-center">
                            <span class="badge {{ $hasVariants ? 'badge-soft-info' : 'badge-soft-secondary' }}">{{ $hasVariants ? 'Variant' : 'Simple' }}</span>
                        </td>
                        <td class="text-end">
                            <span class="fw-semibold small">{{ $stock }}</span>
                        </td>
                        <td class="text-end small">{{ $threshold }}</td>
                        <td>{!! $statusBadge !!}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.products.show', $product->slug) }}" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $products->links() }}
    </div>
</div>
@endsection
