<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dadi_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('dadi_conversations')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant', 'system', 'tool'])->default('user');
            $table->unsignedBigInteger('sequence');
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'sequence'], 'dadi_messages_conversation_sequence_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dadi_messages');
    }
};
