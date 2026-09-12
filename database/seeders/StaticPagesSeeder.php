<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StaticPagesSeeder extends Seeder
{
    public function run(): void
    {
        $storeName = setting('store_name', 'VANRITI');
        $storeAddress = setting('store_address', '');
        $storeEmail = setting('store_email', '');
        $storePhone = setting('store_phone', '');
        $returnDays = setting('return_window_days', '7');

        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'meta_title' => "About {$storeName} - Natural Beauty & Wellness",
                'meta_description' => "Discover the story behind {$storeName} — handcrafted skincare, herbal teas and wellness essentials, made with nature in mind.",
                'content' => <<<HTML
<div class="vr-section" style="background:var(--vr-cream);">
    <div class="container">
        <nav class="vr-breadcrumb mb-4" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">About Us</li>
            </ol>
        </nav>
        <div class="vr-kicker mb-3">PURE BY NATURE</div>
        <h1 class="vr-section-title mb-4">The {$storeName} Story</h1>
        <div class="d-grid gap-4 lead-parent">
            <p class="lead">At {$storeName}, we believe nature already has the answers. We bring together time-honoured botanicals and modern formulation science to craft skincare, personal care, herbal teas and wellness essentials that are pure, effective and kind to the planet.</p>
            <div class="row g-4 my-2">
                <div class="col-sm-6">
                    <div class="vr-cart-item h-100 p-4">
                        <div class="vr-kicker mb-2">Our Why</div>
                        <h5 class="fw-bold mb-2">Nature-first, always</h5>
                        <p class="small text-muted mb-0">Every product starts with a simple question — would nature approve? We shortlist ingredients by purity, provenance and performance before anything else.</p>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="vr-cart-item h-100 p-4">
                        <div class="vr-kicker mb-2">Our Promise</div>
                        <h5 class="fw-bold mb-2">Honest & transparent</h5>
                        <p class="small text-muted mb-0">Clear ingredient lists, truthful claims and responsibly sourced botanicals. What's on the label is exactly what's in the jar.</p>
                    </div>
                </div>
            </div>
            <h5 class="fw-bold mb-2">What we make</h5>
            <ul class="mb-0">
                <li><strong>Skincare &amp; personal care</strong> — gentle, actives-led products that respect your skin's natural barrier.</li>
                <li><strong>Herbal teas &amp; infusions</strong> — single-origin and blended botanicals for everyday wellness.</li>
                <li><strong>Wellness essentials</strong> — clean rituals that make self-care feel like coming home.</li>
            </ul>
            <hr>
            <div class="small text-muted mb-0">
                <span class="fw-semibold text-dark">{$storeName}</span><br>
                {$storeAddress}<br>
                {$storeEmail} &middot; {$storePhone}
            </div>
        </div>
    </div>
</div>
HTML,
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'meta_title' => "Privacy Policy - {$storeName}",
                'meta_description' => "Read how {$storeName} collects, uses and protects your personal information.",
                'content' => <<<HTML
<div class="vr-section" style="background:var(--vr-cream);">
    <div class="container">
        <nav class="vr-breadcrumb mb-4" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Privacy Policy</li>
            </ol>
        </nav>
        <div class="vr-kicker mb-3">PURE BY NATURE</div>
        <h1 class="vr-section-title mb-4">Privacy Policy</h1>
        <div class="d-grid gap-4 small">
            <p class="text-muted">Last updated: September 2026</p>
            <section>
                <h5 class="fw-bold mb-2">1. Who we are</h5>
                <p class="mb-0">{$storeName} ({$storeAddress}) respects your privacy. This policy explains what we collect, why we collect it, and how we keep it safe when you shop with us.</p>
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
                <p class="mb-0">You may request a copy, correction or deletion of your personal data, or withdraw consent for marketing at any time by writing to {$storeEmail}.</p>
            </section>
            <section>
                <h5 class="fw-bold mb-2">8. Contact</h5>
                <p class="mb-0">Questions about this policy? Write to {$storeEmail} or call {$storePhone}.</p>
            </section>
        </div>
    </div>
</div>
HTML,
            ],
            [
                'title' => 'Terms & Conditions',
                'slug' => 'terms-and-conditions',
                'meta_title' => "Terms & Conditions - {$storeName}",
                'meta_description' => "The terms and conditions that govern the use of {$storeName} and purchases made on our store.",
                'content' => <<<HTML
<div class="vr-section" style="background:var(--vr-cream);">
    <div class="container">
        <nav class="vr-breadcrumb mb-4" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Terms &amp; Conditions</li>
            </ol>
        </nav>
        <div class="vr-kicker mb-3">PURE BY NATURE</div>
        <h1 class="vr-section-title mb-4">Terms &amp; Conditions</h1>
        <div class="d-grid gap-4 small">
            <section>
                <h5 class="fw-bold mb-2">1. Acceptance of terms</h5>
                <p class="mb-0">By accessing or purchasing from {$storeName}, you agree to these terms. If you do not agree, please do not use our store.</p>
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
                <p class="mb-0">Returns are accepted within {$returnDays} days of delivery for items that are unused and in original packaging. Refunds for eligible returns are processed to the original payment method or as store credit. For full details, see our <a class="vr-link-underline" href="/shop/return-policy">Return Policy</a>.</p>
            </section>
            <section>
                <h5 class="fw-bold mb-2">6. Intellectual property</h5>
                <p class="mb-0">All content on this store — including text, design, logos, images and product formulations — is the property of {$storeName} and may not be reproduced without written permission.</p>
            </section>
            <section>
                <h5 class="fw-bold mb-2">7. Limitation of liability</h5>
                <p class="mb-0">Products are for personal use. {$storeName} is not liable for any indirect or consequential loss arising from the use of our products. Always read the product label and conduct a patch test before using any skincare product.</p>
            </section>
            <section>
                <h5 class="fw-bold mb-2">8. Governing law</h5>
                <p class="mb-0">These terms are governed by the laws of India. Any disputes are subject to the exclusive jurisdiction of the courts of Bengaluru, Karnataka.</p>
            </section>
        </div>
    </div>
</div>
HTML,
            ],
            [
                'title' => 'Shipping & Delivery',
                'slug' => 'shipping-and-delivery',
                'meta_title' => "Shipping & Delivery - {$storeName}",
                'meta_description' => "Shipping timelines, charges and delivery details for orders placed on {$storeName}.",
                'content' => <<<HTML
<div class="vr-section" style="background:var(--vr-cream);">
    <div class="container">
        <nav class="vr-breadcrumb mb-4" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Shipping &amp; Delivery</li>
            </ol>
        </nav>
        <div class="vr-kicker mb-3">PURE BY NATURE</div>
        <h1 class="vr-section-title mb-4">Shipping &amp; Delivery</h1>
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="vr-cart-item h-100 p-4 text-center">
                    <i class="bi bi-truck d-block mb-2" style="font-size:1.6rem;color:var(--vr-green);"></i>
                    <div class="fw-bold">₹499+</div>
                    <div class="small text-muted">Free shipping on orders above</div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="vr-cart-item h-100 p-4 text-center">
                    <i class="bi bi-clock d-block mb-2" style="font-size:1.6rem;color:var(--vr-green);"></i>
                    <div class="fw-bold">3-7 days</div>
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
                <p class="mb-0">Standard shipping is ₹49 per order. Orders of ₹499 or more qualify for <strong>free standard shipping</strong>. An express shipping option is available at checkout if you need your order sooner.</p>
            </section>
            <section>
                <h5 class="fw-bold mb-2">Dispatch &amp; delivery timelines</h5>
                <ul class="mb-0 ps-3">
                    <li>Orders are packed and dispatched within 24&ndash;48 hours (excluding weekends and public holidays).</li>
                    <li>Standard delivery typically takes 3-7 business days from dispatch.</li>
                    <li>Express delivery typically takes 2&ndash;4 business days from dispatch.</li>
                    <li>You will receive tracking details by SMS and email once your order is handed to our courier partner.</li>
                </ul>
            </section>
            <section>
                <h5 class="fw-bold mb-2">Tracking your order</h5>
                <p class="mb-0">Track your order anytime on the <a class="vr-link-underline" href="/track">Track Order</a> page using your order number and registered mobile number, or from your account under <em>My Orders</em>.</p>
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
                <p class="mb-0">Changed your mind? See our <a class="vr-link-underline" href="/shop/return-policy">Returns &amp; Refunds</a> policy for details of our {$returnDays}-day easy-return window.</p>
            </section>
            <section>
                <h5 class="fw-bold mb-2">Questions?</h5>
                <p class="mb-0">Reach us at {$storeEmail} or {$storePhone}, or through the <a class="vr-link-underline" href="/contact">Contact Us</a> page.</p>
            </section>
        </div>
    </div>
</div>
HTML,
            ],
        ];

        foreach ($pages as $data) {
            Page::updateOrCreate(
                ['slug' => $data['slug']],
                $data + ['status' => 'published']
            );
        }
    }
}
