<?php

namespace Database\Seeders;

use App\Models\WhatsAppBotRule;
use App\Models\WhatsAppQuickReply;
use Illuminate\Database\Seeder;

class WhatsAppBotSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Bot Rules
        $rules = [
            [
                'name' => 'Order Tracking Assistant',
                'keywords' => 'track, tracking, order status, where is my order, order update, track order',
                'match_type' => 'contains',
                'action_type' => 'dynamic_order_tracking',
                'response_text' => null,
                'buttons' => null,
                'is_active' => true,
                'priority' => 100,
                'cooldown_minutes' => 5,
            ],
            [
                'name' => 'Greeting & Help Menu',
                'keywords' => 'hi, hello, hey, start, menu, namaste, help',
                'match_type' => 'exact',
                'action_type' => 'interactive_menu',
                'response_text' => "Namaste {name}! 🌿 Welcome to VANRITI.\nHow can our wellness experts assist you today?",
                'buttons' => [
                    ['id' => 'btn_track', 'title' => 'Track My Order'],
                    ['id' => 'btn_catalog', 'title' => 'Browse Products'],
                    ['id' => 'btn_offers', 'title' => 'View Offers'],
                ],
                'is_active' => true,
                'priority' => 90,
                'cooldown_minutes' => 30,
            ],
            [
                'name' => 'Discount & Coupon Finder',
                'keywords' => 'offer, offers, coupon, promo, discount, deal, deals, code',
                'match_type' => 'contains',
                'action_type' => 'coupon',
                'response_text' => null,
                'buttons' => null,
                'is_active' => true,
                'priority' => 80,
                'cooldown_minutes' => 30,
            ],
            [
                'name' => 'Product Catalog Showcase',
                'keywords' => 'catalog, catalogue, products, items, collection, shop, buy',
                'match_type' => 'contains',
                'action_type' => 'catalog',
                'response_text' => null,
                'buttons' => null,
                'is_active' => true,
                'priority' => 70,
                'cooldown_minutes' => 15,
            ],
            [
                'name' => 'Customer Care & Human Handover',
                'keywords' => 'agent, human, support, care, executive, speak, talk',
                'match_type' => 'contains',
                'action_type' => 'text',
                'response_text' => "Hello {name}! 🌿 A member of our Vanriti Care Team has been alerted and will join this chat with you shortly. Please feel free to leave any details or questions below.",
                'buttons' => null,
                'is_active' => true,
                'priority' => 60,
                'cooldown_minutes' => 30,
            ],
        ];

        foreach ($rules as $r) {
            WhatsAppBotRule::updateOrCreate(['name' => $r['name']], $r);
        }

        // 2. Quick Replies
        $quickReplies = [
            [
                'shortcut' => 'greet',
                'title' => 'Warm Greeting & Welcome',
                'category' => 'Greetings',
                'content' => "Hello {name}! 🌿 Thank you for reaching out to VANRITI. How may I assist you with your skincare and Ayurvedic wellness routine today?",
            ],
            [
                'shortcut' => 'track',
                'title' => 'Order Status & Tracking',
                'category' => 'Orders',
                'content' => "Your order has been dispatched via our express courier partner. You can track live transit milestones anytime at:\n{site_url}/orders",
            ],
            [
                'shortcut' => 'payment',
                'title' => 'Safe Online Payment Link',
                'category' => 'Billing',
                'content' => "You can complete your checkout safely using UPI, Cards, Net Banking or Cash on Delivery using this secure direct link:\n{site_url}/checkout",
            ],
            [
                'shortcut' => 'ayurveda',
                'title' => 'Ayurvedic Ritual & Usage Guide',
                'category' => 'Consultation',
                'content' => "For best results, cleanse with lukewarm water, pat dry, and gently massage 3-4 drops into your skin using upwards circular motions before bedtime. Let the potent botanicals absorb overnight. 🌿",
            ],
            [
                'shortcut' => 'return',
                'title' => 'Hassle-Free Replacement Policy',
                'category' => 'Support',
                'content' => "We want you to love your Vanriti ritual! If you received a damaged item or have any quality concerns within 7 days of delivery, our team will arrange a hassle-free replacement immediately.",
            ],
        ];

        foreach ($quickReplies as $qr) {
            WhatsAppQuickReply::updateOrCreate(['shortcut' => $qr['shortcut']], $qr);
        }
    }
}
