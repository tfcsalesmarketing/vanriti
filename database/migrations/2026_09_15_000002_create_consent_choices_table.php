<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only history of consent decisions. Records are created by the
     * ConsentService for every explicit choice and are never updated or
     * deleted as part of normal consent changes. Deliberately carries no IP
     * address, user agent or other PII beyond the user-reference itself.
     */
    public function up(): void
    {
        Schema::create('consent_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('consent_version');
            $table->string('policy_version');
            $table->boolean('necessary');
            $table->boolean('analytics');
            $table->boolean('advertising');
            $table->boolean('marketing_communications');
            $table->string('source')->index();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_choices');
    }
};
