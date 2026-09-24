@extends('storefront.layouts.app')

@section('title', 'Shipping & Delivery - ' . store_name())
@section('meta_description', 'Shipping timelines, charges and delivery details for orders placed on ' . store_name() . '.')

@section('content')
@php
    $threshold = (float) setting('free_shipping_threshold', 499);
    $charge = (float) setting('shipping_charge', 49);
    $days = setting('estimated_days', '3-7');
@endphp

<div class="vr-section">
    <div class="container">
        <nav class="vr-breadcrumb mb-4" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Shipping &amp; Delivery</li>
            </ol>
        </nav>

        <div class="vr-kicker mb-3">PURE BY NATURE</div>
        <h1 class="vr-section-title mb-4">Shipping &amp; Delivery</h1>

        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="vr-cart-item h-100 p-4 text-center">
                    <i class="bi bi-truck d-block mb-2" style="font-size:1.6rem;color:var(--vr-green);"></i>
                    <div class="fw-bold">{{ format_price($threshold) }}+</div>
                    <div class="small text-muted">Free shipping on orders above</div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="vr-cart-item h-100 p-4 text-center">
                    <i class="bi bi-clock d-block mb-2" style="font-size:1.6rem;color:var(--vr-green);"></i>
                    <div class="fw-bold">{{ $days }}</div>
                    <div class="small text-muted">Standard delivery (business days)</div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="vr-cart-item h-100 p-4 text-center">
                    <i class="bi bi-cash-coin d-block mb-2" style="font-size:1.6rem;color:var(--vr-green);"></i>
                    <div class="fw-bold">COD</div>
                    <div class="small text-muted">Pay at your doorstep</div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-4 small">
            <section>
                <h5 class="fw-bold mb-2">Delivery areas</h5>
                <p class="mb-0">We currently deliver across India. During checkout, enter your pincode to confirm availability for your area. Delivery to remote or pincode-restricted areas may take longer.</p>
            </section>

            <section>
                <h5 class="fw-bold mb-2">Shipping charges</h5>
                <p class="mb-0">
                    Standard shipping is {{ $charge > 0 ? format_price($charge) : 'FREE' }} per order.
                    Orders of {{ format_price($threshold) }} or more qualify for <strong>free standard shipping</strong>.
                    An express shipping option is available at checkout if you need your order sooner.
                </p>
            </section>

            <section>
                <h5 class="fw-bold mb-2">Dispatch &amp; delivery timelines</h5>
                <ul class="mb-0 ps-3">
                    <li>Orders are packed and dispatched within 24&ndash;48 hours (excluding weekends and public holidays).</li>
                    <li>Standard delivery typically takes {{ $days }} business days from dispatch.</li>
                    <li>Express delivery typically takes 2&ndash;4 business days from dispatch.</li>
                    <li>You will receive tracking details by SMS and email once your order is handed to our courier partner.</li>
                </ul>
            </section>

            <section>
                <h5 class="fw-bold mb-2">Tracking your order</h5>
                <p class="mb-0">Track your order anytime on the <a class="vr-link-underline" href="{{ route('track') }}">Track Order</a> page using your order number and registered mobile number, or from your account under <em>My Orders</em>.</p>
            </section>

            <section>
                <h5 class="fw-bold mb-2">Delivery instructions</h5>
                <ul class="mb-0 ps-3">
                    <li>Please keep your registered mobile number handy during the delivery window.</li>
                    <li>Add a nearby location landmark at checkout to help our courier partners.</li>
                    <li>Inspect your package at the time of delivery. For any damage in transit, refuse delivery or contact us within 48 hours.</li>
                </ul>
            </section>

            <section>
                <h5 class="fw-bold mb-2">Returns &amp; refunds</h5>
                <p class="mb-0">Changed your mind? See our <a class="vr-link-underline" href="{{ route('shop.return-policy') }}">Returns &amp; Refunds</a> policy for details of our {{ setting('return_window_days', 7) }}-day easy-return window.</p>
            </section>

            <section>
                <h5 class="fw-bold mb-2">Questions?</h5>
                <p class="mb-0">Reach us at {{ setting('store_email', '') }} or {{ setting('store_phone', '') }}, or through the <a class="vr-link-underline" href="{{ route('contact.index') }}">Contact Us</a> page.</p>
            </section>
        </div>
    </div>
</div>
@endsection