<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class CancellationPolicySeeder extends Seeder
{
    public function run(): void
    {
        $storeName = setting('store_name', 'VANRITI');
        $storeEmail = setting('store_email', '');
        $storePhone = setting('store_phone', '');

        $content = <<<HTML
<div class="vr-section" style="background:var(--vr-cream);">
    <div class="container">
        <nav class="vr-breadcrumb mb-4" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cancellation Policy</li>
            </ol>
        </nav>
        <div class="vr-kicker mb-3">PURE BY NATURE</div>
        <h1 class="vr-section-title mb-4">Cancellation Policy</h1>
        <div class="d-grid gap-4 small">
            <section>
                <h5 class="fw-bold mb-2">1. When can you cancel</h5>
                <ul class="mb-0 ps-3">
                    <li>Orders can be cancelled anytime before they are packed and dispatched.</li>
                    <li>Once an order is dispatched, it can no longer be cancelled. However, you may raise a return after delivery.</li>
                    <li>COD orders can be refused at the time of delivery without any penalty.</li>
                </ul>
            </section>
            <section>
                <h5 class="fw-bold mb-2">2. How to cancel an order</h5>
                <ul class="mb-0 ps-3">
                    <li>Log in to your account and go to <em>My Orders</em>.</li>
                    <li>Select the order and click <strong>Cancel Order</strong>.</li>
                    <li>Choose a reason for cancellation, if prompted, and confirm.</li>
                    <li>You will receive a confirmation by email and SMS.</li>
                </ul>
            </section>
            <section>
                <h5 class="fw-bold mb-2">3. Refunds for cancelled orders</h5>
                <ul class="mb-0 ps-3">
                    <li>Online payments are refunded to the original payment method within 3&ndash;5 business days.</li>
                    <li>COD orders do not require a refund as no payment was collected online.</li>
                    <li>If a promotional discount was used, the coupon may be restored at our discretion.</li>
                </ul>
            </section>
            <section>
                <h5 class="fw-bold mb-2">4. Non-cancellable orders</h5>
                <p class="mb-0">Orders that are already dispatched or in transit cannot be cancelled. Please wait for delivery and raise a return if needed.</p>
            </section>
            <section>
                <h5 class="fw-bold mb-2">5. Contact</h5>
                <p class="mb-0">For any cancellation questions, reach us at {$storeEmail} or call {$storePhone}.</p>
            </section>
        </div>
    </div>
</div>
HTML;

        Page::updateOrCreate(
            ['slug' => 'cancellation-policy'],
            [
                'title' => 'Cancellation Policy',
                'status' => 'published',
                'meta_title' => "Cancellation Policy - {$storeName}",
                'meta_description' => "Learn how and when you can cancel an order placed on {$storeName}.",
                'content' => $content,
            ]
        );
    }
}