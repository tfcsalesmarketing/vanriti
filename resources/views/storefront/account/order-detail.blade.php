@extends('storefront.layouts.app')

@section('title', 'Order '.$order->order_number)
@section('robots', 'noindex, nofollow')

@section('content')
@php
    $statusColors = [
        'pending' => 'ps-pending', 'confirmed' => 'os-processing', 'processing' => 'os-processing',
        'packed' => 'os-processing', 'shipped' => 'os-shipped', 'out_for_delivery' => 'os-shipped',
        'delivered' => 'os-delivered', 'cancelled' => 'os-cancelled', 'failed' => 'os-cancelled',
    ];
    $reviewed = $order->items->map(fn ($i) => $i->reviewExist)->all() ?? [];
    $alreadyReviewing = $order->items->filter(fn ($i) => ($i->reviewExist ?? false));
    $hasReturn = $order->returnRequests->isNotEmpty();
@endphp

<div class="container py-4">
    <nav class="vr-breadcrumb mb-3">
        <a href="{{ route('account.orders') }}">My Orders</a> &rsaquo;
        <span>{{ $order->order_number }}</span>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h5 class="fw-bold mb-0">Order {{ $order->order_number }}</h5>
        <span class="vr-pill-badge {{ $statusColors[$order->order_status] ?? 'ps-pending' }}">
            {{ ucwords(str_replace('_', ' ', $order->order_status)) }}
        </span>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            @foreach ($order->items as $item)
                <div class="card border-0 shadow-sm mb-3" style="border-radius: 14px;">
                    <div class="card-body p-3 d-flex gap-3 align-items-start">
                        @if ($item->image)
                            <img src="{{ image_url($item->image) }}"
                                 alt="{{ $item->product_name }}" class="rounded-3" style="width:70px;height:70px;object-fit:cover;background:var(--vr-cream);">
                        @else
                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:70px;height:70px;background:var(--vr-cream);">
                                <i class="bi bi-box text-muted"></i>
                            </div>
                        @endif
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">{{ $item->product_name }}</div>
                            @if ($item->variant_name)
                                <div class="small text-muted">{{ $item->variant_name }} &middot; {{ $item->sku }}</div>
                            @endif
                            <div class="small text-muted mt-1">
                                Qty: {{ $item->quantity }} &times; {{ format_price($item->unit_price) }}
                            </div>
                        </div>
                        <div class="text-end small">
                            <div class="fw-semibold">{{ format_price($item->total_price) }}</div>

                            @if ($order->order_status === 'delivered' && ! $alreadyReviewing->contains('order_item_id', $item->id) && ! $item->reviewExist)
                                <button class="btn btn-sm btn-vr-outline mt-2" data-bs-toggle="collapse" data-bs-target="#review-{{ $item->id }}">
                                    <i class="bi bi-star me-1"></i>Review
                                </button>
                                <div class="collapse mt-2" id="review-{{ $item->id }}">
                                    <form method="POST" action="{{ route('account.order.review', $order) }}" class="text-start" novalidate>
                                        @csrf
                                        <input type="hidden" name="order_item_id" value="{{ $item->id }}">
                                        <div class="mb-2">
                                            <select name="rating" class="form-select form-select-sm">
                                                @for ($i = 5; $i >= 1; $i--)
                                                    <option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                                                @endfor
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                        <div class="mb-2">
                                            <input type="text" name="title" class="form-control form-control-sm" placeholder="Review title (optional)">
                                        </div>
                                        <div class="mb-2">
                                            <textarea name="comment" class="form-control form-control-sm" rows="2" placeholder="Tell us what you thought..."></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-vr">Submit Review</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            @if ($order->isReturnable() && ! $hasReturn)
                <div class="card border-0 shadow-sm mb-3" style="border-radius: 14px;">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-1"><i class="bi bi-arrow-counterclockwise me-1"></i>Raise a Return</h6>
                        <p class="small text-muted mb-3">
                            Select items to return. Request must be raised within {{ setting('return_window_days', 7) }} days of delivery.
                        </p>
                        <form method="POST" action="{{ route('account.order.return', $order) }}" novalidate>
                            @csrf
                            <div class="mb-3">
                                @foreach ($order->items as $item)
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="items[]" value="{{ $item->id }}" id="ritem-{{ $item->id }}">
                                        <label class="form-check-label small" for="ritem-{{ $item->id }}">
                                            {{ $item->product_name }} &times; {{ $item->quantity }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <select name="reason" class="form-select form-select-sm">
                                        <option value="">Select reason</option>
                                        <option>Damaged / broken item</option>
                                        <option>Wrong item delivered</option>
                                        <option>Item expired / nearing expiry</option>
                                        <option>Defective product</option>
                                        <option>Changed my mind</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="description" class="form-control form-control-sm" placeholder="Additional details (optional)">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-sm btn-vr mt-3">Submit Return Request</button>
                        </form>
                    </div>
                </div>
            @endif

            @if ($order->isCancellable())
                <div class="card border-0 shadow-sm mb-3" style="border-radius: 14px;">
                    <div class="card-body p-3 d-flex flex-wrap align-items-center gap-2">
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1">Cancel Order</h6>
                            <p class="small text-muted mb-0">Orders can be cancelled before shipping.</p>
                        </div>
                        <form method="POST" action="{{ route('account.order.cancel', $order) }}"
                              onsubmit="return confirm('Are you sure you want to cancel this order?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Cancel Order</button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm mb-3" style="border-radius: 14px;">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-1"></i>Order Progress</h6>
                    @include('storefront.partials.order-status-steps', ['order' => $order])

                    <hr class="my-3">
                    <h6 class="fw-bold mb-2 small text-muted text-uppercase">Updates</h6>
                    <ul class="list-unstyled small mb-0">
                        @forelse ($order->statusHistories as $history)
                            <li class="d-flex gap-2 mb-2">
                                <i class="bi bi-check-circle-fill" style="color:var(--vr-green);"></i>
                                <span>
                                    <span class="fw-semibold">{{ ucwords(str_replace('_', ' ', $history->status)) }}</span>
                                    <span class="text-muted">&middot; {{ $history->created_at->format('d M Y, h:i A') }}</span>
                                    @if ($history->description)
                                        <div class="text-muted mt-1">{{ $history->description }}</div>
                                    @endif
                                </span>
                            </li>
                        @empty
                            <li class="text-muted">No updates yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 14px;">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-2">Order Summary</h6>
                    <div class="small d-grid gap-1">
                        <div class="d-flex justify-content-between"><span class="text-muted">Subtotal <span class="text-muted" style="font-size:0.85em;">(incl. GST)</span></span><span>{{ format_price($order->subtotal) }}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Item Discounts</span><span class="text-success">- {{ format_price($order->discount_amount) }}</span></div>
                        @if ($order->coupon_discount > 0)
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">
                                    Coupon{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}
                                    @if ($order->coupon_type || $order->coupon_value !== null)
                                        <span class="d-block" style="font-size:0.85em;">{{ ucfirst((string) $order->coupon_type) }}{{ $order->coupon_value !== null ? ' '.format_price($order->coupon_value) : '' }}</span>
                                    @endif
                                </span>
                                <span class="text-success">- {{ format_price($order->coupon_discount) }}</span>
                            </div>
                        @endif
                        <div class="d-flex justify-content-between"><span class="text-muted">Taxable Value</span><span>{{ format_price($order->taxable_value) }}</span></div>
                        @if ($order->isIntraState())
                            <div class="d-flex justify-content-between"><span class="text-muted">CGST{{ $order->uniformGstRate() !== null ? ' ('.number_format($order->uniformGstRate() / 2, 1, '.', '').'%)' : '' }}</span><span>{{ format_price($order->cgst_amount) }}</span></div>
                            <div class="d-flex justify-content-between"><span class="text-muted">SGST{{ $order->uniformGstRate() !== null ? ' ('.number_format($order->uniformGstRate() / 2, 1, '.', '').'%)' : '' }}</span><span>{{ format_price($order->sgst_amount) }}</span></div>
                        @else
                            <div class="d-flex justify-content-between"><span class="text-muted">IGST{{ $order->uniformGstRate() !== null ? ' ('.(float) $order->uniformGstRate().'%)' : '' }}</span><span>{{ format_price($order->igst_amount) }}</span></div>
                        @endif
                        <div class="d-flex justify-content-between"><span class="text-muted">Shipping</span><span>{{ $order->shipping_charge > 0 ? format_price($order->shipping_charge) : 'FREE' }}</span></div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between fs-6 fw-bold"><span>Total</span><span>{{ format_price($order->grand_total) }}</span></div>
                        <div class="text-muted" style="font-size:0.8em;">Total payable is inclusive of GST.</div>
                        <div class="d-flex justify-content-between mt-1"><span class="text-muted">Paid</span><span>{{ format_price($order->amount_paid) }}</span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted">Due</span><span>{{ format_price($order->amount_due) }}</span></div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3" style="border-radius: 14px;">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-2"><i class="bi bi-geo-alt me-1"></i>Delivery Address</h6>
                    <p class="small mb-0">
                        {{ $order->shipping_name }}<br>
                        {{ $order->shipping_address_line1 }}{{ $order->shipping_address_line2 ? ', '.$order->shipping_address_line2 : '' }}<br>
                        {{ $order->shipping_city }}, {{ $order->shipping_state }} - {{ $order->shipping_pincode }}<br>
                        Mobile: {{ $order->shipping_mobile }}
                    </p>
                </div>
            </div>

            @if ($order->shipments->isNotEmpty())
                <div class="card border-0 shadow-sm mb-3" style="border-radius: 14px;">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-2"><i class="bi bi-truck me-1"></i>Shipment</h6>
                        @foreach ($order->shipments as $shipment)
                            <div class="small">
                                <div class="d-flex justify-content-between"><span class="text-muted">Courier</span><span>{{ $shipment->courier ?? '—' }}</span></div>
                                <div class="d-flex justify-content-between"><span class="text-muted">Tracking</span><span class="fw-semibold">{{ $shipment->tracking_number ?? '—' }}</span></div>
                                <div class="d-flex justify-content-between"><span class="text-muted">Status</span><span>{{ ucwords(str_replace('_', ' ', $shipment->status)) }}</span></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection