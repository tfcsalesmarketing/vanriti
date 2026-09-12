@extends('admin.layouts.app')

@section('title', 'Order '.$order->order_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Order {{ $order->order_number }}</h5>
        <small class="text-muted">Placed on {{ $order->created_at->format('d M Y, h:i A') }}</small>
    </div>
    <div class="d-flex gap-2">
        @php $oClass = ['cancelled'=>'danger','failed'=>'danger','delivered'=>'success','shipped'=>'info','out_for_delivery'=>'info','pending'=>'secondary','confirmed'=>'primary','processing'=>'warning','packed'=>'warning']; @endphp
        <span class="badge bg-{{ $oClass[$order->order_status] ?? 'secondary' }} align-self-center">{{ ucwords(str_replace('_', ' ', $order->order_status)) }}</span>
        <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-sm btn-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Order Number</div>
                <div class="fw-bold">{{ $order->order_number }}</div>
                <div class="text-muted small mt-2">Date</div>
                <div class="fw-semibold small">{{ $order->created_at->format('d M Y, h:i A') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Payment</div>
                <div class="fw-bold small">{{ ucwords(str_replace('_', ' ', $order->payment_status)) }}</div>
                <div class="text-muted small mt-2">Method</div>
                <div class="fw-semibold small text-uppercase">{{ $order->payment_method }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Grand Total</div>
                <div class="fw-bold">{{ format_price($order->grand_total) }}</div>
                <div class="text-muted small mt-2">Paid / Due</div>
                <div class="fw-semibold small">{{ format_price($order->amount_paid) }} / {{ format_price($order->amount_due) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Customer</div>
                <div class="fw-bold small">{{ $order->user?->name ?: $order->billing_name }}</div>
                <div class="text-muted small">{{ $order->user?->email }}</div>
                <div class="text-muted small">{{ $order->user?->phone ?: $order->billing_mobile }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">Billing Address</div>
            <div class="card-body small">
                <div class="fw-semibold">{{ $order->billing_name }}</div>
                <div class="text-muted">{{ $order->billing_address_line1 }}</div>
                @if ($order->billing_address_line2)
                    <div class="text-muted">{{ $order->billing_address_line2 }}</div>
                @endif
                @if ($order->billing_landmark)
                    <div class="text-muted">{{ $order->billing_landmark }}</div>
                @endif
                <div class="text-muted">{{ $order->billing_city }}, {{ $order->billing_state }} - {{ $order->billing_pincode }}</div>
                <div class="text-muted">{{ $order->billing_country }}</div>
                <div class="text-muted mt-2">Ph: {{ $order->billing_mobile }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">Shipping Address</div>
            <div class="card-body small">
                <div class="fw-semibold">{{ $order->shipping_name }}</div>
                <div class="text-muted">{{ $order->shipping_address_line1 }}</div>
                @if ($order->shipping_address_line2)
                    <div class="text-muted">{{ $order->shipping_address_line2 }}</div>
                @endif
                @if ($order->shipping_landmark)
                    <div class="text-muted">{{ $order->shipping_landmark }}</div>
                @endif
                <div class="text-muted">{{ $order->shipping_city }}, {{ $order->shipping_state }} - {{ $order->shipping_pincode }}</div>
                <div class="text-muted">{{ $order->shipping_country }}</div>
                <div class="text-muted mt-2">Ph: {{ $order->shipping_mobile }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Items</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if ($item->image)
                                    @if (str_starts_with($item->image, 'http'))
                                        <img src="{{ $item->image }}" alt="" class="rounded" width="40" height="40" style="object-fit:cover;">
                                    @else
                                        <img src="{{ image_url($item->image) }}" alt="" class="rounded" width="40" height="40" style="object-fit:cover;">
                                    @endif
                                @else
                                    <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><i class="bi bi-image text-muted"></i></div>
                                @endif
                                <div>
                                    <div class="fw-semibold small">{{ $item->product_name }}</div>
                                    <div class="text-muted small">{{ $item->variant_name ?: ($item->sku ?: '') }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center small">{{ $item->quantity }}</td>
                        <td class="text-end small">{{ format_price($item->unit_price) }}</td>
                        <td class="text-end fw-semibold small">{{ format_price($item->total_price) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">Totals</div>
            <div class="card-body py-2 small">
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Subtotal</span><span>{{ format_price($order->subtotal) }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Discount</span><span>{{ format_price($order->discount_amount) }}</span></div>
                @if ($order->coupon_discount > 0)
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">
                            Coupon{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}
                            @if ($order->coupon_type || $order->coupon_value !== null)
                                <span class="d-block text-muted" style="font-size:0.8em;">{{ ucfirst((string) $order->coupon_type) }}{{ $order->coupon_value !== null ? ' '.format_price($order->coupon_value) : '' }}</span>
                            @endif
                        </span>
                        <span>- {{ format_price($order->coupon_discount) }}</span>
                    </div>
                @endif
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Taxable Value</span><span>{{ format_price($order->taxable_value) }}</span></div>
                @if ($order->isIntraState())
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">CGST{{ $order->uniformGstRate() !== null ? ' ('.number_format($order->uniformGstRate() / 2, 1, '.', '').'%)' : '' }}</span><span>{{ format_price($order->cgst_amount) }}</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">SGST{{ $order->uniformGstRate() !== null ? ' ('.number_format($order->uniformGstRate() / 2, 1, '.', '').'%)' : '' }}</span><span>{{ format_price($order->sgst_amount) }}</span></div>
                @else
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">IGST{{ $order->uniformGstRate() !== null ? ' ('.(float) $order->uniformGstRate().'%)' : '' }}</span><span>{{ format_price($order->igst_amount) }}</span></div>
                @endif
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Shipping</span><span>{{ format_price($order->shipping_charge) }}</span></div>
                <hr class="my-1">
                <div class="d-flex justify-content-between py-1 fw-bold"><span>Grand Total</span><span>{{ format_price($order->grand_total) }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Paid</span><span>{{ format_price($order->amount_paid) }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Due</span><span class="fw-semibold">{{ format_price($order->amount_due) }}</span></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">Status History</div>
            <div class="card-body">
                @forelse ($order->statusHistories as $history)
                    <div class="d-flex gap-2 mb-2 small">
                        <i class="bi bi-circle-fill mt-1" style="font-size:0.5rem;"></i>
                        <div>
                            <div class="fw-semibold">{{ ucwords(str_replace('_', ' ', $history->status)) }}</div>
                            <div class="text-muted" style="font-size:0.75rem;">{{ $history->created_at->format('d M Y, h:i A') }}</div>
                            @if ($history->description)
                                <div class="text-muted">{{ $history->description }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-muted small">No status history.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">Payments</div>
            <div class="card-body">
                @forelse ($order->payments as $payment)
                    <div class="d-flex justify-content-between small mb-2">
                        <div>
                            <span class="badge bg-{{ $payment->status === 'paid' ? 'success' : 'secondary' }}">{{ ucwords($payment->status) }}</span>
                            <div class="text-muted mt-1">{{ $payment->method ?: 'N/A' }} @if ($payment->payment_reference) · {{ $payment->payment_reference }} @endif</div>
                        </div>
                        <div class="fw-semibold">{{ format_price($payment->amount) }}</div>
                    </div>
                @empty
                    <div class="text-muted small">No payments recorded.</div>
                @endforelse
                @if ($order->shipments->count())
                    <div class="border-top pt-2 mt-2">
                        <div class="fw-semibold small mb-1">Shipments</div>
                        @foreach ($order->shipments as $shipment)
                            <div class="small text-muted">
                                <a href="{{ route('admin.shipments.show', $shipment) }}" class="text-decoration-none">Shipment #{{ $shipment->id }}</a>
                                @if ($shipment->tracking_number) · {{ $shipment->tracking_number }} @endif
                                · <span class="badge bg-secondary">{{ $shipment->status }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($order->internal_notes)
    <div class="card">
        <div class="card-header">Internal Notes</div>
        <div class="card-body small text-muted" style="white-space:pre-line;">{{ $order->internal_notes }}</div>
    </div>
@endif

{{-- ── ShipMojo Shipping Panel ─────────────────────────────────────── --}}
@if (setting('shipmojo_enabled'))
@php
    $shipment = $order->shipments->first();
    $pushed   = $shipment?->isPushedToShipMojo();
    $hasAwb   = $shipment?->hasAwb();
@endphp
<div class="card mt-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="fw-semibold">
            <i class="bi bi-truck me-1 text-success"></i> ShipMojo Shipping
        </span>
        <div class="d-flex align-items-center gap-2">
            @if ($pushed)
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    <i class="bi bi-check-circle me-1"></i>Pushed
                </span>
            @endif
            @if ($hasAwb)
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                    AWB: {{ $shipment->awb_number }}
                </span>
            @endif
        </div>
    </div>
    <div class="card-body">

        {{-- Status Row --}}
        @if ($shipment && $pushed)
        <div class="row g-2 mb-3 text-center small">
            <div class="col">
                <div class="text-muted">ShipMojo Ref</div>
                <div class="fw-semibold">{{ $shipment->shipmojo_reference_id ?? '—' }}</div>
            </div>
            <div class="col">
                <div class="text-muted">Courier</div>
                <div class="fw-semibold">{{ $shipment->courier ?? '—' }}</div>
            </div>
            <div class="col">
                <div class="text-muted">AWB</div>
                <div class="fw-semibold">{{ $shipment->awb_number ?? '—' }}</div>
            </div>
            <div class="col">
                <div class="text-muted">LR Number</div>
                <div class="fw-semibold">{{ $shipment->lr_number ?? '—' }}</div>
            </div>
            <div class="col">
                <div class="text-muted">Status</div>
                <div class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $shipment->status ?? '—') }}</div>
            </div>
            <div class="col">
                <div class="text-muted">Pushed At</div>
                <div class="fw-semibold">{{ $shipment->shipmojo_pushed_at?->format('d M, h:i A') ?? '—' }}</div>
            </div>
        </div>
        @endif

        {{-- Action Buttons --}}
        <div class="d-flex flex-wrap gap-2">

            {{-- 1. Push Order --}}
            @if (! $pushed)
            <form method="POST" action="{{ route('admin.orders.shipmojo.push', $order) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-success"
                    onclick="return confirm('Push order {{ $order->order_number }} to ShipMojo?')">
                    <i class="bi bi-cloud-upload me-1"></i> Push to ShipMojo
                </button>
            </form>
            @else
            <form method="POST" action="{{ route('admin.orders.shipmojo.push', $order) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary"
                    onclick="return confirm('Re-push order to ShipMojo?')">
                    <i class="bi bi-arrow-clockwise me-1"></i> Re-push
                </button>
            </form>
            @endif

            {{-- 2. Auto-Assign --}}
            @if ($pushed && ! $hasAwb)
            <form method="POST" action="{{ route('admin.orders.shipmojo.auto-assign', $order) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary"
                    onclick="return confirm('Auto-assign courier for this order?')">
                    <i class="bi bi-lightning me-1"></i> Auto-Assign Courier
                </button>
            </form>
            @endif

            {{-- 3. Schedule Pickup --}}
            @if ($pushed && ! $hasAwb)
            <form method="POST" action="{{ route('admin.orders.shipmojo.schedule-pickup', $order) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-info text-white"
                    onclick="return confirm('Schedule pickup for this order?')">
                    <i class="bi bi-calendar-check me-1"></i> Schedule Pickup
                </button>
            </form>
            @endif

            {{-- 4. Download Label --}}
            @if ($hasAwb)
            <a href="{{ route('admin.orders.shipmojo.label', $order) }}"
               class="btn btn-sm btn-dark" target="_blank">
                <i class="bi bi-printer me-1"></i> Download Label
            </a>
            @endif

            {{-- 5. Sync Tracking --}}
            @if ($hasAwb)
            <form method="POST" action="{{ route('admin.orders.shipmojo.track', $order) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-arrow-repeat me-1"></i> Sync Tracking
                </button>
            </form>
            @endif

            {{-- 6. Cancel --}}
            @if ($hasAwb && ! in_array($shipment->status, ['delivered', 'returned']))
            <form method="POST" action="{{ route('admin.orders.shipmojo.cancel', $order) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('Cancel this shipment in ShipMojo? This cannot be undone.')">
                    <i class="bi bi-x-circle me-1"></i> Cancel Shipment
                </button>
            </form>
            @endif

        </div>

        {{-- Tracking Events --}}
        @if ($shipment && $shipment->trackingEvents->count())
        <hr class="my-3">
        <div class="small fw-semibold mb-2 text-muted">Tracking Events</div>
        <ul class="list-group list-group-flush">
            @foreach ($shipment->trackingEvents->take(8) as $event)
            <li class="list-group-item px-0 py-1 small">
                <span class="badge bg-secondary me-2">{{ ucwords(str_replace('_',' ',$event->status)) }}</span>
                {{ $event->description }}
                @if ($event->location) · <em>{{ $event->location }}</em> @endif
                <span class="text-muted float-end">{{ \Carbon\Carbon::parse($event->event_date)->format('d M, H:i') }}</span>
            </li>
            @endforeach
        </ul>
        @endif

    </div>
</div>
@endif
{{-- ── End ShipMojo Panel ──────────────────────────────────────────── --}}

@endsection
