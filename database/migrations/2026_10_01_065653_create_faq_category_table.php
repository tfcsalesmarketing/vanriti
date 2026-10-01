<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_id')->constrained('faqs')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['faq_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_category');
    }
};
