<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * First-party behavioral event log backing the admin Analytics Dashboard.
     *
     * Deliberately decoupled: no foreign keys, no unique constraints, and
     * never PII. session_id is an HMAC-SHA256 of the Laravel session id so the
     * storefront may be measured without persisting any personal identifier.
     * Order/revenue truth remains the orders/order_items/payments tables; this
     * table only records behavioral events (page_view, view_item, add_to_cart,
     * begin_checkout, add_payment_info) observed on the storefront.
     */
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 60);
            $table->char('session_id', 64);
            $table->foreignId('user_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('sku', 190)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('source', 120)->nullable();
            $table->string('medium', 120)->nullable();
            $table->string('campaign', 190)->nullable();
            $table->string('device_type', 20)->nullable();
            $table->string('landing_path', 500)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['event_type', 'occurred_at']);
            $table->index(['event_type', 'sku']);
            $table->index(['session_id']);
            $table->index(['user_id']);
            $table->index(['sku', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
