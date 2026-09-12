@extends('admin.layouts.app')

@section('title', 'Products')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Products</h5>
        <small class="text-muted">{{ $products->total() }} products total</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.products.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Product</a>
        <a href="{{ route('admin.products.export') }}" class="btn btn-sm btn-outline-success"><i class="bi bi-download me-1"></i>Export CSV</a>
        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#importModal"><i class="bi bi-upload me-1"></i>Import CSV</button>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.products.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name or SKU..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->parent_id ? '— ' : '' }}{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-dark w-100"><i class="bi bi-search"></i></button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
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
                    <th>Category</th>
                    <th class="text-end">Price</th>
                    <th class="text-end">Stock</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @php $img = $product->images->first(); @endphp
                                @if ($img)
                                    <img src="{{ image_url($img->image_path) }}" alt="" class="rounded" width="40" height="40" style="object-fit:cover;">
                                @else
                                    <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold small text-truncate" style="max-width:200px;">
                                        <a href="{{ route('admin.products.show', $product->slug) }}" class="text-decoration-none text-dark">{{ $product->name }}</a>
                                    </div>
                                    <div class="text-muted" style="font-size:0.75rem;">{{ $product->slug }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="small">{{ $product->sku ?: '—' }}</td>
                        <td class="small">
                            @php $pc = $product->categories->first(); @endphp
                            {{ $pc ? $pc->name : '—' }}
                        </td>
                        <td class="text-end">
                            <div class="fw-semibold small">{{ format_price($product->selling_price) }}</div>
                            @if ($product->mrp > $product->selling_price)
                                <div><del class="text-muted small">{{ format_price($product->mrp) }}</del> <span class="badge bg-success" style="font-size:0.65rem;">{{ $product->discount_percent }}% off</span></div>
                            @endif
                        </td>
                        <td class="text-end">
                            @php $stock = $product->getAvailableStock(); @endphp
                            <span class="small">{{ $stock }}</span>
                            @if ($product->isOutOfStock())
                                <span class="badge bg-danger" style="font-size:0.6rem;">Out</span>
                            @elseif ($product->isLowStock())
                                <span class="badge bg-warning text-dark" style="font-size:0.6rem;">Low</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $statusClasses = ['draft' => 'secondary', 'active' => 'success', 'inactive' => 'danger'];
                            @endphp
                            <span class="badge bg-{{ $statusClasses[$product->status] ?? 'secondary' }}">{{ ucfirst($product->status) }}</span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.products.show', $product->slug) }}" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('admin.products.edit', $product->slug) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('admin.products.destroy', $product->slug) }}" onsubmit="return confirm('Delete this product?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
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
