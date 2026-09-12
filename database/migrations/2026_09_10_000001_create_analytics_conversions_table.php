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
        Schema::create('analytics_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->string('order_number');
            $table->string('channel')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['event_type', 'order_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_conversions');
    }
};