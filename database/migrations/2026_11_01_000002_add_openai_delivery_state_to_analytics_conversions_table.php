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
        Schema::table('analytics_conversions', function (Blueprint $table) {
            $table->string('openai_state')->nullable()->index()->after('meta_attempts');
            $table->timestamp('openai_sent_at')->nullable()->after('openai_state');
            $table->unsignedTinyInteger('openai_attempts')->default(0)->after('openai_sent_at');
            $table->string('openai_event_id', 64)->nullable()->index()->after('openai_attempts');
            $table->timestamp('openai_browser_rendered_at')->nullable()->after('openai_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analytics_conversions', function (Blueprint $table) {
            $table->dropIndex(['openai_state']);
            $table->dropIndex(['openai_event_id']);
            $table->dropColumn(['openai_state', 'openai_sent_at', 'openai_attempts', 'openai_event_id', 'openai_browser_rendered_at']);
        });
    }
};

