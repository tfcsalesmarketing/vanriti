<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            ['key' => 'whatsapp_orders_enabled', 'value' => '1', 'group' => 'whatsapp', 'label' => 'Enable WhatsApp Order Confirmations', 'type' => 'boolean'],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', ['whatsapp_orders_enabled'])
            ->delete();
    }
};
