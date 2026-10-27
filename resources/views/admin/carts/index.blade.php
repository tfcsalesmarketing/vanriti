@extends('admin.layouts.app')

@section('title', 'Cart Items & Recovery')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Cart Items & Retention</h5>
        <div class="text-muted small mt-0.5">Track live shopping carts, customer drop-offs, and trigger WhatsApp cart recoveries</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-light text-dark border px-3 py-1.5 fw-semibold">
            <i class="bi bi-cart3 me-1 text-primary"></i>{{ $cartItems->total() }} Active Line Items
        </span>
    </div>
</div>

<div class="card filter-card mb-3">
    <form method="GET" action="{{ route('admin.carts.index') }}" class="row g-2 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label small fw-semibold text-muted mb-1">Product</label>
            <select name="product_id" class="form-select form-select-sm">
                <option value="">All Products</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Customer</label>
            <select name="user_id" class="form-select form-select-sm">
                <option value="">All Users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Owner Type</label>
            <select name="owner_type" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="users" @selected(request('owner_type') === 'users')>Registered Users</option>
                <option value="guests" @selected(request('owner_type') === 'guests')>Guests</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Added From</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small fw-semibold text-muted mb-1">Added To</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
        </div>
        <div class="col-12 col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-dark flex-fill" title="Apply Filter"><i class="bi bi-funnel"></i></button>
            <a href="{{ route('admin.carts.index') }}" class="btn btn-sm btn-outline-secondary" title="Clear Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

@if ($topProducts->isNotEmpty())
<div class="card table-card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-fire text-danger"></i>
            <span>Most Added to Cart</span>
        </div>
        <small class="text-muted">High demand intent</small>
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 36px;">#</th>
                    <th>Product</th>
                    <th class="text-center">Total Quantity</th>
                    <th class="text-center">Cart Lines</th>
                    <th class="text-end">Distinct Carts</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($topProducts as $idx => $product)
                    <tr>
                        <td class="text-muted fw-bold small text-center">{{ ($topProducts->firstItem() ?? 1) + $idx }}</td>
                        <td>
                            <div class="fw-semibold small text-dark">{{ $product->product_name }}</div>
                            <div class="text-muted font-monospace" style="font-size:0.73rem;">{{ $product->product_sku ?: '—' }}</div>
                        </td>
                        <td class="text-center small"><span class="badge bg-light text-dark border">{{ (int) $product->total_quantity }}</span></td>
                        <td class="text-center small">{{ (int) $product->line_count }}</td>
                        <td class="text-end small fw-bold text-dark">{{ (int) $product->cart_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if ($topProducts->hasPages())
        <div class="card-footer d-flex align-items-center justify-content-between">
            <span class="small text-muted">Showing {{ $topProducts->firstItem() }} to {{ $topProducts->lastItem() }} of {{ $topProducts->total() }} products</span>
            <div>{{ $topProducts->withQueryString()->links() }}</div>
        </div>
    @endif
</div>
@endif

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Added Date</th>
                    <th>Customer / Session</th>
                    <th>Product Details</th>
                    <th>Variant</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cartItems as $item)
                    <tr>
                        <td class="small text-muted" style="white-space: nowrap;">
                            {{ $item->created_at?->format('d M Y') }}
                            <div style="font-size:0.72rem; color:#8C9E90;">{{ $item->created_at?->format('h:i A') }}</div>
                        </td>
                        <td class="small">
                            @if ($item->cart?->owner_type === \App\Models\User::class && $item->cart?->owner)
                                <div class="fw-bold text-dark">{{ $item->cart->owner->name }}</div>
                                <div class="text-muted" style="font-size:0.75rem;">{{ $item->cart->owner->email }}</div>
                                @php
                                    $cartUserPhone = trim((string) ($item->cart->owner->phone ?? ''));
                                    $cleanCartPhone = preg_replace('/[^0-9]/', '', $cartUserPhone);
                                    if (strlen($cleanCartPhone) === 10) {
                                        $cleanCartPhone = '91' . $cleanCartPhone;
                                    }
                                @endphp
                                @if ($cartUserPhone && $item->cart)
                                    <div class="text-muted mt-0.5" style="font-size:0.75rem;">
                                        <i class="bi bi-telephone me-1 text-muted"></i>{{ $cartUserPhone }}
                                    </div>
                                    <form method="POST" action="{{ route('admin.carts.send-recovery-whatsapp', $item->cart) }}" class="mt-1" onsubmit="return confirm('Send WhatsApp cart recovery template via Meta API to {{ $cartUserPhone }}?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success py-0.5 px-2 small" style="font-size:0.72rem;">
                                            <i class="bi bi-whatsapp me-1"></i>Send Meta Recovery
                                        </button>
                                    </form>
                                @endif
                            @else
                                <div class="d-flex align-items-center gap-1.5">
                                    <span class="badge bg-light text-muted border">Guest</span>
                                </div>
                                <div class="text-muted font-monospace mt-0.5" style="font-size:0.72rem;">{{ Str::limit($item->cart?->session_id, 24) }}</div>
                            @endif
                        </td>
                        <td class="small">
                            <div class="fw-bold text-dark">{{ $item->product?->name }}</div>
                            <div class="text-muted font-monospace" style="font-size:0.73rem;">{{ $item->product?->sku ?: '—' }}</div>
                        </td>
                        <td class="small">
                            <span class="badge bg-light text-secondary border">{{ $item->variant?->name ?: 'Default' }}</span>
                        </td>
                        <td class="text-center small"><span class="badge bg-light text-dark border">{{ $item->quantity }}</span></td>
                        <td class="text-end small">{{ format_price($item->unit_price) }}</td>
                        <td class="text-end small fw-bold text-dark">{{ format_price($item->unit_price * $item->quantity) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state py-5">
                                <div class="empty-state-icon"><i class="bi bi-cart-x"></i></div>
                                <div class="empty-state-title">No Cart Items Found</div>
                                <p class="empty-state-desc">No active or abandoned cart items match the selected criteria.</p>
                                <a href="{{ route('admin.carts.index') }}" class="btn btn-sm btn-outline-secondary mt-2">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Clear Filters
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($cartItems->hasPages())
        <div class="card-footer d-flex align-items-center justify-content-between">
            <span class="small text-muted">Showing {{ $cartItems->firstItem() }} to {{ $cartItems->lastItem() }} of {{ $cartItems->total() }} entries</span>
            <div>{{ $cartItems->links() }}</div>
        </div>
    @endif
</div>
@endsection