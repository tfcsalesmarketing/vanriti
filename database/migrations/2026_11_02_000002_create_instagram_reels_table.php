<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_reels', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('instagram_url', 500)->nullable();
            $table->string('instagram_shortcode', 100)->nullable();
            $table->string('video_url', 500)->nullable();
            $table->string('thumbnail_url', 500)->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('views_count', 50)->nullable();
            $table->text('caption')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_reels');
    }
};
