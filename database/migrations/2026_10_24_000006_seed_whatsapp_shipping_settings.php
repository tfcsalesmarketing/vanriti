<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['key' => 'whatsapp_shipping_notifications_enabled', 'value' => '1', 'group' => 'whatsapp', 'label' => 'Enable WhatsApp Shipping & Delivery Notifications', 'type' => 'boolean'],
            ['key' => 'whatsapp_dispatch_template_name', 'value' => 'order_dispatched', 'group' => 'whatsapp', 'label' => 'WhatsApp Order Dispatched Template Name', 'type' => 'text'],
            ['key' => 'whatsapp_out_for_delivery_template_name', 'value' => 'order_out_for_delivery', 'group' => 'whatsapp', 'label' => 'WhatsApp Out For Delivery Template Name', 'type' => 'text'],
            ['key' => 'whatsapp_delivered_template_name', 'value' => 'order_delivered', 'group' => 'whatsapp', 'label' => 'WhatsApp Order Delivered Template Name', 'type' => 'text'],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', [
                'whatsapp_shipping_notifications_enabled',
                'whatsapp_dispatch_template_name',
                'whatsapp_out_for_delivery_template_name',
                'whatsapp_delivered_template_name',
            ])
            ->delete();
    }
};
