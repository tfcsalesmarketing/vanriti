<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dadi_recommendation_events', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 26)->unique();
            $table->foreignId('conversation_id')->nullable()->constrained('dadi_conversations')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('action', ['impression', 'click', 'add_to_cart', 'buy_now', 'purchase']);
            $table->string('dedupe_key')->nullable()->unique();
            $table->string('session_id')->nullable()->index();
            $table->string('guest_cart_key')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cart_id')->nullable()->constrained('carts')->nullOnDelete();
            $table->foreignId('cart_item_id')->nullable()->constrained('cart_items')->nullOnDelete();
            $table->string('order_number', 40)->nullable()->index();
            $table->timestamps();

            $table->index(['conversation_id', 'product_id', 'action'], 'dadi_evt_conv_prod_action_idx');
            $table->index(['user_id', 'product_id', 'action'], 'dadi_evt_user_product_action_idx');
            $table->unique(['order_number', 'product_id'], 'dadi_evt_order_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dadi_recommendation_events');
    }
};
