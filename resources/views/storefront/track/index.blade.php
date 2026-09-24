@extends('storefront.layouts.app')
@section('title', 'Track Order')

@section('content')
<div class="vr-section">
    <div class="container">
        <h1 class="vr-section-title mb-4">Track Order</h1>

        <div class="row justify-content-center mb-4">
            <div class="col-md-6 col-lg-5">
                <div class="vr-cart-item p-4">
                    <form action="{{ route('track.lookup') }}" method="POST" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Order Number</label>
                            <input type="text" name="order_number" class="form-control"
                                   placeholder="e.g. VAN-2026-000001"
                                   value="{{ old('order_number') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Mobile Number</label>
                            <input type="text" name="mobile" class="form-control"
                                   placeholder="Used during checkout"
                                   value="{{ old('mobile') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                        <button type="submit" class="btn btn-vr w-100">
                            <i class="bi bi-search me-1"></i>Track
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @if ($orders !== null && $orders->count() > 0)
            @foreach ($orders as $order)
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-8">
                        <div class="vr-cart-item p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="fw-bold">{{ $order->order_number }}</span>
                                    <span class="vr-pill-badge os-{{ $order->order_status }} ms-2">{{ ucfirst(str_replace('_', ' ', $order->order_status)) }}</span>
                                </div>
                                <span class="small text-muted">{{ $order->created_at->format('d M Y') }}</span>
                            </div>

                            <div class="small text-muted mb-3">
                                Total: <strong>{{ format_price($order->grand_total) }}</strong>
                                &middot; {{ $order->items->count() }} item(s)
                            </div>

                            @include('storefront.partials.order-status-steps', ['order' => $order])

                            <div class="d-flex flex-wrap gap-2 mb-3 mt-3">
                                @foreach ($order->items as $item)
                                    <div class="d-flex align-items-center gap-2 p-2 border rounded" style="font-size:0.82rem;">
                                        @if ($item->image)
                                            <img src="{{ image_url($item->image) }}" alt="" style="width:32px;height:32px;object-fit:cover;border-radius:6px;">
                                        @endif
                                        <span>{{ $item->product_name }}</span>
                                        <span class="text-muted">x{{ $item->quantity }}</span>
                                    </div>
                                @endforeach
                            </div>

                            @if ($order->shipments->count() > 0)
                                <h6 class="fw-bold mt-3 mb-2">Tracking Updates</h6>
                                @foreach ($order->shipments as $shipment)
                                    @if ($shipment->trackingEvents->count() > 0)
                                        <div class="border-start ps-3 mb-3" style="border-color:var(--vr-green) !important;">
                                            <div class="small text-muted mb-2">
                                                {{ $shipment->courier ?? 'Shipment' }}
                                                @if ($shipment->tracking_number)
                                                    &middot; {{ $shipment->tracking_number }}
                                                @endif
                                            </div>
                                            @foreach ($shipment->trackingEvents as $event)
                                                <div class="d-flex align-items-start gap-2 mb-2">
                                                    <div style="width:8px;height:8px;border-radius:50%;background:var(--vr-green);margin-top:6px;" class="flex-shrink-0"></div>
                                                    <div>
                                                        <div class="fw-semibold" style="font-size:0.82rem;">{{ $event->status }}</div>
                                                        @if ($event->description)
                                                            <div class="text-muted" style="font-size:0.78rem;">{{ $event->description }}</div>
                                                        @endif
                                                        <div class="text-muted" style="font-size:0.72rem;">
                                                            {{ $event->event_date ? $event->event_date->format('d M Y, h:i A') : '' }}
                                                            @if ($event->location)
                                                                &middot; {{ $event->location }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="small text-muted border rounded p-2 mb-2">
                                            No tracking events yet for this shipment.
                                        </div>
                                    @endif
                                @endforeach
                            @else
                                <div class="small text-muted border rounded p-2">
                                    Shipment information will be updated once your order is dispatched.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @elseif ($orders !== null && $orders->isEmpty())
            <div class="text-center text-muted py-3">
                <p>No orders found. Please check your order number and mobile number.</p>
            </div>
        @endif
    </div>
</div>
@endsection
