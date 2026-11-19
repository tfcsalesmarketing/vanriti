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
            if (! Schema::hasColumn('products', 'amazon_url')) {
                $table->string('amazon_url', 1000)->nullable()->after('video_url');
            }
            if (! Schema::hasColumn('products', 'flipkart_url')) {
                $table->string('flipkart_url', 1000)->nullable()->after('amazon_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('products', 'amazon_url')) {
                $columnsToDrop[] = 'amazon_url';
            }
            if (Schema::hasColumn('products', 'flipkart_url')) {
                $columnsToDrop[] = 'flipkart_url';
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};

