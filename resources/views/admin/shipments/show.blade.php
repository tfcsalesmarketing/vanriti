@extends('admin.layouts.app')

@section('title', 'Shipment #'.$shipment->id)

@section('content')
@php
    $classes = ['delivered'=>'success','shipped'=>'info','out_for_delivery'=>'info','packed'=>'primary','pending'=>'secondary','returning'=>'warning','returned'=>'secondary','failed'=>'danger'];
    $trackClasses = ['delivered'=>'success','shipped'=>'info','out_for_delivery'=>'info','packed'=>'primary','failed'=>'danger','returning'=>'warning'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Shipment #{{ $shipment->id }}</h5>
        <small class="text-muted">Created on {{ $shipment->created_at->format('d M Y, h:i A') }}</small>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-{{ $classes[$shipment->status] ?? 'secondary' }} align-self-center">{{ ucwords(str_replace('_', ' ', $shipment->status)) }}</span>
        <a href="{{ route('admin.shipments.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">Shipment Info</div>
            <div class="card-body small">
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Courier</span><span>{{ $shipment->courier ?: '—' }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Tracking #</span><span>{{ $shipment->tracking_number ?: '—' }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">AWB #</span><span>{{ $shipment->awb_number ?: '—' }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Method</span><span>{{ $shipment->shipping_method ?: '—' }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Weight</span><span>{{ $shipment->weight ? $shipment->weight.' kg' : '—' }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Shipped At</span><span>{{ $shipment->shipped_at?->format('d M Y, h:i A') ?: '—' }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Est. Delivery</span><span>{{ $shipment->estimated_delivery?->format('d M Y') ?: '—' }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Delivered At</span><span>{{ $shipment->delivered_at?->format('d M Y, h:i A') ?: '—' }}</span></div>
                @if ($shipment->notes)
                    <div class="text-muted mt-2">Notes</div>
                    <div>{{ $shipment->notes }}</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">Order</div>
            <div class="card-body small">
                @if ($shipment->order)
                    <a href="{{ route('admin.orders.show', $shipment->order) }}" class="text-decoration-none fw-semibold">{{ $shipment->order->order_number }}</a>
                    <div class="text-muted">Customer: {{ $shipment->order->user?->name ?: $shipment->order->billing_name }}</div>
                    <div class="text-muted">Total: {{ format_price($shipment->order->grand_total) }}</div>
                    <div class="text-muted">Status: <span class="badge bg-secondary">{{ ucwords(str_replace('_', ' ', $shipment->order->order_status)) }}</span></div>
                @else
                    <div class="text-muted">Order not found.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Add Tracking Event</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.shipments.tracking', $shipment) }}" class="row g-2">
            @csrf
            <div class="col-md-2">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select form-select-sm" required>
                    @foreach (['packed', 'shipped', 'out_for_delivery', 'delivered', 'failed', 'returning'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Description</label>
                <input type="text" name="description" class="form-control form-control-sm" maxlength="500" placeholder="e.g. Package out for delivery">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Location</label>
                <input type="text" name="location" class="form-control form-control-sm" maxlength="255" placeholder="e.g. New Delhi">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Event Date</label>
                <input type="datetime-local" name="event_date" class="form-control form-control-sm" value="{{ now()->format('Y-m-d\TH:i') }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Est. Delivery</label>
                <input type="datetime-local" name="estimated_delivery" class="form-control form-control-sm" value="{{ $shipment->estimated_delivery?->format('Y-m-d\TH:i') }}">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-sm btn-primary w-100">Add</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Tracking Timeline</div>
    <div class="card-body">
        @forelse ($shipment->trackingEvents as $event)
            <div class="d-flex gap-2 mb-3 small">
                <div class="d-flex flex-column align-items-center">
                    <i class="bi bi-circle-fill text-{{ $trackClasses[$event->status] ?? 'secondary' }}" style="font-size:0.5rem;"></i>
                </div>
                <div>
                    <div class="fw-semibold">{{ ucwords(str_replace('_', ' ', $event->status)) }}</div>
                    <div class="text-muted" style="font-size:0.75rem;">{{ $event->event_date?->format('d M Y, h:i A') }}</div>
                    @if ($event->location)<div class="text-muted">{{ $event->location }}</div>@endif
                    @if ($event->description)<div>{{ $event->description }}</div>@endif
                </div>
            </div>
        @empty
            <div class="text-muted small">No tracking events yet.</div>
        @endforelse
    </div>
</div>
@endsection
