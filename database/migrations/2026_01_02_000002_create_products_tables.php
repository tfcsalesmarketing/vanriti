<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable();
            $table->string('short_description', 500)->nullable();
            $table->longText('description')->nullable();
            $table->text('highlights')->nullable();
            $table->text('ingredients')->nullable();
            $table->text('benefits')->nullable();
            $table->text('how_to_use')->nullable();
            $table->text('directions')->nullable();
            $table->text('warnings')->nullable();
            $table->text('precautions')->nullable();
            $table->text('disclaimer')->nullable();
            $table->string('net_quantity')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('mrp', 12, 2)->nullable();
            $table->decimal('selling_price', 12, 2)->nullable();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->string('hsn_code')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('manufacturer_address')->nullable();
            $table->string('country_of_origin')->nullable();
            $table->string('shelf_life')->nullable();
            $table->string('expiry_info')->nullable();
            $table->string('video_url')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->text('search_keywords')->nullable();
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_bestseller')->default(false);
            $table->boolean('is_new_arrival')->default(false);
            $table->integer('total_sold')->default(0);
            $table->integer('review_count')->default(0);
            $table->decimal('review_rating', 3, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_featured', 'is_bestseller', 'is_new_arrival']);
            $table->index('created_at');
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('mrp', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->string('weight')->nullable();
            $table->string('dimensions')->nullable();
            $table->string('image')->nullable();
            $table->string('barcode')->nullable();
            $table->string('hsn_code')->nullable();
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'status']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('owner');
            $table->string('image_path');
            $table->string('alt_text')->nullable();
            $table->enum('type', ['main', 'gallery', 'thumbnail'])->default('gallery');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'type', 'sort_order']);
        });

        Schema::create('product_category', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->primary(['product_id', 'category_id']);
        });

        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->morphs('stockable');
            $table->integer('stock_on_hand')->default(0);
            $table->integer('reserved')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->timestamps();

            $table->unique(['stockable_type', 'stockable_id'], 'inventory_unique');
        });

        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('stockable');
            $table->morphs('reference');
            $table->enum('type', ['purchase', 'sale', 'adjustment', 'return', 'reversal'])->default('adjustment');
            $table->integer('quantity_change');
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->text('reason')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['stockable_type', 'stockable_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('product_category');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
    }
};