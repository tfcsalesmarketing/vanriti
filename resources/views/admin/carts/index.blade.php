@extends('admin.layouts.app')

@section('title', 'Cart Items')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Cart Items</h5>
        <small class="text-muted">{{ $cartItems->total() }} cart line items</small>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.carts.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Product</label>
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">All products</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">User</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">All users</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Owner</label>
                <select name="owner_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="users" @selected(request('owner_type') === 'users')>Registered users</option>
                    <option value="guests" @selected(request('owner_type') === 'guests')>Guests</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Guest session</label>
                <input type="text" name="session_id" class="form-control form-control-sm" placeholder="Session ID" value="{{ request('session_id') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-dark"><i class="bi bi-search me-1"></i>Filter</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.carts.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

@if ($topProducts->isNotEmpty())
<div class="card mb-3">
    <div class="card-header fw-bold small">Most added to cart</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-end">Quantity</th>
                    <th class="text-end">Cart lines</th>
                    <th class="text-end">Carts</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($topProducts as $product)
                    <tr>
                        <td class="small">
                            <div class="fw-semibold">{{ $product->product_name }}</div>
                            <div class="text-muted" style="font-size:0.75rem;">{{ $product->product_sku }}</div>
                        </td>
                        <td class="text-end">{{ (int) $product->total_quantity }}</td>
                        <td class="text-end">{{ (int) $product->line_count }}</td>
                        <td class="text-end">{{ (int) $product->cart_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Added</th>
                    <th>User / Session</th>
                    <th>Product</th>
                    <th>Variant</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Unit price</th>
                    <th class="text-end">Line total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cartItems as $item)
                    <tr>
                        <td class="small text-muted">{{ $item->created_at?->format('d M Y H:i') }}</td>
                        <td class="small">
                            @if ($item->cart?->owner_type === \App\Models\User::class && $item->cart?->owner)
                                <div class="fw-semibold">{{ $item->cart->owner->name }}</div>
                                <div class="text-muted" style="font-size:0.75rem;">{{ $item->cart->owner->email }}</div>
                            @else
                                <div class="fw-semibold">Guest</div>
                                <div class="text-muted" style="font-size:0.75rem;">{{ $item->cart?->session_id }}</div>
                            @endif
                        </td>
                        <td class="small">
                            <div class="fw-semibold">{{ $item->product?->name }}</div>
                            <div class="text-muted" style="font-size:0.75rem;">{{ $item->product?->sku }}</div>
                        </td>
                        <td class="small text-muted">{{ $item->variant?->name ?: 'Default' }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">{{ format_price($item->unit_price) }}</td>
                        <td class="text-end fw-semibold">{{ format_price($item->unit_price * $item->quantity) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No cart items found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $cartItems->links() }}
</div>
@endsection