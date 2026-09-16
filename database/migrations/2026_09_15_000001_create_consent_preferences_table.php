<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Current authenticated consent state, one row per user. This row is the
     * authoritative consent source for authenticated resolution; the encrypted
     * vanriti_consent cookie mirrors it for browser-visible state.
     *
     * necessary is stored (always true) but is never an optional choice.
     */
    public function up(): void
    {
        Schema::create('consent_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('necessary')->default(true);
            $table->boolean('analytics')->default(false);
            $table->boolean('advertising')->default(false);
            $table->boolean('marketing_communications')->default(false);
            $table->string('consent_version');
            $table->string('policy_version');
            $table->string('source')->index();
            $table->timestamp('consented_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_preferences');
    }
};
