<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['general', 'store_name', 'VANRITI', 'Store Name', 'text'],
            ['general', 'store_tagline', 'Natural Beauty & Wellness', 'Tagline', 'text'],

            ['general', 'support_email', 'support@vanriti.com', 'Customer Support Email', 'email'],
            ['general', 'store_email', 'hello@vanriti.com', 'Store Email', 'email'],
            ['general', 'store_phone', '+91 98765 43210', 'Phone', 'text'],
            ['general', 'whatsapp_number', '+91 98765 43210', 'WhatsApp Number', 'text'],
            ['general', 'store_address', 'VANRITI House, MG Road, Bengaluru, Karnataka 560001', 'Business Address', 'textarea'],
            ['general', 'gst_number', 'GSTINXXXXXXX', 'GST Number', 'text'],

            ['general', 'facebook_url', 'https://facebook.com/vanriti', 'Facebook', 'url'],
            ['general', 'instagram_url', 'https://instagram.com/vanriti', 'Instagram', 'url'],
            ['general', 'twitter_url', 'https://twitter.com/vanriti', 'Twitter / X', 'url'],
            ['general', 'youtube_url', 'https://youtube.com/@vanriti', 'YouTube', 'url'],
            ['general', 'linkedin_url', 'https://linkedin.com/company/vanriti', 'LinkedIn', 'url'],

            ['shipping', 'shipping_charge', '49', 'Standard Shipping Charge', 'number'],
            ['shipping', 'free_shipping_threshold', '499', 'Free Shipping Above', 'number'],
            ['shipping', 'cod_available', '1', 'Allow Cash on Delivery', 'boolean'],
            ['shipping', 'estimated_days', '3-7', 'Estimated Delivery (days)', 'text'],
            ['shipping', 'allow_pincode_check', '1', 'Enable Pincode Check', 'boolean'],

            ['tax', 'tax_type', 'inclusive', 'GST Display (inclusive/exclusive)', 'select'],
            ['tax', 'default_gst_rate', '18', 'Default GST Rate %', 'number'],
            ['tax', 'business_state', 'Haryana', 'Business State (GST)', 'text'],

            ['orders', 'min_order_amount', '1', 'Minimum Order Amount', 'number'],
            ['orders', 'auto_confirm_orders', '1', 'Auto-confirm COD orders', 'boolean'],

            ['returns', 'return_window_days', '7', 'Return Window (days)', 'number'],
            ['returns', 'return_policy', 'Items can be returned within 7 days of delivery, provided they are unused and in original packaging.', 'Return Policy', 'textarea'],

            ['payment', 'cod_enabled', '1', 'Cash on Delivery Enabled', 'boolean'],
            ['payment', 'online_payment_enabled', '1', 'Online Payment Enabled', 'boolean'],
            ['payment', 'razorpay_enabled', '0', 'Razorpay Enabled', 'boolean'],
            ['payment', 'razorpay_key_id', '', 'Razorpay Key ID', 'text'],
            ['payment', 'razorpay_key_secret', '', 'Razorpay Key Secret', 'password'],

            ['seo', 'meta_title', 'VANRITI - Natural Beauty & Wellness Products', 'Default Meta Title', 'text'],
            ['seo', 'meta_description', 'Discover premium natural beauty, personal care, herbal and wellness products crafted with care.', 'Default Meta Description', 'text'],
            ['seo', 'meta_keywords', 'vanriti, natural beauty, ayurvedic, herbal wellness, skincare, haircare', 'Default Meta Keywords', 'text'],
            ['seo', 'meta_pixel_id', '', 'Meta Pixel ID', 'text'],
            ['seo', 'meta_capi_access_token', '', 'Meta CAPI Access Token', 'password'],
            ['seo', 'meta_test_event_code', '', 'Meta CAPI Test Event Code (empty in production)', 'text'],

            ['shipmojo', 'shipmojo_enabled', '0', 'Enable ShipMojo', 'boolean'],
            ['shipmojo', 'shipmojo_public_key', '', 'ShipMojo Public Key', 'text'],
            ['shipmojo', 'shipmojo_private_key', '', 'ShipMojo Private Key', 'password'],
            ['shipmojo', 'shipmojo_warehouse_id', '', 'Default Warehouse ID', 'text'],
            ['shipmojo', 'shipmojo_warehouse_pincode', '', 'Warehouse Pincode (for rates)', 'text'],
            ['shipmojo', 'shipmojo_auto_push', '0', 'Auto-Push Orders to ShipMojo', 'boolean'],
            ['shipmojo', 'shipmojo_auto_assign', '1', 'Auto-Assign Courier After Push', 'boolean'],
            ['shipmojo', 'shipmojo_default_weight_grams', '500', 'Default Weight (grams)', 'number'],
            ['shipmojo', 'shipmojo_default_length', '20', 'Default Box Length (cm)', 'number'],
            ['shipmojo', 'shipmojo_default_width', '15', 'Default Box Width (cm)', 'number'],
            ['shipmojo', 'shipmojo_default_height', '10', 'Default Box Height (cm)', 'number'],
        ];

        foreach ($settings as [$group, $key, $value, $label, $type]) {
            Setting::updateOrCreate(['key' => $key], [
                'group' => $group,
                'value' => $value,
                'label' => $label,
                'type' => $type,
            ]);
        }
    }
}