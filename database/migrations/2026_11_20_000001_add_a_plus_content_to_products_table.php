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
        Schema::table('products', function (Blueprint $table) {
            $table->json('a_plus_content')->nullable()->after('flipkart_url');
            $table->boolean('has_a_plus')->default(false)->after('a_plus_content');

            $table->index('has_a_plus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['has_a_plus']);
            $table->dropColumn(['a_plus_content', 'has_a_plus']);
        });
    }
};
