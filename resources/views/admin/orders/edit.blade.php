@extends('admin.layouts.app')

@section('title', 'Edit Order '.$order->order_number)

@section('content')
@php
    $oClass = ['cancelled'=>'danger','failed'=>'danger','delivered'=>'success','shipped'=>'info','out_for_delivery'=>'info','pending'=>'secondary','confirmed'=>'primary','processing'=>'warning','packed'=>'warning'];
    $pClass = ['paid'=>'success','refunded'=>'secondary','partially_refunded'=>'warning','pending'=>'secondary','processing'=>'info','failed'=>'danger','cancelled'=>'danger'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Edit Order {{ $order->order_number }}</h5>
        <small class="text-muted">Placed on {{ $order->created_at->format('d M Y, h:i A') }}</small>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-{{ $oClass[$order->order_status] ?? 'secondary' }} align-self-center">{{ ucwords(str_replace('_', ' ', $order->order_status)) }}</span>
        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-info">View</a>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">Update Order Status</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small text-muted">New Status</label>
                        <select name="order_status" class="form-select form-select-sm" required>
                            @foreach ($orderStatuses as $status)
                                <option value="{{ $status }}" {{ $order->order_status === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-muted">Description (optional)</label>
                        <textarea name="description" class="form-control form-control-sm" rows="2" maxlength="500" placeholder="e.g. Package handed to courier"></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-circle me-1"></i>Update Status</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">Payment Status</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.orders.payment-status', $order) }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small text-muted">Payment Status</label>
                        <select name="payment_status" class="form-select form-select-sm" required>
                            <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="failed" {{ $order->payment_status === 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="cancelled" {{ $order->payment_status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="small text-muted mb-2">
                        Current: <span class="badge bg-{{ $pClass[$order->payment_status] ?? 'secondary' }}">{{ ucwords(str_replace('_', ' ', $order->payment_status)) }}</span>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-credit-card me-1"></i>Update Payment</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">Add Internal Note</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.orders.notes', $order) }}">
            @csrf
            <div class="mb-2">
                <textarea name="internal_notes" class="form-control form-control-sm" rows="2" maxlength="2000" placeholder="Add a note for your team..."></textarea>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-sticky me-1"></i>Add Note</button>
        </form>
        @if ($order->internal_notes)
            <hr>
            <div class="small text-muted" style="white-space:pre-line;">{{ $order->internal_notes }}</div>
        @endif
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
            <div class="card-header">Billing</div>
            <div class="card-body small">
                <div class="fw-semibold">{{ $order->billing_name }}</div>
                <div class="text-muted">{{ $order->billing_address_line1 }}</div>
                @if ($order->billing_address_line2)<div class="text-muted">{{ $order->billing_address_line2 }}</div>@endif
                @if ($order->billing_landmark)<div class="text-muted">{{ $order->billing_landmark }}</div>@endif
                <div class="text-muted">{{ $order->billing_city }}, {{ $order->billing_state }} - {{ $order->billing_pincode }}</div>
                <div class="text-muted mt-2">Ph: {{ $order->billing_mobile }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">Shipping</div>
            <div class="card-body small">
                <div class="fw-semibold">{{ $order->shipping_name }}</div>
                <div class="text-muted">{{ $order->shipping_address_line1 }}</div>
                @if ($order->shipping_address_line2)<div class="text-muted">{{ $order->shipping_address_line2 }}</div>@endif
                @if ($order->shipping_landmark)<div class="text-muted">{{ $order->shipping_landmark }}</div>@endif
                <div class="text-muted">{{ $order->shipping_city }}, {{ $order->shipping_state }} - {{ $order->shipping_pincode }}</div>
                <div class="text-muted mt-2">Ph: {{ $order->shipping_mobile }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Status History</div>
    <div class="card-body">
        @forelse ($order->statusHistories as $history)
            <div class="d-flex gap-2 mb-2 small">
                <i class="bi bi-circle-fill mt-1" style="font-size:0.5rem;"></i>
                <div>
                    <div class="fw-semibold">{{ ucwords(str_replace('_', ' ', $history->status)) }}</div>
                    <div class="text-muted" style="font-size:0.75rem;">{{ $history->created_at->format('d M Y, h:i A') }}</div>
                    @if ($history->description)<div class="text-muted">{{ $history->description }}</div>@endif
                </div>
            </div>
        @empty
            <div class="text-muted small">No status history.</div>
        @endforelse
    </div>
</div>
@endsection
