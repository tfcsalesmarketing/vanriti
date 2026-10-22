@extends('admin.layouts.app')

@section('title', 'Wishlist Items')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Wishlist Items</h5>
        <small class="text-muted">{{ $wishlistItems->total() }} wishlist items</small>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.wishlists.index') }}" class="row g-2 align-items-end">
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
                <a href="{{ route('admin.wishlists.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

@if ($topProducts->isNotEmpty())
<div class="card mb-3">
    <div class="card-header fw-bold small">Most wishlisted products</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-end">Wishlist items</th>
                    <th class="text-end">Wishlists</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($topProducts as $product)
                    <tr>
                        <td class="small">
                            <div class="fw-semibold">{{ $product->product_name }}</div>
                            <div class="text-muted" style="font-size:0.75rem;">{{ $product->product_sku }}</div>
                        </td>
                        <td class="text-end">{{ (int) $product->wishlist_count }}</td>
                        <td class="text-end">{{ (int) $product->wishlist_list_count }}</td>
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
                    <th>SKU</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($wishlistItems as $item)
                    <tr>
                        <td class="small text-muted">{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y H:i') }}</td>
                        <td class="small">
                            @if ($item->owner_type === \App\Models\User::class && $item->user_name)
                                <div class="fw-semibold">{{ $item->user_name }}</div>
                                <div class="text-muted" style="font-size:0.75rem;">{{ $item->user_email }}</div>
                            @else
                                <div class="fw-semibold">Guest</div>
                                <div class="text-muted" style="font-size:0.75rem;">{{ $item->session_id }}</div>
                            @endif
                        </td>
                        <td class="small">
                            <div class="fw-semibold">{{ $item->product_name }}</div>
                        </td>
                        <td class="small text-muted">{{ $item->product_sku }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No wishlist items found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $wishlistItems->links() }}
</div>
@endsection