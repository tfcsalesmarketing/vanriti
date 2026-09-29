<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Records the moment the order-confirmation WhatsApp was handed to
            // Meta, so a duplicated trigger (Razorpay callback racing the
            // webhook) can never send the message twice.
            $table->timestamp('whatsapp_confirmation_sent_at')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('whatsapp_confirmation_sent_at');
        });
    }
};
