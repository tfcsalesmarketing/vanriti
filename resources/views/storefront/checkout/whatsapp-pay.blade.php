@extends('storefront.layouts.app')

@section('title', 'Complete Payment — VANRITI')

@section('content')
<div class="py-5 bg-light min-vh-100 d-flex align-items-center">
    <div class="container" style="max-width: 580px;">
        <div class="text-center mb-4">
            <a href="{{ route('home') }}" class="d-inline-block text-decoration-none">
                <span class="fs-2 fw-bold" style="font-family: 'Cinzel', serif; letter-spacing: 2px; color: var(--vr-green, #172B18);">VANRITI</span>
                <span class="d-block small text-muted text-uppercase" style="letter-spacing: 3px; font-size: 0.7rem;">Ayurveda & Nature</span>
            </a>
        </div>

        @if ($expired)
            <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 text-center">
                <div class="text-warning mb-3">
                    <i class="ri-time-line" style="font-size: 3.5rem;"></i>
                </div>
                <h4 class="fw-bold mb-2">Payment Link Expired</h4>
                <p class="text-muted small mb-4">This checkout session has expired for security reasons. Please start a new order on WhatsApp to continue shopping.</p>
                <a href="{{ route('shop.index') }}" class="btn vr-app-btn w-100 py-2.5">
                    <i class="ri-store-2-line me-1"></i> Browse Products
                </a>
            </div>
        @elseif ($alreadyPaid)
            <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 text-center">
                <div class="text-success mb-3">
                    <i class="ri-checkbox-circle-fill" style="font-size: 3.5rem;"></i>
                </div>
                <h4 class="fw-bold text-success mb-2">Order Confirmed & Paid!</h4>
                <p class="text-muted small mb-3">Order <strong>{{ $order->order_number }}</strong> has already been paid successfully.</p>
                <div class="p-3 bg-light rounded-3 mb-4 text-start small">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Total Paid:</span>
                        <strong class="text-dark">{{ format_price($order->grand_total) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Confirmation Sent to:</span>
                        <strong class="text-dark">{{ $session->phone }}</strong>
                    </div>
                </div>
                <a href="{{ route('home') }}" class="btn vr-app-outline-btn w-100 py-2.5">
                    Return to Store
                </a>
            </div>
        @else
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden" id="paymentCard">
                <div class="p-4 bg-white border-bottom">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">
                            <i class="ri-whatsapp-line me-1"></i> WhatsApp Order
                        </span>
                        <span class="text-muted small">Order #{{ $order->order_number }}</span>
                    </div>
                    <h5 class="fw-bold mb-1">Complete Your Payment</h5>
                    <p class="text-muted small mb-0">Customer: <strong>{{ $session->customer_name }}</strong> ({{ $session->phone }})</p>
                </div>

                <div class="p-4 bg-white">
                    <div class="mb-3">
                        <h6 class="fw-bold small text-uppercase text-muted mb-2" style="letter-spacing:0.5px;">Order Items</h6>
                        <div class="d-flex flex-column gap-2">
                            @foreach ($order->items as $item)
                                <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                    <div class="small">
                                        <div class="fw-semibold text-dark">{{ $item->product_name }}</div>
                                        <div class="text-muted" style="font-size:0.8rem;">Qty: {{ $item->quantity }} × {{ format_price($item->unit_price) }}</div>
                                    </div>
                                    <span class="small fw-bold text-dark">{{ format_price($item->total_price) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3 p-3 rounded-3" style="background:#f9fafb;">
                        <h6 class="fw-bold small text-uppercase text-muted mb-1" style="letter-spacing:0.5px;">Delivery Address</h6>
                        <div class="small text-dark">
                            {{ $order->shipping_address_line1 }}<br>
                            {{ $order->shipping_city }}, {{ $order->shipping_state }} - {{ $order->shipping_pincode }}
                        </div>
                    </div>

                    <div class="d-flex justify-content-between small text-muted mb-2">
                        <span>Subtotal</span>
                        <span class="text-dark fw-semibold">{{ format_price($order->subtotal) }}</span>
                    </div>

                    <div class="d-flex justify-content-between small text-muted mb-2">
                        <span>Delivery Charges</span>
                        <span>
                            @if ($order->shipping_charge <= 0)
                                <span class="text-success fw-semibold">FREE</span>
                            @else
                                <span class="text-dark fw-semibold">{{ format_price($order->shipping_charge) }}</span>
                            @endif
                        </span>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-bold fs-5">Total Payable</span>
                        <span class="fw-bold fs-4 text-success">{{ format_price($order->grand_total) }}</span>
                    </div>

                    <div id="paymentError" class="alert alert-danger small d-none mb-3"></div>

                    <button type="button" class="btn vr-app-btn w-100 py-3 d-flex align-items-center justify-content-center gap-2 fs-6 fw-semibold" id="rzpPayBtn">
                        <i class="ri-shield-check-line fs-5"></i>
                        <span>Pay {{ format_price($order->grand_total) }} via Razorpay</span>
                    </button>

                    <div class="mt-3 text-center text-muted small" style="font-size:0.78rem;">
                        <i class="ri-lock-line text-success"></i> 256-Bit Encrypted Secure Payment (UPI, Cards, NetBanking, Wallets)
                    </div>
                </div>
            </div>

            {{-- Success Card (Revealed on successful verification) --}}
            <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 text-center d-none" id="successCard">
                <div class="text-success mb-3">
                    <i class="ri-checkbox-circle-fill" style="font-size: 3.5rem;"></i>
                </div>
                <h4 class="fw-bold text-success mb-2">Payment Confirmed!</h4>
                <p class="text-muted small mb-3">Thank you, {{ $session->customer_name }}! Your payment has been received and order <strong>{{ $order->order_number }}</strong> is placed.</p>
                <div class="p-3 bg-light rounded-3 mb-4 text-start small">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Order ID:</span>
                        <strong>{{ $order->order_number }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Amount Paid:</span>
                        <strong class="text-success">{{ format_price($order->grand_total) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">WhatsApp Confirmation:</span>
                        <span class="badge bg-success">Sent to {{ $session->phone }}</span>
                    </div>
                </div>
                <a href="{{ route('home') }}" class="btn vr-app-outline-btn w-100 py-2.5">
                    Continue to VANRITI
                </a>
            </div>
        @endif
    </div>
</div>

@if (! $expired && ! $alreadyPaid)
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var payBtn = document.getElementById('rzpPayBtn');
            var errorBox = document.getElementById('paymentError');
            var paymentCard = document.getElementById('paymentCard');
            var successCard = document.getElementById('successCard');

            var options = {
                key: "{{ $keyId }}",
                amount: {{ $amountPaise }},
                currency: "INR",
                name: "VANRITI",
                description: "Order #{{ $order->order_number }}",
                image: "{{ asset('images/logo.png') }}",
                order_id: "{{ $razorpayOrderId }}",
                prefill: {
                    name: "{{ addslashes($session->customer_name ?? '') }}",
                    contact: "{{ $session->phone }}",
                    email: "{{ $order->user?->email ?? '' }}"
                },
                theme: {
                    color: "#172B18"
                },
                handler: function (response) {
                    payBtn.disabled = true;
                    payBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying payment...';

                    fetch("{{ route('checkout.whatsapp.pay.verify', ['token' => $session->session_token]) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_signature: response.razorpay_signature
                        })
                    })
                    .then(function (res) {
                        return res.json().then(function (data) {
                            return { ok: res.ok, data: data };
                        });
                    })
                    .then(function (res) {
                        if (res.ok && res.data.success) {
                            if (paymentCard) paymentCard.classList.add('d-none');
                            if (successCard) successCard.classList.remove('d-none');
                        } else {
                            payBtn.disabled = false;
                            payBtn.innerHTML = '<i class="ri-shield-check-line fs-5 me-1"></i> Try Payment Again';
                            if (errorBox) {
                                errorBox.textContent = res.data.message || 'Payment verification failed.';
                                errorBox.classList.remove('d-none');
                            }
                        }
                    })
                    .catch(function () {
                        payBtn.disabled = false;
                        payBtn.innerHTML = '<i class="ri-shield-check-line fs-5 me-1"></i> Try Payment Again';
                        if (errorBox) {
                            errorBox.textContent = 'Could not verify payment with server. Please contact support.';
                            errorBox.classList.remove('d-none');
                        }
                    });
                },
                modal: {
                    ondismiss: function () {
                        payBtn.disabled = false;
                        payBtn.innerHTML = '<i class="ri-shield-check-line fs-5 me-1"></i> Pay {{ format_price($order->grand_total) }} via Razorpay';
                    }
                }
            };

            var rzp = new Razorpay(options);

            if (payBtn) {
                payBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    errorBox.classList.add('d-none');
                    rzp.open();
                });
            }
        });
    </script>
@endif
@endsection
