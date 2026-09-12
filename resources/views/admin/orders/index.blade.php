@extends('admin.layouts.app')

@section('title', 'Orders')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Orders</h5>
        <small class="text-muted">{{ $orders->total() }} orders total</small>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search order #, email, name..." value="{{ request('q') }}">
            </div>
            <div class="col-md-2">
                <select name="order_status" class="form-select form-select-sm">
                    <option value="">All Order Status</option>
                    @foreach ($orderStatuses as $status)
                        <option value="{{ $status }}" {{ request('order_status') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="payment_status" class="form-select form-select-sm">
                    <option value="">All Payment Status</option>
                    @foreach ($paymentStatuses as $status)
                        <option value="{{ $status }}" {{ request('payment_status') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-dark w-100"><i class="bi bi-search"></i></button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th class="text-end">Items</th>
                    <th class="text-end">Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $orderClasses = [
                        'cancelled' => 'danger', 'failed' => 'danger', 'delivered' => 'success',
                        'shipped' => 'info', 'out_for_delivery' => 'info', 'pending' => 'secondary',
                        'confirmed' => 'primary', 'processing' => 'warning', 'packed' => 'warning',
                    ];
                    $paymentClasses = [
                        'paid' => 'success', 'refunded' => 'secondary', 'partially_refunded' => 'warning',
                        'pending' => 'secondary', 'processing' => 'info', 'failed' => 'danger', 'cancelled' => 'danger',
                    ];
                @endphp
                @forelse ($orders as $order)
                    <tr>
                        <td class="small text-primary fw-semibold">{{ $order->order_number }}</td>
                        <td class="small">{{ $order->created_at->format('d M Y') }}</td>
                        <td>
                            <div class="fw-semibold small">{{ $order->user?->name ?: $order->billing_name }}</div>
                            <div class="text-muted small">{{ $order->user?->email }}</div>
                        </td>
                        <td class="text-end small">{{ $order->items_count }}</td>
                        <td class="text-end fw-semibold small">{{ format_price($order->grand_total) }}</td>
                        <td>
                            <span class="badge bg-{{ $paymentClasses[$order->payment_status] ?? 'secondary' }}">{{ ucwords(str_replace('_', ' ', $order->payment_status)) }}</span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $orderClasses[$order->order_status] ?? 'secondary' }}">{{ ucwords(str_replace('_', ' ', $order->order_status)) }}</span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $orders->links() }}
    </div>
</div>
@endsection
