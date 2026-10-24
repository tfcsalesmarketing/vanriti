<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_bot_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('keywords'); // Comma-separated triggers, e.g. "track, order status, where is my order"
            $table->string('match_type', 30)->default('contains'); // contains, exact, starts_with
            $table->string('action_type', 40)->default('text'); // text, dynamic_order_tracking, interactive_menu, catalog, coupon
            $table->text('response_text')->nullable();
            $table->json('buttons')->nullable(); // Optional interactive reply buttons
            $table->boolean('is_active')->default(true)->index();
            $table->integer('priority')->default(0);
            $table->integer('cooldown_minutes')->default(30);
            $table->unsignedInteger('hit_count')->default(0);
            $table->timestamps();
        });

        Schema::create('whatsapp_quick_replies', function (Blueprint $table) {
            $table->id();
            $table->string('shortcut', 50)->unique(); // e.g. "greet", "tracking", "consult"
            $table->string('title', 150);
            $table->string('category', 60)->default('General');
            $table->text('content');
            $table->unsignedInteger('usage_count')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_quick_replies');
        Schema::dropIfExists('whatsapp_bot_rules');
    }
};
