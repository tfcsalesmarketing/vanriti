@extends('storefront.layouts.app')

@section('title', 'Privacy Policy - ' . store_name())
@section('meta_description', 'Read how ' . store_name() . ' collects, uses and protects your personal information.')

@section('content')
<div class="vr-section">
    <div class="container">
        <nav class="vr-breadcrumb mb-4" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Privacy Policy</li>
            </ol>
        </nav>

        <div class="vr-kicker mb-3">PURE BY NATURE</div>
        <h1 class="vr-section-title mb-4">Privacy Policy</h1>

        <div class="d-grid gap-4 small">
            <p class="text-muted">Last updated: {{ now()->format('F Y') }}</p>

            <section>
                <h5 class="fw-bold mb-2">1. Who we are</h5>
                <p class="mb-0">{{ store_name() }} ({{ setting('store_address', '') }}) respects your privacy. This policy explains what we collect, why we collect it, and how we keep it safe when you shop with us at {{ rtrim(config('app.url'), '/') }}.</p>
            </section>

            <section>
                <h5 class="fw-bold mb-2">2. Information we collect</h5>
                <ul class="mb-0 ps-3">
                    <li><strong>Account details</strong> — name, email, mobile number and password (stored encrypted).</li>
                    <li><strong>Order details</strong> — shipping &amp; billing address, order history, payment method information.</li>
                    <li><strong>Preferences</strong> — wishlist, review submissions, newsletter subscriptions and marketing preferences.</li>
                    <li><strong>Technical data</strong> — IP address, browser type and basic analytics used to improve the site.</li>
                </ul>
            </section>

            <section>
                <h5 class="fw-bold mb-2">3. How we use your information</h5>
                <ul class="mb-0 ps-3">
                    <li>To process and deliver your orders, including payment verification.</li>
                    <li>To manage your account, returns, refunds and support requests.</li>
                    <li>To keep in touch about your order status and, only with your consent, offers and updates.</li>
                    <li>To prevent fraud and maintain the security of our services.</li>
                </ul>
            </section>

            <section>
                <h5 class="fw-bold mb-2">4. Payments</h5>
                <p class="mb-0">We accept Cash on Delivery and secure online payments. Online payment processing is handled by our payment partner (Razorpay). We do not store your card or bank details on our servers.</p>
            </section>

            <section>
                <h5 class="fw-bold mb-2">5. Sharing of information</h5>
                <p class="mb-0">We only share your data with trusted partners needed to fulfil your order — courier partners, payment gateways and our technology providers — and only to the extent required. We never sell your personal information.</p>
            </section>

            <section>
                <h5 class="fw-bold mb-2">6. Data retention &amp; security</h5>
                <p class="mb-0">We retain your data only as long as needed for the purposes above and legal obligations. Reasonable technical and organisational measures are in place to protect your information.</p>
            </section>

            <section>
                <h5 class="fw-bold mb-2">7. Your rights</h5>
                <p class="mb-0">You may request a copy, correction or deletion of your personal data, or withdraw consent for marketing at any time by writing to {{ setting('store_email', '') }}.</p>
            </section>

            <section>
                <h5 class="fw-bold mb-2">8. Contact</h5>
                <p class="mb-0">Questions about this policy? Write to {{ setting('store_email', '') }} or call {{ setting('store_phone', '') }}.</p>
            </section>
        </div>
    </div>
</div>
@endsection