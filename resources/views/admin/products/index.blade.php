@extends('admin.layouts.app')

@section('title', 'Products')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Product Catalogue</h5>
        <div class="text-muted small mt-0.5">Manage inventory, pricing, and variants across {{ $products->total() }} total product(s)</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-upload"></i><span>Import CSV</span>
        </button>
        <a href="{{ route('admin.products.export') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i class="bi bi-download"></i><span>Export CSV</span>
        </a>
        <a href="{{ route('admin.products.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
            <i class="bi bi-plus-lg"></i><span>Add Product</span>
        </a>
    </div>
</div>

<div class="card filter-card mb-3">
    <form method="GET" action="{{ route('admin.products.index') }}" class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Search product title, slug, or SKU..." value="{{ request('search') }}">
            </div>
        </div>
        <div class="col-6 col-md-3">
            <select name="category_id" class="form-select form-select-sm">
                <option value="">All Categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->parent_id ? '— ' : '' }}{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-dark flex-fill" title="Filter Products"><i class="bi bi-funnel me-1"></i>Filter</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th class="text-end">Selling Price</th>
                    <th class="text-center">Stock Level</th>
                    <th>Status</th>
                    <th class="text-end" style="min-width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2.5">
                                @php $img = $product->images->first(); @endphp
                                @if ($img)
                                    <img src="{{ image_url($img->image_path) }}" alt="" class="rounded-2 border" width="44" height="44" style="object-fit:cover;">
                                @else
                                    <div class="rounded-2 border bg-light d-flex align-items-center justify-content-center" style="width:44px; height:44px;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-bold small text-truncate" style="max-width:240px;">
                                        <a href="{{ route('admin.products.show', $product->slug) }}" class="text-decoration-none text-dark hover-green">
                                            {{ $product->name }}
                                        </a>
                                    </div>
                                    <div class="text-muted small" style="font-size:0.75rem;">{{ $product->slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="small font-monospace text-secondary">{{ $product->sku ?: '—' }}</td>
                        <td class="small">
                            @php $pc = $product->categories->first(); @endphp
                            @if ($pc)
                                <span class="badge bg-light text-dark border">{{ $pc->name }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="fw-bold small text-dark">{{ format_price($product->selling_price) }}</div>
                            @if ($product->mrp > $product->selling_price)
                                <div style="font-size:0.75rem;">
                                    <del class="text-muted">{{ format_price($product->mrp) }}</del>
                                    <span class="badge badge-soft-success py-0 px-1" style="font-size:0.65rem;">{{ $product->discount_percent }}% off</span>
                                </div>
                            @endif
                        </td>
                        <td class="text-center">
                            @php $stock = $product->getAvailableStock(); @endphp
                            <div class="d-inline-flex align-items-center gap-1.5">
                                @if ($product->isOutOfStock())
                                    <span class="badge badge-soft-danger"><i class="bi bi-x-circle me-1"></i>Out of Stock</span>
                                @elseif ($product->isLowStock())
                                    <span class="badge badge-soft-warning"><i class="bi bi-exclamation-triangle me-1"></i>{{ $stock }} Left</span>
                                @else
                                    <span class="badge badge-soft-success"><i class="bi bi-check-circle me-1"></i>{{ $stock }} in stock</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @php
                                $statusClasses = ['draft' => 'secondary', 'active' => 'success', 'inactive' => 'danger'];
                            @endphp
                            <span class="badge badge-soft-{{ $statusClasses[$product->status] ?? 'secondary' }}">
                                {{ ucfirst($product->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end align-items-center">
                                <a href="{{ route('admin.products.show', $product->slug) }}" class="btn btn-sm btn-outline-secondary p-1 px-2" title="View Product Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.products.edit', $product->slug) }}" class="btn btn-sm btn-outline-secondary p-1 px-2" title="Edit Product">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.products.destroy', $product->slug) }}" data-confirm="Delete this product?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger p-1 px-2" title="Delete Product">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state py-5">
                                <div class="empty-state-icon"><i class="bi bi-box-seam"></i></div>
                                <div class="empty-state-title">No Products Found</div>
                                <p class="empty-state-desc">No products matched your search or category filters.</p>
                                <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary mt-2">
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
            <span class="small text-muted">Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} products</span>
            <div>{{ $products->links() }}</div>
        </div>
    @endif
</div>

<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.products.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Import Products</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2">Upload a CSV file with columns: name, sku, mrp, selling_price, gst_rate, stock, status, category_slug</p>
                    <input type="file" name="import_file" class="form-control" accept=".csv,.txt" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Import</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
