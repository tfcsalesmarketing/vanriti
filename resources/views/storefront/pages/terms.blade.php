@extends('storefront.layouts.app')

@section('title', 'Terms & Conditions - ' . store_name())
@section('meta_description', 'The terms and conditions that govern the use of ' . store_name() . ' and purchases made on our store.')

@section('content')
<div class="vr-section">
    <div class="container">
        <nav class="vr-breadcrumb mb-4" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Terms &amp; Conditions</li>
            </ol>
        </nav>

        <div class="vr-kicker mb-3">PURE BY NATURE</div>
        <h1 class="vr-section-title mb-4">Terms &amp; Conditions</h1>

        <div class="d-grid gap-4 small">
            <section>
                <h5 class="fw-bold mb-2">1. Acceptance of terms</h5>
                <p class="mb-0">By accessing or purchasing from {{ store_name() }}, you agree to these terms. If you do not agree, please do not use our store.</p>
            </section>

            <section>
                <h5 class="fw-bold mb-2">2. Products &amp; availability</h5>
                <p class="mb-0">All products are subject to availability and may be withdrawn or restructured at any time. We make every effort to display accurate product information, prices and stock levels but cannot guarantee the absence of errors.</p>
            </section>

            <section>
                <h5 class="fw-bold mb-2">3. Pricing &amp; payment</h5>
                <p class="mb-0">All prices are in Indian Rupees (INR) and include applicable taxes as displayed at checkout. We accept Cash on Delivery and online payments via our secure payment gateway. An order is confirmed only once payment is verified (for online payments) or the order is placed for Cash on Delivery.</p>
            </section>

            <section>
                <h5 class="fw-bold mb-2">4. Orders &amp; cancellations</h5>
                <p class="mb-0">We reserve the right to cancel any order for reasons including suspected fraud, pricing errors or stock unavailability. Orders may be cancelled by you before dispatch through your account or our support team.</p>
            </section>

            <section>
                <h5 class="fw-bold mb-2">5. Returns &amp; refunds</h5>
                <p class="mb-0">Returns are accepted within {{ setting('return_window_days', 7) }} days of delivery for items that are unused and in original packaging. Refunds for eligible returns are processed to the original payment method or as store credit. For full details, see our <a class="vr-link-underline" href="{{ route('shop.return-policy') }}">Return Policy</a>.</p>
            </section>

            <section>
                <h5 class="fw-bold mb-2">6. Intellectual property</h5>
                <p class="mb-0">All content on this store — including text, design, logos, images and product formulations — is the property of {{ store_name() }} and may not be reproduced without written permission.</p>
            </section>

            <section>
                <h5 class="fw-bold mb-2">7. Limitation of liability</h5>
                <p class="mb-0">Products are for personal use. {{ store_name() }} is not liable for any indirect or consequential loss arising from the use of our products. Always read the product label and conduct a patch test before using any skincare product.</p>
            </section>

            <section>
                <h5 class="fw-bold mb-2">8. Governing law</h5>
                <p class="mb-0">These terms are governed by the laws of India. Any disputes are subject to the exclusive jurisdiction of the courts of Bengaluru, Karnataka.</p>
            </section>
        </div>
    </div>
</div>
@endsection