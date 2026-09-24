<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['key' => 'whatsapp_order_template_name', 'value' => 'order_confirmation', 'group' => 'whatsapp', 'label' => 'WhatsApp Order Confirmation Template Name', 'type' => 'text'],
            ['key' => 'whatsapp_delivery_eta_days', 'value' => '5', 'group' => 'whatsapp', 'label' => 'WhatsApp Order Delivery ETA (days)', 'type' => 'number'],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', ['whatsapp_order_template_name', 'whatsapp_delivery_eta_days'])
            ->delete();
    }
};
