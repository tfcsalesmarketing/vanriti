@extends('storefront.layouts.app')
@section('title', 'Payment Failed')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="vr-section py-4 py-lg-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="vr-status-card text-center">
                    <div class="vr-status-header">
                        <div class="vr-status-icon-wrap failed mb-3">
                            <i class="ri-close-circle-fill"></i>
                        </div>
                        <h2 class="fw-bold mb-1">Payment Failed</h2>
                        <p class="text-muted small mb-3">Unfortunately, your payment could not be processed.</p>

                        <div class="d-inline-block px-3 py-1 bg-light rounded-pill border mb-3">
                            <span class="small fw-semibold text-muted">Order Number:</span>
                            <span class="small fw-bold text-dark ms-1">#{{ $order->order_number }}</span>
                        </div>
                    </div>

                    <div class="p-4 bg-light border-top border-bottom text-start">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold text-dark small"><i class="ri-shopping-bag-line me-1 text-danger"></i> Order Details</span>
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
                            <span class="vr-pill-badge ps-failed">Failed</span>
                        </div>

                        @if ($order->payment?->failed_at)
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Attempted At</span>
                                <span>{{ $order->payment->failed_at->format('d M Y, h:i A') }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="p-4 d-flex flex-column align-items-center gap-3">
                        <div class="alert alert-light border text-start small w-100 mb-0" style="border-radius:12px;">
                            <i class="ri-information-line me-1 text-muted"></i>
                            No amount has been deducted. You can retry the payment or choose a different payment method.
                        </div>

                        <div class="d-flex gap-2 w-100 justify-content-center flex-wrap">
                            <a href="{{ route('checkout.index') }}" class="btn vr-app-btn px-4 py-2">
                                <i class="ri-refresh-line me-1"></i> Retry Payment
                            </a>
                            <a href="{{ route('cart.index') }}" class="btn vr-app-outline-btn px-4 py-2">
                                <i class="ri-shopping-bag-3-line me-1"></i> View Cart
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
