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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('oppref', 190)->nullable()->index()->after('internal_notes');
            $table->string('attribution_source', 60)->nullable()->after('oppref');
            $table->string('attribution_campaign', 190)->nullable()->after('attribution_source');
            $table->string('attribution_medium', 60)->nullable()->after('attribution_campaign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['oppref']);
            $table->dropColumn(['oppref', 'attribution_source', 'attribution_campaign', 'attribution_medium']);
        });
    }
};

