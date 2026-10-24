<?php

namespace Database\Seeders;

use App\Models\WhatsAppTemplate;
use Illuminate\Database\Seeder;

class WhatsAppTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'order_confirmation',
                'language' => 'en',
                'category' => 'UTILITY',
                'status' => 'APPROVED',
                'header_type' => 'TEXT',
                'header_text' => 'Order Confirmed! 🌿',
                'body_text' => "Hello {{1}}, thank you for ordering with VANRITI! Your order #{{2}} has been confirmed for ₹{{3}}. We are carefully handcrafting and packing your pure Ayurvedic items.",
                'footer_text' => 'Vanriti Ayurvedic Care',
                'buttons' => [
                    ['type' => 'URL', 'text' => 'View Order', 'url' => url('/orders')],
                ],
                'synced_at' => now(),
            ],
            [
                'name' => 'shipping_update',
                'language' => 'en',
                'category' => 'UTILITY',
                'status' => 'APPROVED',
                'header_type' => 'TEXT',
                'header_text' => 'Your Order is on the Way! 🚚',
                'body_text' => "Hello {{1}}, your Vanriti order #{{2}} has been dispatched with {{3}} (AWB: {{4}}). Estimated arrival: {{5}}.",
                'footer_text' => 'Vanriti Dispatch Desk',
                'buttons' => [
                    ['type' => 'URL', 'text' => 'Track Shipment', 'url' => url('/orders')],
                ],
                'synced_at' => now(),
            ],
            [
                'name' => 'abandoned_cart_reminder',
                'language' => 'en',
                'category' => 'MARKETING',
                'status' => 'APPROVED',
                'header_type' => 'IMAGE',
                'header_text' => null,
                'body_text' => "Hello {{1}}, your Ayurvedic beauty essentials are waiting in your cart! Complete your purchase today with code {{2}} to enjoy {{3}} off.",
                'footer_text' => 'Reply STOP to opt out',
                'buttons' => [
                    ['type' => 'URL', 'text' => 'Complete Order', 'url' => url('/checkout')],
                ],
                'synced_at' => now(),
            ],
            [
                'name' => 'promotional_offer',
                'language' => 'en',
                'category' => 'MARKETING',
                'status' => 'APPROVED',
                'header_type' => 'IMAGE',
                'header_text' => null,
                'body_text' => "Namaste {{1}}! 🌸 Celebrate pure holistic wellness with VANRITI. Enjoy {{2}} across our entire natural skincare range with coupon code {{3}} at checkout.",
                'footer_text' => 'Reply STOP to opt out',
                'buttons' => [
                    ['type' => 'URL', 'text' => 'Shop Collection', 'url' => url('/shop')],
                ],
                'synced_at' => now(),
            ],
        ];

        foreach ($templates as $t) {
            WhatsAppTemplate::updateOrCreate(
                ['name' => $t['name'], 'language' => $t['language']],
                $t
            );
        }
    }
}
