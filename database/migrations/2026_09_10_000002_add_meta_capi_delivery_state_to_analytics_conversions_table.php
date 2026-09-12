<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Meta CAPI delivery state to the durable analytics_conversions
     * ledger. The existing UNIQUE(event_type, order_number) constraint and the
     * Canonical purchase row are untouched; these nullable columns only track
     * external delivery so retries stay bounded and deterministic.
     */
    public function up(): void
    {
        Schema::table('analytics_conversions', function (Blueprint $table) {
            $table->string('meta_state')->nullable()->index();
            $table->timestamp('meta_sent_at')->nullable();
            $table->unsignedTinyInteger('meta_attempts')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytics_conversions', function (Blueprint $table) {
            $table->dropIndex(['meta_state']);
            $table->dropColumn(['meta_state', 'meta_sent_at', 'meta_attempts']);
        });
    }
};