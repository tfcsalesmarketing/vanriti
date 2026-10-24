<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'whatsapp_shipped_sent_at')) {
                $table->timestamp('whatsapp_shipped_sent_at')->nullable()->after('whatsapp_recovery_sent_at');
            }
            if (! Schema::hasColumn('orders', 'whatsapp_delivered_sent_at')) {
                $table->timestamp('whatsapp_delivered_sent_at')->nullable()->after('whatsapp_shipped_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'whatsapp_shipped_sent_at')) {
                $table->dropColumn('whatsapp_shipped_sent_at');
            }
            if (Schema::hasColumn('orders', 'whatsapp_delivered_sent_at')) {
                $table->dropColumn('whatsapp_delivered_sent_at');
            }
        });
    }
};
