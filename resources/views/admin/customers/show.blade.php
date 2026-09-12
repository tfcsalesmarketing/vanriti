@extends('admin.layouts.app')

@section('title', 'Customer: ' . $user->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Customer Details</h5>
    <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3">
            <span class="avatar-circle bg-primary text-white" style="width:60px;height:60px;font-size:1.5rem;">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
            <div>
                <h5 class="mb-1 fw-bold">{{ $user->name }}</h5>
                <div class="text-muted small">{{ $user->email }}{{ $user->phone ? ' | ' . $user->phone : '' }}</div>
                <div class="mt-1">
                    <span class="badge {{ $user->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($user->status) }}</span>
                    <span class="badge bg-light text-dark">Joined {{ $user->created_at->format('d M Y') }}</span>
                </div>
            </div>
            <div class="ms-auto">
                <form method="POST" action="{{ route('admin.customers.toggle', $user->id) }}" onsubmit="return confirm('{{ $user->status === 'active' ? 'Deactivate' : 'Activate' }} this customer?')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-{{ $user->status === 'active' ? 'warning' : 'success' }}">
                        <i class="bi {{ $user->status === 'active' ? 'bi-person-x' : 'bi-person-check' }} me-1"></i>{{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h4 class="fw-bold mb-0">{{ $stats['total_orders'] }}</h4>
                <div class="text-muted small">Total Orders</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h4 class="fw-bold mb-0">{{ format_price($stats['total_spent']) }}</h4>
                <div class="text-muted small">Total Spent</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h4 class="fw-bold mb-0">{{ $stats['last_order_date'] ? $stats['last_order_date']->format('d M Y') : '—' }}</h4>
                <div class="text-muted small">Last Order</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Addresses</div>
            <div class="card-body">
                @forelse ($addresses as $address)
                    <div class="border rounded p-3 mb-2">
                        <div class="fw-semibold small">{{ $address->full_name }}</div>
                        <div class="small text-muted">
                            {{ $address->address_line1 }}{{ $address->address_line2 ? ', ' . $address->address_line2 : '' }}<br>
                            {{ $address->landmark ? $address->landmark . ', ' : '' }}{{ $address->city }}, {{ $address->state }} - {{ $address->pincode }}<br>
                            {{ $address->country }}
                        </div>
                        <div class="small mt-1">
                            <span class="badge bg-light text-dark">{{ ucfirst($address->type) }}</span>
                            @if ($address->is_default)
                                <span class="badge bg-primary">Default</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No addresses on file.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header fw-semibold">Reviews</div>
            <div class="card-body">
                @forelse ($reviews as $review)
                    <div class="border-bottom py-2">
                        <div class="d-flex justify-content-between">
                            <span class="small fw-semibold">{{ $review->product?->name ?: 'Product' }}</span>
                            <span class="small text-muted">{{ $review->created_at->format('d M Y') }}</span>
                        </div>
                        <div class="small">
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bi {{ $i <= $review->rating ? 'bi-star-fill text-warning' : 'bi-star text-muted' }}"></i>
                            @endfor
                            <span class="badge bg-light text-dark">{{ ucfirst($review->status) }}</span>
                        </div>
                        @if ($review->comment)
                            <div class="text-muted small mt-1">{{ Str::limit($review->comment, 100) }}</div>
                        @endif
                    </div>
                @empty
                    <p class="text-muted small mb-0">No reviews yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header fw-semibold">Orders</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $orderStatusColors = [
                                'pending' => 'warning', 'confirmed' => 'info', 'processing' => 'info',
                                'shipped' => 'primary', 'out_for_delivery' => 'primary', 'delivered' => 'success',
                                'cancelled' => 'danger', 'failed' => 'danger',
                            ];
                        @endphp
                        @forelse ($orders as $order)
                            <tr>
                                <td class="small fw-semibold">{{ $order->order_number }}</td>
                                <td class="text-end small">{{ format_price($order->grand_total) }}</td>
                                <td>
                                    <span class="badge bg-{{ $orderStatusColors[$order->order_status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $order->order_status)) }}</span>
                                </td>
                                <td class="small text-muted">{{ $order->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
