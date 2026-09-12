@extends('storefront.layouts.app')
@section('title', 'Payment Pending')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-section py-4 py-lg-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="vr-status-card text-center">
                    <div class="vr-status-header">
                        <div class="vr-status-icon-wrap pending mb-3">
                            <i class="ri-time-fill"></i>
                        </div>
                        <h2 class="fw-bold mb-1">Payment Pending</h2>
                        <p class="text-muted small mb-3">Your payment is being processed. This may take a few minutes.</p>

                        <div class="d-inline-block px-3 py-1 bg-light rounded-pill border mb-3">
                            <span class="small fw-semibold text-muted">Order Number:</span>
                            <span class="small fw-bold text-dark ms-1">#{{ $order->order_number }}</span>
                        </div>
                    </div>

                    <div class="p-4 bg-light border-top border-bottom text-start">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold text-dark small"><i class="ri-shopping-bag-line me-1 text-warning"></i> Order Details</span>
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
                            <span>Payment Status</span>
                            <span class="vr-pill-badge ps-pending">Pending</span>
                        </div>

                        <div class="d-flex justify-content-between small text-muted">
                            <span>Order Status</span>
                            <span class="vr-badge-pill os-{{ $order->order_status }}">{{ ucfirst($order->order_status) }}</span>
                        </div>
                    </div>

                    <div class="p-4 d-flex flex-column align-items-center gap-3">
                        <div class="alert alert-light border text-start small w-100 mb-0" style="border-radius:12px;">
                            <i class="ri-information-line me-1 text-muted"></i>
                            If the payment is successful, your order will be confirmed automatically and you'll receive an email confirmation.
                        </div>

                        <div class="d-flex gap-2 w-100 justify-content-center flex-wrap">
                            <a href="{{ route('account.order', $order) }}" class="btn vr-app-btn px-4 py-2">
                                <i class="ri-file-list-3-line me-1"></i> View Order
                            </a>
                            <a href="{{ route('account.orders') }}" class="btn vr-app-outline-btn px-4 py-2">
                                <i class="ri-list-check me-1"></i> My Orders
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
