@extends('admin.layouts.app')

@section('title', 'Products')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Product Catalogue</h5>
        <div class="text-muted small mt-0.5">Manage inventory, pricing, variants, and storefront visibility</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-upload"></i><span>Import CSV</span>
        </button>
        <a href="{{ route('admin.products.export') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i class="bi bi-download"></i><span>Export CSV</span>
        </a>
        <a href="{{ route('admin.products.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
            <i class="bi bi-plus-lg"></i><span>Add Product</span>
        </a>
    </div>
</div>

{{-- ── Catalogue KPI Metric Cards ───────────────────────────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-xs" style="border-radius:12px; background:linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); border-left:4px solid #10b981 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">Total Catalogue</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#d1fae5;color:#059669;">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
                <div class="fs-5 fw-bold text-dark">{{ $kpis['total'] ?? $products->total() }}</div>
                <div class="text-muted" style="font-size:0.72rem;">{{ $kpis['featured'] ?? 0 }} featured on homepage</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-xs" style="border-radius:12px; background:linear-gradient(135deg, #eff6ff 0%, #ffffff 100%); border-left:4px solid #3b82f6 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">Active in Storefront</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#dbeafe;color:#2563eb;">
                        <i class="bi bi-shop"></i>
                    </div>
                </div>
                <div class="fs-5 fw-bold text-dark">{{ $kpis['active'] ?? 0 }}</div>
                <div class="text-muted" style="font-size:0.72rem;">Published & buyable</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-xs" style="border-radius:12px; background:linear-gradient(135deg, #fefce8 0%, #ffffff 100%); border-left:4px solid #eab308 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">Low Stock Alert</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#fef9c3;color:#ca8a04;">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
                <div class="fs-5 fw-bold text-dark">{{ $kpis['low_stock'] ?? 0 }}</div>
                <div class="text-muted" style="font-size:0.72rem;">Below reorder threshold</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card h-100 border-0 shadow-xs" style="border-radius:12px; background:linear-gradient(135deg, #fef2f2 0%, #ffffff 100%); border-left:4px solid #ef4444 !important;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-semibold">Out of Stock</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#fee2e2;color:#dc2626;">
                        <i class="bi bi-x-circle"></i>
                    </div>
                </div>
                <div class="fs-5 fw-bold text-dark">{{ $kpis['out_of_stock'] ?? 0 }}</div>
                <div class="text-muted" style="font-size:0.72rem;">Unavailable for customers</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Streamlined Filter Bar ───────────────────────────────────────── --}}
<div class="card filter-card mb-3">
    <form method="GET" action="{{ route('admin.products.index') }}" class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Search title, SKU, slug..." value="{{ request('search') }}">
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

{{-- ── Products Table Card ─────────────────────────────────────────── --}}
<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:36px;" class="text-center">⭐</th>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th class="text-end">Selling Price</th>
                    <th class="text-center">Stock Level</th>
                    <th>Status</th>
                    <th class="text-end" style="min-width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php
                        $primaryImage = $product->getPrimaryImage();
                        $hasWaImage = (bool) $product->getWhatsAppImage();
                        $stock = $product->getAvailableStock();
                        $statusClasses = ['draft' => 'secondary', 'active' => 'success', 'inactive' => 'danger'];
                    @endphp
                    <tr id="product-row-{{ $product->id }}">
                        {{-- Featured Toggle Star --}}
                        <td class="text-center">
                            <button type="button" class="btn btn-link p-0 text-decoration-none"
                                    onclick="toggleFeatured('{{ $product->slug }}', this)"
                                    title="{{ $product->is_featured ? 'Featured on Homepage (Click to unfeature)' : 'Mark as Featured' }}">
                                <i class="bi {{ $product->is_featured ? 'bi-star-fill text-warning' : 'bi-star text-muted opacity-50' }}" style="font-size:1.05rem;"></i>
                            </button>
                        </td>

                        {{-- Product Info & Thumbnail --}}
                        <td>
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="position-relative" style="width:44px; height:44px; flex-shrink:0;">
                                    @if ($primaryImage)
                                        <img src="{{ image_url($primaryImage->image_path) }}" alt="" class="rounded-2 border w-100 h-100" style="object-fit:cover;">
                                    @else
                                        <div class="rounded-2 border bg-light d-flex align-items-center justify-content-center w-100 h-100">
                                            <i class="bi bi-image text-muted"></i>
                                        </div>
                                    @endif
                                    @if ($hasWaImage)
                                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle"
                                              style="width:10px; height:10px;" title="Optimized 1:1 WhatsApp image active"></span>
                                    @endif
                                </div>
                                <div>
                                    <div class="fw-bold small text-truncate" style="max-width:240px;">
                                        <a href="{{ route('admin.products.show', $product->slug) }}" class="text-decoration-none text-dark hover-green">
                                            {{ $product->name }}
                                        </a>
                                    </div>
                                    <div class="text-muted small" style="font-size:0.75rem;">
                                        <code>{{ $product->slug }}</code>
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- SKU --}}
                        <td class="small font-monospace text-secondary">{{ $product->sku ?: '—' }}</td>

                        {{-- Category --}}
                        <td class="small">
                            @php $pc = $product->categories->first(); @endphp
                            @if ($pc)
                                <span class="badge bg-light text-dark border">{{ $pc->name }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        {{-- Selling Price & MRP --}}
                        <td class="text-end">
                            <div class="fw-bold small text-dark">{{ format_price($product->selling_price) }}</div>
                            @if ($product->mrp > $product->selling_price)
                                <div style="font-size:0.72rem;">
                                    <del class="text-muted">{{ format_price($product->mrp) }}</del>
                                    <span class="badge bg-success-subtle text-success py-0 px-1">{{ $product->discountPercent() }}% off</span>
                                </div>
                            @endif
                        </td>

                        {{-- Stock Level (Clickable inline stock adjuster) --}}
                        <td class="text-center">
                            <button type="button" class="btn btn-sm p-0 border-0 text-decoration-none"
                                    onclick="openStockModal('{{ $product->slug }}', '{{ addslashes($product->name) }}', {{ $stock }})"
                                    title="Click to quick-adjust stock">
                                <span id="stock-badge-{{ $product->slug }}" class="badge {{ $product->isOutOfStock() ? 'badge-soft-danger' : ($product->isLowStock() ? 'badge-soft-warning' : 'badge-soft-success') }} py-1 px-2">
                                    @if ($product->isOutOfStock())
                                        <i class="bi bi-x-circle me-1"></i>Out of Stock
                                    @elseif ($product->isLowStock())
                                        <i class="bi bi-exclamation-triangle me-1"></i>{{ $stock }} Left
                                    @else
                                        <i class="bi bi-check-circle me-1"></i>{{ $stock }} in stock
                                    @endif
                                    <i class="bi bi-pencil-fill ms-1 opacity-50" style="font-size:0.65rem;"></i>
                                </span>
                            </button>
                        </td>

                        {{-- Status (1-click quick toggle) --}}
                        <td>
                            <button type="button" class="btn btn-sm p-0 border-0"
                                    onclick="toggleStatus('{{ $product->slug }}', this)"
                                    title="Click to toggle status (Active / Inactive)">
                                <span id="status-badge-{{ $product->slug }}" class="badge badge-soft-{{ $statusClasses[$product->status] ?? 'secondary' }} py-1 px-2">
                                    {{ ucfirst($product->status) }}
                                </span>
                            </button>
                        </td>

                        {{-- Action Buttons --}}
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end align-items-center">
                                <a href="{{ route('product.show', $product->slug) }}" class="btn btn-sm btn-outline-secondary p-1 px-2" title="View in Storefront" target="_blank">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                                <a href="{{ route('admin.products.show', $product->slug) }}" class="btn btn-sm btn-outline-secondary p-1 px-2" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.products.edit', $product->slug) }}" class="btn btn-sm btn-outline-primary p-1 px-2" title="Edit Product">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.products.destroy', $product->slug) }}" class="d-inline" data-confirm="Delete this product? This action cannot be undone.">
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
                        <td colspan="8">
                            <div class="empty-state py-5 text-center">
                                <div class="empty-state-icon fs-1 text-muted mb-2"><i class="bi bi-box-seam"></i></div>
                                <h6 class="fw-bold">No Products Found</h6>
                                <p class="text-muted small">No products matched your search or category filters.</p>
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
        <div class="card-footer d-flex align-items-center justify-content-between p-3 bg-light">
            <span class="small text-muted">Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} products</span>
            <div>{{ $products->links() }}</div>
        </div>
    @endif
</div>

{{-- ── Quick Stock Level Adjustment Modal ────────────────────────────── --}}
<div class="modal fade" id="quickStockModal" tabindex="-1" aria-labelledby="quickStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow border-0" style="border-radius:12px;">
            <div class="modal-header py-2.5 px-3 bg-light">
                <h6 class="modal-title fw-bold small" id="quickStockModalLabel">Adjust Stock Level</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickStockForm" onsubmit="submitStockUpdate(event)">
                @csrf
                <div class="modal-body p-3">
                    <div class="small fw-semibold text-truncate mb-2" id="stockModalProductName">Product Name</div>
                    <div class="mb-2">
                        <label class="form-label small text-muted">Available Stock Units</label>
                        <div class="input-group input-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="adjustModalStock(-1)">-</button>
                            <input type="number" id="stockModalInput" name="stock" class="form-control text-center fw-bold" min="0" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="adjustModalStock(1)">+</button>
                        </div>
                    </div>
                    <div class="mb-1">
                        <input type="text" id="stockModalReason" name="reason" class="form-control form-control-sm" placeholder="Reason (e.g. Stock check)">
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success px-3" id="saveStockBtn">Save Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── CSV Import Modal ─────────────────────────────────────────────── --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.products.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content shadow border-0" style="border-radius:12px;">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Import Products CSV</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2">Upload a CSV file with columns: <code>name, sku, mrp, selling_price, gst_rate, stock, status, category_slug</code></p>
                    <input type="file" name="import_file" class="form-control" accept=".csv,.txt" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Import Products</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let currentProductSlug = null;

function openStockModal(slug, name, currentStock) {
    currentProductSlug = slug;
    document.getElementById('stockModalProductName').innerText = name;
    document.getElementById('stockModalInput').value = currentStock;
    document.getElementById('stockModalReason').value = '';
    const modal = new bootstrap.Modal(document.getElementById('quickStockModal'));
    modal.show();
    setTimeout(() => document.getElementById('stockModalInput').focus(), 300);
}

function adjustModalStock(delta) {
    const input = document.getElementById('stockModalInput');
    const val = Math.max(0, parseInt(input.value || 0) + delta);
    input.value = val;
}

function submitStockUpdate(e) {
    e.preventDefault();
    if (!currentProductSlug) return;

    const btn = document.getElementById('saveStockBtn');
    btn.disabled = true;
    btn.innerText = 'Saving...';

    const stock = document.getElementById('stockModalInput').value;
    const reason = document.getElementById('stockModalReason').value;

    fetch(`/admin/products/${currentProductSlug}/stock`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ stock: parseInt(stock), reason: reason })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById(`stock-badge-${currentProductSlug}`);
            if (badge) {
                let badgeClass = 'badge-soft-success';
                let icon = 'bi-check-circle';
                let label = `${data.stock} in stock`;

                if (data.is_out_of_stock) {
                    badgeClass = 'badge-soft-danger';
                    icon = 'bi-x-circle';
                    label = 'Out of Stock';
                } else if (data.is_low_stock) {
                    badgeClass = 'badge-soft-warning';
                    icon = 'bi-exclamation-triangle';
                    label = `${data.stock} Left`;
                }

                badge.className = `badge ${badgeClass} py-1 px-2`;
                badge.innerHTML = `<i class="bi ${icon} me-1"></i>${label} <i class="bi bi-pencil-fill ms-1 opacity-50" style="font-size:0.65rem;"></i>`;
            }

            bootstrap.Modal.getInstance(document.getElementById('quickStockModal')).hide();
        } else {
            alert('Failed to update stock: ' + (data.message || 'Error'));
        }
    })
    .catch(err => {
        alert('Network error updating stock.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerText = 'Save Stock';
    });
}

function toggleFeatured(slug, btn) {
    fetch(`/admin/products/${slug}/toggle-featured`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const icon = btn.querySelector('i');
            if (data.is_featured) {
                icon.className = 'bi bi-star-fill text-warning';
                btn.title = 'Featured on Homepage (Click to unfeature)';
            } else {
                icon.className = 'bi bi-star text-muted opacity-50';
                btn.title = 'Mark as Featured';
            }
        }
    })
    .catch(() => alert('Could not update featured status.'));
}

function toggleStatus(slug, btn) {
    fetch(`/admin/products/${slug}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById(`status-badge-${slug}`);
            if (badge) {
                const map = { active: 'badge-soft-success', inactive: 'badge-soft-danger', draft: 'badge-soft-secondary' };
                badge.className = `badge ${map[data.status] || 'badge-soft-secondary'} py-1 px-2`;
                badge.innerText = data.label;
            }
        }
    })
    .catch(() => alert('Could not toggle status.'));
}
</script>
@endsection
