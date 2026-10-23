<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('whatsapp_recovery_sent_at')->nullable()->after('whatsapp_confirmation_sent_at');
        });

        DB::table('settings')->insertOrIgnore([
            [
                'key' => 'whatsapp_recovery_enabled',
                'value' => '1',
                'group' => 'whatsapp',
                'label' => 'Enable WhatsApp Checkout Recovery',
                'type' => 'boolean',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'whatsapp_recovery_template_name',
                'value' => 'checkout_recovery',
                'group' => 'whatsapp',
                'label' => 'WhatsApp Checkout Recovery Template Name',
                'type' => 'text',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'whatsapp_recovery_delay_hours',
                'value' => '1',
                'group' => 'whatsapp',
                'label' => 'WhatsApp Recovery Delay (hours)',
                'type' => 'number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('whatsapp_recovery_sent_at');
        });

        DB::table('settings')
            ->whereIn('key', [
                'whatsapp_recovery_enabled',
                'whatsapp_recovery_template_name',
                'whatsapp_recovery_delay_hours',
            ])
            ->delete();
    }
};
