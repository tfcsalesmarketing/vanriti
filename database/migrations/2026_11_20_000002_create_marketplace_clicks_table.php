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
        if (! Schema::hasTable('marketplace_clicks')) {
            Schema::create('marketplace_clicks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')
                    ->constrained('products')
                    ->cascadeOnDelete();
                $table->string('sku', 100)->nullable()->index();
                $table->string('marketplace', 30)->index(); // 'amazon' or 'flipkart'
                $table->string('session_id', 100)->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('referrer')->nullable();
                $table->timestamps();

                $table->index(['product_id', 'marketplace']);
                $table->index(['created_at', 'marketplace']);
                $table->index(['sku', 'marketplace']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_clicks');
    }
};

