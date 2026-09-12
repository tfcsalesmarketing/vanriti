<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class ReturnPolicySeeder extends Seeder
{
    public function run(): void
    {
        $storeName = setting('store_name', 'VANRITI');
        $storeEmail = setting('store_email', '');
        $storePhone = setting('store_phone', '');
        $returnDays = setting('return_window_days', '7');

        $content = <<<HTML
<div class="vr-section" style="background:var(--vr-cream);">
    <div class="container">
        <nav class="vr-breadcrumb mb-4" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Return &amp; Refund Policy</li>
            </ol>
        </nav>
        <div class="vr-kicker mb-3">PURE BY NATURE</div>
        <h1 class="vr-section-title mb-4">Return &amp; Refund Policy</h1>
        <div class="d-grid gap-4 small">
            <section>
                <h5 class="fw-bold mb-2">1. Eligibility</h5>
                <p class="mb-0">Returns are accepted within {$returnDays} days of delivery. Items must be unused, unopened and in their original packaging.</p>
            </section>
            <section>
                <h5 class="fw-bold mb-2">2. How to request a return</h5>
                <ul class="mb-0 ps-3">
                    <li>Log in to your account and go to <em>My Orders</em>.</li>
                    <li>Select the order and click <strong>Request Return</strong>.</li>
                    <li>Choose the items and reason for return.</li>
                    <li>Our team will review and approve within 24&ndash;48 hours.</li>
                </ul>
            </section>
            <section>
                <h5 class="fw-bold mb-2">3. Return shipping</h5>
                <p class="mb-0">Once approved, we will arrange a pickup from your address at no extra cost. Please pack the items securely in their original packaging.</p>
            </section>
            <section>
                <h5 class="fw-bold mb-2">4. Refunds</h5>
                <ul class="mb-0 ps-3">
                    <li>Refunds are processed within 5&ndash;7 business days after we receive and inspect the returned items.</li>
                    <li>Online payments are refunded to the original payment method.</li>
                    <li>COD orders are refunded via bank transfer or store credit.</li>
                </ul>
            </section>
            <section>
                <h5 class="fw-bold mb-2">5. Non-returnable items</h5>
                <p class="mb-0">Products that are opened, used, or damaged due to customer handling are not eligible for return. Gift cards and promotional items are also non-returnable.</p>
            </section>
            <section>
                <h5 class="fw-bold mb-2">6. Damaged or wrong items</h5>
                <p class="mb-0">If you receive a damaged or incorrect item, contact us within 48 hours of delivery with photos. We will arrange an immediate replacement or full refund.</p>
            </section>
            <section>
                <h5 class="fw-bold mb-2">7. Contact</h5>
                <p class="mb-0">For any return or refund questions, reach us at {$storeEmail} or call {$storePhone}.</p>
            </section>
        </div>
    </div>
</div>
HTML;

        Page::updateOrCreate(
            ['slug' => 'return-policy'],
            [
                'title' => 'Return & Refund Policy',
                'status' => 'published',
                'meta_title' => "Return & Refund Policy - {$storeName}",
                'meta_description' => "Learn about our return and refund policy for orders placed on {$storeName}.",
                'content' => $content,
            ]
        );
    }
}
