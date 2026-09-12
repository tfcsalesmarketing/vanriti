@extends('storefront.layouts.app')
@section('title', 'Order Confirmed')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-section py-4 py-lg-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="vr-status-card text-center">
                    <div class="vr-status-header">
                        <div class="vr-status-icon-wrap success">
                            <i class="ri-checkbox-circle-fill"></i>
                        </div>
                        <h2 class="fw-bold mb-1">Order Confirmed!</h2>
                        <p class="text-muted small mb-3">Thank you for your purchase. We're processing your order.</p>
                        
                        <div class="d-inline-block px-3 py-1 bg-light rounded-pill border mb-3">
                            <span class="small fw-semibold text-muted">Order Number:</span>
                            <span class="small fw-bold text-dark ms-1">#{{ $order->order_number }}</span>
                        </div>
                    </div>

                    <div class="p-4 bg-light border-top border-bottom text-start">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold text-dark small"><i class="ri-shopping-bag-line me-1 text-success"></i> Order Details</span>
                            <span class="vr-type-chip">{{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}</span>
                        </div>

                        <div class="d-flex justify-content-between small mb-2 text-muted">
                            <span>Order Total</span>
                            <span class="fw-bold text-dark fs-6">{{ format_price($order->grand_total) }}</span>
                        </div>

                        <div class="d-flex justify-content-between small mb-2 text-muted">
                            <span>Payment Method</span>
                            <span class="fw-semibold text-dark text-capitalize">{{ str_replace('_', ' ', $order->payment_method) }}</span>
                        </div>

                        <div class="d-flex justify-content-between small mb-2 text-muted">
                            <span>Order Status</span>
                            <span class="vr-badge-pill os-{{ $order->order_status }}">{{ ucfirst($order->order_status) }}</span>
                        </div>

                        @if ($order->shipping_address)
                            <div class="pt-3 border-top mt-3 small">
                                <span class="fw-semibold text-dark d-block mb-1"><i class="ri-map-pin-line me-1 text-success"></i> Delivery Address</span>
                                <div class="text-muted">
                                    {{ $order->shipping_address['full_name'] ?? '' }} &middot; {{ $order->shipping_address['mobile'] ?? '' }}<br>
                                    {{ $order->shipping_address['address_line1'] ?? '' }}, {{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['state'] ?? '' }} - {{ $order->shipping_address['pincode'] ?? '' }}
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="p-4 d-flex flex-column align-items-center gap-3">
                        <div class="d-flex gap-2 w-100 justify-content-center flex-wrap">
                            <a href="{{ route('account.order', $order) }}" class="btn vr-app-btn px-4 py-2">
                                <i class="ri-file-list-3-line me-1"></i> View Order Details
                            </a>
                            <a href="{{ route('track') }}" class="btn vr-app-outline-btn px-4 py-2">
                                <i class="ri-map-pin-user-line me-1"></i> Track Delivery
                            </a>
                        </div>

                        <a href="{{ route('shop.index') }}" class="small text-muted vr-link-underline">
                            <i class="ri-arrow-left-line me-1"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@if ($metaEventId)
    @php
        $_purchasePayload = [
            'value' => (float) $order->grand_total,
            'currency' => 'INR',
            'content_type' => 'product',
            'contents' => $order->items->map(fn ($item) => [
                'id' => $item->sku,
                'quantity' => $item->quantity,
                'price' => (float) $item->unit_price,
            ])->values()->all(),
            'transaction_id' => $order->order_number,
        ];
    @endphp
    @push('scripts')
    <script>
    fbq('track', 'Purchase', {!! json_encode($_purchasePayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}, {eventID: {!! json_encode($metaEventId) !!}});
    </script>
    @endpush
@endif

@if ($shippingInfoPayload && $paymentInfoPayload)
    @push('scripts')
    <script>
    window.dataLayer.push({!! json_encode($shippingInfoPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!});
    window.dataLayer.push({!! json_encode($paymentInfoPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!});
    </script>
    @endpush
@endif

@if ($purchasePayload)
    @push('scripts')
    <script>
    window.dataLayer.push({!! json_encode($purchasePayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!});
    </script>
    @endpush
@endif
