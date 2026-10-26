<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->enum('status', ['pending', 'notified', 'cancelled'])->default('pending');
            $table->string('channel', 20)->default('whatsapp');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['product_variant_id', 'status']);
            $table->index(['phone', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_notifications');
    }
};
