@extends('admin.layouts.app')

@section('title', $product->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">{{ $product->name }}</h5>
        <small class="text-muted">SKU: {{ $product->sku ?: '—' }} &middot; Slug: {{ $product->slug }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.products.edit', $product->slug) }}" class="btn btn-sm btn-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
        <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Basic Information</div>
            <div class="card-body">
                <div class="row g-2 small">
                    <div class="col-md-6"><strong>Name:</strong> {{ $product->name }}</div>
                    <div class="col-md-6">
                        <strong>Status:</strong>
                        @php $sc = ['draft' => 'secondary', 'active' => 'success', 'inactive' => 'danger']; @endphp
                        <span class="badge bg-{{ $sc[$product->status] ?? 'secondary' }}">{{ ucfirst($product->status) }}</span>
                    </div>
                    <div class="col-12"><strong>Short Description:</strong> {{ $product->short_description ?: '—' }}</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Pricing</div>
            <div class="card-body">
                <div class="row g-2 small">
                    <div class="col-md-3"><strong>MRP:</strong> {{ format_price($product->mrp) }}</div>
                    <div class="col-md-3"><strong>Selling:</strong> {{ format_price($product->selling_price) }}</div>
                    <div class="col-md-3"><strong>GST:</strong> {{ $product->gst_rate }}%</div>
                    <div class="col-md-3"><strong>Discount:</strong> {{ $product->discount_percent }}%</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Stock</div>
            <div class="card-body">
                <div class="row g-2 small mb-3">
                    <div class="col-md-4"><strong>Stock on Hand:</strong> {{ $inventory->stock_on_hand ?? $product->stock }}</div>
                    <div class="col-md-4"><strong>Reserved:</strong> {{ $inventory->reserved ?? 0 }}</div>
                    <div class="col-md-4"><strong>Low Stock Threshold:</strong> {{ $product->low_stock_threshold }}</div>
                </div>
                <div class="d-flex gap-2 mb-2">
                    @if ($product->isOutOfStock())
                        <span class="badge bg-danger">Out of Stock</span>
                    @elseif ($product->isLowStock())
                        <span class="badge bg-warning text-dark">Low Stock</span>
                    @else
                        <span class="badge bg-success">In Stock</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Categories</div>
            <div class="card-body small">
                @forelse ($product->categories as $cat)
                    <span class="badge bg-light text-dark border me-1 mb-1">{{ $cat->name }}</span>
                @empty
                    <span class="text-muted">No categories assigned.</span>
                @endforelse
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Flags</div>
            <div class="card-body">
                @if ($product->is_featured)<span class="badge bg-primary me-1">Featured</span>@endif
                @if ($product->is_bestseller)<span class="badge bg-warning text-dark me-1">Bestseller</span>@endif
                @if ($product->is_new_arrival)<span class="badge bg-info me-1">New Arrival</span>@endif
                @if (!$product->is_featured && !$product->is_bestseller && !$product->is_new_arrival)<span class="text-muted small">No flags set.</span>@endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Images</div>
            <div class="card-body">
                @php $images = $product->images; @endphp
                @if ($images->count())
                    <div class="row g-2">
                        @foreach ($images as $img)
                            <div class="col-4">
                                <img src="{{ image_url($img->image_path) }}" alt="{{ $img->alt_text }}" class="rounded border w-100" style="height:80px;object-fit:cover;">
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted small mb-0">No images.</p>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Update Stock</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.products.stock', $product->slug) }}">
                    @csrf
                    <div class="mb-2">
                        <select name="type" class="form-select form-select-sm" required>
                            <option value="purchase">Purchase</option>
                            <option value="sale">Sale</option>
                            <option value="adjustment">Adjustment</option>
                            <option value="return">Return</option>
                            <option value="reversal">Reversal</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <input type="number" name="quantity" class="form-control form-control-sm" placeholder="Quantity" min="1" required>
                    </div>
                    <div class="mb-2">
                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason (required for adjustment)">
                    </div>
                    <button type="submit" class="btn btn-sm btn-dark w-100"><i class="bi bi-arrow-repeat me-1"></i>Update Stock</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <span>Variants</span>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#addVariantForm"><i class="bi bi-plus"></i> Add</button>
    </div>
    <div class="card-body">
        <div id="addVariantForm" class="collapse mb-3">
            <form method="POST" action="{{ route('admin.products.variants.store', $product->slug) }}">
                @csrf
                <div class="row g-2">
                    <div class="col-md-2">
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Name">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="sku" class="form-control form-control-sm" placeholder="SKU">
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="mrp" class="form-control form-control-sm" placeholder="MRP" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="selling_price" class="form-control form-control-sm" placeholder="Selling Price" required>
                    </div>
                    <div class="col-md-1">
                        <input type="number" name="stock" class="form-control form-control-sm" placeholder="Stock" value="0">
                    </div>
                    <div class="col-md-1">
                        <input type="number" step="0.01" name="gst_rate" class="form-control form-control-sm" placeholder="GST%" value="0">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>

        @php $variants = $product->variants; @endphp
        @if ($variants->count())
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>SKU</th>
                            <th class="text-end">MRP</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Stock</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($variants as $variant)
                            <tr>
                                <td class="small">{{ $variant->name ?: '—' }}</td>
                                <td class="small">{{ $variant->sku ?: '—' }}</td>
                                <td class="text-end small">{{ format_price($variant->mrp) }}</td>
                                <td class="text-end small">{{ format_price($variant->selling_price) }}</td>
                                <td class="text-end small">{{ $variant->stock }}</td>
                                <td>
                                    <span class="badge bg-{{ $variant->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($variant->status) }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <form method="POST" action="{{ route('admin.products.variants.update', $variant->id) }}" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="name" value="{{ $variant->name }}">
                                            <input type="hidden" name="sku" value="{{ $variant->sku }}">
                                            <input type="hidden" name="mrp" value="{{ $variant->mrp }}">
                                            <input type="hidden" name="selling_price" value="{{ $variant->selling_price }}">
                                            <input type="hidden" name="stock" value="{{ $variant->stock }}">
                                            <input type="hidden" name="gst_rate" value="{{ $variant->gst_rate }}">
                                            <input type="hidden" name="status" value="{{ $variant->status }}">
                                        </form>
                                        <form method="POST" action="{{ route('admin.products.variants.destroy', $variant->id) }}" class="d-inline" onsubmit="return confirm('Delete variant?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted small mb-0">No variants.</p>
        @endif
    </div>
</div>
@endsection
