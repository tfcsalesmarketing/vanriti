@extends('admin.layouts.app')

@section('title', 'Shipments')

@section('content')
@php
    $classes = ['delivered'=>'success','shipped'=>'info','out_for_delivery'=>'info','packed'=>'primary','pending'=>'secondary','returning'=>'warning','returned'=>'secondary','failed'=>'danger'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Shipments</h5>
        <small class="text-muted">{{ $shipments->total() }} shipments total</small>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.shipments.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-dark w-100"><i class="bi bi-search"></i></button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.shipments.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Shipment</th>
                    <th>Order #</th>
                    <th>Courier</th>
                    <th>Tracking #</th>
                    <th>Status</th>
                    <th>Shipped At</th>
                    <th>Est. Delivery</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shipments as $shipment)
                    <tr>
                        <td class="small text-primary fw-semibold">#{{ $shipment->id }}</td>
                        <td class="small">{{ $shipment->order?->order_number ?: '—' }}</td>
                        <td class="small">{{ $shipment->courier ?: '—' }}</td>
                        <td class="small">{{ $shipment->tracking_number ?: '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $classes[$shipment->status] ?? 'secondary' }}">{{ ucwords(str_replace('_', ' ', $shipment->status)) }}</span>
                        </td>
                        <td class="small">{{ $shipment->shipped_at?->format('d M Y') }}</td>
                        <td class="small">{{ $shipment->estimated_delivery?->format('d M Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.shipments.show', $shipment) }}" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No shipments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $shipments->links() }}
    </div>
</div>
@endsection
