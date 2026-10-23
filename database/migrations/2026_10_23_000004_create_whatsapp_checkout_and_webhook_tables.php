<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_checkout_sessions')) {
            Schema::create('whatsapp_checkout_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('phone', 25)->index();
                $table->string('wa_id', 35)->nullable()->index();
                $table->string('customer_name', 255)->nullable();
                $table->string('session_token', 64)->unique();
                $table->string('status', 50)->default('cart_received')->index();
                $table->json('cart_data')->nullable();
                $table->json('address_data')->nullable();
                $table->string('pincode', 10)->nullable()->index();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('shipping_charge', 12, 2)->default(0);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('grand_total', 12, 2)->default(0);
                $table->string('currency', 5)->default('INR');
                $table->string('payment_method', 25)->nullable();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->string('razorpay_order_id', 100)->nullable()->index();
                $table->string('last_message_id', 120)->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();

                $table->index(['phone', 'status']);
            });
        }

        if (! Schema::hasTable('whatsapp_webhook_logs')) {
            Schema::create('whatsapp_webhook_logs', function (Blueprint $table) {
                $table->id();
                $table->string('message_id', 120)->unique();
                $table->string('from_phone', 25)->nullable()->index();
                $table->string('event_type', 50)->default('message')->index();
                $table->json('payload')->nullable();
                $table->boolean('processed')->default(false);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'checkout_source')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('checkout_source', 25)->default('web')->index()->after('payment_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'checkout_source')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('checkout_source');
            });
        }

        Schema::dropIfExists('whatsapp_webhook_logs');
        Schema::dropIfExists('whatsapp_checkout_sessions');
    }
};
