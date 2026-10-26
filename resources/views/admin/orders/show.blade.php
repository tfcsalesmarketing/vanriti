@extends('admin.layouts.app')

@section('title', 'Order '.$order->order_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Order {{ $order->order_number }}</h5>
        <small class="text-muted">Placed on {{ $order->created_at->format('d M Y, h:i A') }}</small>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        @php
            $oClass = ['cancelled'=>'danger','failed'=>'danger','delivered'=>'success','shipped'=>'info','out_for_delivery'=>'info','pending'=>'secondary','confirmed'=>'primary','processing'=>'warning','packed'=>'warning'];
            $custPhone = $phone ?? trim((string) ($order->shipping_mobile ?: ($order->billing_mobile ?: $order->user?->phone)));
        @endphp
        <span class="badge bg-{{ $oClass[$order->order_status] ?? 'secondary' }} align-self-center py-2 px-3">{{ ucwords(str_replace('_', ' ', $order->order_status)) }}</span>
        @if ($custPhone)
            <button type="button" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#sendWhatsAppModal">
                <i class="bi bi-whatsapp"></i>
                <span>WhatsApp Customer</span>
                @if (isset($conversation) && $conversation->isWindowOpen())
                    <span class="badge bg-white text-success ms-1 px-1 py-0" style="font-size:0.65rem;">Live</span>
                @endif
            </button>
            @if (isset($conversation))
                <a href="{{ route('admin.whatsapp.inbox', ['conversation' => $conversation->id]) }}" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1" target="_blank" title="View conversation stream in Live Inbox">
                    <i class="bi bi-chat-dots-fill"></i>
                    <span>Live Chat</span>
                </a>
            @endif
        @endif
        <a href="{{ route('admin.orders.invoice', $order) }}" class="btn btn-sm btn-outline-dark" target="_blank" title="Print/Download GST Tax Invoice">
            <i class="bi bi-file-earmark-text me-1"></i>Tax Invoice
        </a>
        <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-sm btn-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
</div>

@if ($order->payment_method === 'cod' && in_array($order->order_status, ['on_hold', 'pending']))
    <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 mb-3 rounded-3 shadow-xs">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-clock-history fs-4 text-warning-emphasis"></i>
            <div>
                <strong class="d-block text-dark">COD Order Awaiting Customer Confirmation</strong>
                <span class="small text-muted">Customer has received an automated WhatsApp 1-tap confirmation prompt.</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('admin.orders.confirm-cod', $order) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-success">
                    <i class="bi bi-check-circle me-1"></i>Confirm COD Manually
                </button>
            </form>
            <form method="POST" action="{{ route('admin.orders.resend-cod', $order) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-dark">
                    <i class="bi bi-whatsapp me-1 text-success"></i>Resend WhatsApp Prompt
                </button>
            </form>
        </div>
    </div>
@endif

{{-- ── Fulfillment Stepper Tracker ───────────────────────────────── --}}
@php
    $isCancelled = in_array($order->order_status, ['cancelled', 'failed']);
    $statusOrder = ['pending' => 1, 'confirmed' => 1, 'processing' => 2, 'packed' => 2, 'shipped' => 3, 'out_for_delivery' => 4, 'delivered' => 5];
    $currentStep = $statusOrder[$order->order_status] ?? 1;
    $shipment = $order->shipments->first();
@endphp

@if ($isCancelled)
    <div class="alert alert-danger d-flex align-items-center gap-3 p-3 mb-3 rounded-3 shadow-xs">
        <i class="bi bi-x-octagon-fill fs-3 text-danger"></i>
        <div>
            <div class="fw-bold">Order Cancelled</div>
            <div class="small text-danger-emphasis">
                This order was cancelled on {{ $order->cancelled_at ? $order->cancelled_at->format('d M Y, h:i A') : $order->updated_at->format('d M Y') }}.
                @if ($order->cancellation_reason)
                    <div class="mt-0.5"><strong>Reason:</strong> {{ $order->cancellation_reason }}</div>
                @endif
            </div>
        </div>
    </div>
@else
    <div class="card mb-3 border-0 shadow-xs" style="border-radius:12px; background:#ffffff;">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="small fw-bold text-uppercase text-muted" style="letter-spacing:0.05em; font-size:0.75rem;">Fulfillment Progress</span>
                <span class="badge bg-light text-dark border small fw-semibold">
                    Current: {{ ucwords(str_replace('_', ' ', $order->order_status)) }}
                </span>
            </div>
            <div class="stepper-wrapper d-flex justify-content-between position-relative flex-wrap gap-2">
                @php
                    $steps = [
                        1 => ['title' => 'Placed & Confirmed', 'icon' => 'bi-bag-check', 'date' => $order->created_at->format('d M, h:i A')],
                        2 => ['title' => 'Packed & Ready', 'icon' => 'bi-box-seam', 'date' => $order->statusHistories->whereIn('status', ['processing', 'packed'])->first()?->created_at?->format('d M, h:i A')],
                        3 => ['title' => 'Shipped', 'icon' => 'bi-truck', 'date' => $shipment?->shipped_at?->format('d M, h:i A')],
                        4 => ['title' => 'Out for Delivery', 'icon' => 'bi-geo-alt', 'date' => $order->statusHistories->where('status', 'out_for_delivery')->first()?->created_at?->format('d M, h:i A')],
                        5 => ['title' => 'Delivered', 'icon' => 'bi-check2-circle', 'date' => $shipment?->delivered_at?->format('d M, h:i A')],
                    ];
                @endphp
                @foreach ($steps as $stepNum => $s)
                    @php
                        $isCompleted = $currentStep > $stepNum || ($currentStep === $stepNum && $stepNum === 5);
                        $isCurrent = $currentStep === $stepNum && $stepNum !== 5;
                    @endphp
                    <div class="stepper-item text-center flex-fill position-relative" style="min-width: 120px;">
                        <div class="stepper-icon mx-auto mb-2 rounded-circle d-flex align-items-center justify-content-center"
                             style="width:38px; height:38px; font-size:1.1rem; transition:all 0.2s;
                             {{ $isCompleted ? 'background:#059669; color:#fff; box-shadow:0 0 0 3px #d1fae5;' : ($isCurrent ? 'background:#075e54; color:#fff; box-shadow:0 0 0 4px #a7f3d0;' : 'background:#f3f4f6; color:#9ca3af; border:1px solid #e5e7eb;') }}">
                            <i class="bi {{ $isCompleted ? 'bi-check-lg' : $s['icon'] }}"></i>
                        </div>
                        <div class="stepper-title fw-semibold small {{ $isCurrent || $isCompleted ? 'text-dark' : 'text-muted' }}" style="font-size:0.78rem;">
                            {{ $s['title'] }}
                        </div>
                        @if ($s['date'])
                            <div class="stepper-date text-muted" style="font-size:0.68rem;">{{ $s['date'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Order Number</div>
                <div class="fw-bold d-flex align-items-center gap-2">
                    {{ $order->order_number }}
                    @if (($order->checkout_source ?? '') === 'whatsapp')
                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-whatsapp"></i> WhatsApp</span>
                    @else
                        <span class="badge bg-light text-secondary border">Web</span>
                    @endif
                </div>
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
                <div class="text-muted small">{{ $custPhone }}</div>
                @if ($custPhone)
                    <div class="mt-2 d-flex gap-1 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-success py-1 px-2 d-inline-flex align-items-center gap-1" style="font-size:0.75rem;" data-bs-toggle="modal" data-bs-target="#sendWhatsAppModal">
                            <i class="bi bi-whatsapp"></i> Send WhatsApp
                        </button>
                        @if (isset($conversation))
                            <a href="{{ route('admin.whatsapp.inbox', ['conversation' => $conversation->id]) }}" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size:0.75rem;" target="_blank">
                                <i class="bi bi-box-arrow-up-right"></i> Chat
                            </a>
                        @endif
                    </div>
                @endif
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

@if ($order->whatsAppCheckoutSession)
@php $waSession = $order->whatsAppCheckoutSession; @endphp
<div class="card mb-3 border-success-subtle">
    <div class="card-header bg-success-subtle text-success fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-whatsapp me-2"></i>WhatsApp Commerce Session</span>
        <span class="badge bg-success text-white">{{ $waSession->status }}</span>
    </div>
    <div class="card-body small">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted">Customer Phone</div>
                <div class="fw-semibold">{{ $waSession->phone }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Session Token</div>
                <div class="font-monospace small text-truncate" title="{{ $waSession->session_token }}">{{ $waSession->session_token }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Razorpay Order ID</div>
                <div class="font-monospace">{{ $waSession->razorpay_order_id ?: '—' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Created / Expires</div>
                <div>{{ $waSession->created_at->format('d M H:i') }} / {{ $waSession->expires_at?->format('d M H:i') ?: '—' }}</div>
            </div>
        </div>
    </div>
</div>
@endif

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
                    <div class="small mb-3 pb-2 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-{{ $payment->status === 'paid' ? 'success' : ($payment->status === 'failed' ? 'danger' : 'secondary') }}">{{ ucwords($payment->status) }}</span>
                                <div class="text-muted mt-1">{{ $payment->method ?: 'N/A' }} @if ($payment->payment_reference) · {{ $payment->payment_reference }} @endif</div>
                            </div>
                            <div class="fw-semibold">{{ format_price($payment->amount) }}</div>
                        </div>
                        @if (!empty($payment->gateway_response['failure_reason']))
                            <div class="alert alert-danger py-1 px-2 mt-2 mb-0 small" style="font-size:0.75rem;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <strong>Failure Reason:</strong> {{ $payment->gateway_response['failure_reason'] }}
                            </div>
                        @elseif (!empty($payment->gateway_response['dropoff_reason']))
                            <div class="alert alert-warning py-1 px-2 mt-2 mb-0 small" style="font-size:0.75rem;">
                                <i class="bi bi-box-arrow-right me-1"></i>
                                <strong>Drop-off Note:</strong> {{ $payment->gateway_response['dropoff_reason'] }}
                            </div>
                        @endif
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
            <form method="POST" action="{{ route('admin.orders.shipmojo.push', $order) }}" class="d-inline" data-confirm="Push order {{ $order->order_number }} to ShipMojo?">
                @csrf
                <button type="submit" class="btn btn-sm btn-success">
                    <i class="bi bi-cloud-upload me-1"></i> Push to ShipMojo
                </button>
            </form>
            @else
            <form method="POST" action="{{ route('admin.orders.shipmojo.push', $order) }}" class="d-inline" data-confirm="Re-push order to ShipMojo?">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise me-1"></i> Re-push
                </button>
            </form>
            @endif

            {{-- 3. Schedule Pickup --}}
            @if ($pushed && ! $hasAwb)
            <form method="POST" action="{{ route('admin.orders.shipmojo.schedule-pickup', $order) }}" class="d-inline" data-confirm="Schedule pickup for this order?">
                @csrf
                <button type="submit" class="btn btn-sm btn-info text-white">
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
            <form method="POST" action="{{ route('admin.orders.shipmojo.cancel', $order) }}" class="d-inline" data-confirm="Cancel this shipment in ShipMojo? This cannot be undone.">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger">
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
{{-- ── WhatsApp Interactive Send Modal ────────────────────────────── --}}
@if ($custPhone)
<div class="modal fade" id="sendWhatsAppModal" tabindex="-1" aria-labelledby="sendWhatsAppModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header text-white" style="background:#075e54;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-whatsapp fs-5"></i>
                    <div>
                        <h6 class="modal-title mb-0 fw-bold" id="sendWhatsAppModalLabel">Send WhatsApp to Customer</h6>
                        <small class="opacity-75" style="font-size:0.75rem;">{{ $order->user?->name ?: ($order->shipping_name ?: $order->billing_name) }} · {{ $custPhone }}</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.orders.send-whatsapp', $order) }}" id="orderWhatsAppForm">
                @csrf
                <div class="modal-body p-3">
                    {{-- Session Status Pill --}}
                    <div class="d-flex align-items-center justify-content-between p-2 mb-3 rounded" style="background:#f0fdf4; border:1px solid #bbf7d0;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-shield-check text-success fs-5"></i>
                            <div>
                                <div class="fw-semibold small text-success">
                                    @if (isset($conversation) && $conversation->isWindowOpen())
                                        24-Hour Active Session
                                    @else
                                        Meta Cloud API Mode
                                    @endif
                                </div>
                                <div class="text-muted" style="font-size:0.72rem;">
                                    @if (isset($conversation) && $conversation->isWindowOpen())
                                        Customer messaged recently. Free-form text and Meta templates are both permitted.
                                    @else
                                        Session window is closed. Send via approved Meta Template to bypass the window.
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Send Type Selection --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Message Mode</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="send_type" id="sendTypeTemplate" value="template" checked autocomplete="off">
                            <label class="btn btn-outline-success btn-sm py-2" for="sendTypeTemplate">
                                <i class="bi bi-file-earmark-text me-1"></i> Meta Approved Template
                            </label>

                            <input type="radio" class="btn-check" name="send_type" id="sendTypeText" value="custom_text" autocomplete="off" {{ (isset($conversation) && $conversation->isWindowOpen()) ? '' : 'disabled' }}>
                            <label class="btn btn-outline-success btn-sm py-2 {{ (isset($conversation) && $conversation->isWindowOpen()) ? '' : 'text-muted' }}" for="sendTypeText" title="{{ (isset($conversation) && $conversation->isWindowOpen()) ? 'Free-form message' : 'Requires active 24h customer session' }}">
                                <i class="bi bi-chat-text me-1"></i> Direct Message
                            </label>
                        </div>
                    </div>

                    {{-- Template Selector --}}
                    <div id="templateSection">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Select Order Journey Template</label>
                            <select name="template_name" id="orderTemplateSelect" class="form-select form-select-sm">
                                <optgroup label="🚚 Order Journey Presets">
                                    <option value="order_dispatched" selected>📦 Order Dispatched & Tracking Link</option>
                                    <option value="order_out_for_delivery">🚚 Out for Delivery Alert</option>
                                    <option value="order_delivered">🎉 Order Delivered & Reviews</option>
                                    <option value="order_confirmation">✅ Order Confirmation</option>
                                    <option value="checkout_recovery">🛒 Abandoned Checkout Recovery</option>
                                </optgroup>
                                @if (isset($approvedTemplates) && $approvedTemplates->count())
                                    <optgroup label="📋 All Approved Meta Templates">
                                        @foreach ($approvedTemplates as $tpl)
                                            <option value="{{ $tpl->name }}">{{ $tpl->name }} ({{ $tpl->category }})</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                        </div>

                        {{-- Parameter Preview Card --}}
                        <div class="card bg-light border-0 p-2 mb-2">
                            <div class="small fw-semibold text-muted mb-1" style="font-size:0.75rem;">Template Parameters Preview:</div>
                            <div class="small text-secondary" style="font-size:0.78rem;">
                                <div>• <strong>Customer:</strong> {{ explode(' ', $order->user?->name ?: ($order->shipping_name ?: $order->billing_name))[0] }}</div>
                                <div>• <strong>Order #:</strong> {{ $order->order_number }}</div>
                                <div>• <strong>Courier:</strong> {{ $order->shipments()->latest()->first()?->courier ?: 'ShipMojo Express' }}</div>
                                <div>• <strong>AWB / Tracking:</strong> {{ $order->shipments()->latest()->first()?->awb_number ?: ($order->shipments()->latest()->first()?->tracking_number ?: $order->order_number) }}</div>
                                <div>• <strong>Link:</strong> {{ route('account.order', $order) }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Direct Custom Text Section --}}
                    <div id="customTextSection" style="display:none;">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Direct Message to Customer</label>
                            <textarea name="custom_message" class="form-control form-control-sm" rows="4" placeholder="Type your custom update or customer service message..."></textarea>
                            <small class="text-muted" style="font-size:0.72rem;">Delivered directly to {{ $custPhone }}. Response will appear in Live Inbox.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light p-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success px-3 d-inline-flex align-items-center gap-1 shadow-sm">
                        <i class="bi bi-send-fill"></i>
                        <span>Send WhatsApp Now</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    var radioTemplate = document.getElementById('sendTypeTemplate');
    var radioText = document.getElementById('sendTypeText');
    function toggleOrderSendMode() {
        var isTemplate = radioTemplate && radioTemplate.checked;
        var tplSec = document.getElementById('templateSection');
        var custSec = document.getElementById('customTextSection');
        if (tplSec) tplSec.style.display = isTemplate ? 'block' : 'none';
        if (custSec) custSec.style.display = isTemplate ? 'none' : 'block';
    }
    if (radioTemplate) radioTemplate.addEventListener('change', toggleOrderSendMode);
    if (radioText) radioText.addEventListener('change', toggleOrderSendMode);
});
</script>
@endpush
@endif

@endsection
