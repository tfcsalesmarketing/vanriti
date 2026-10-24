<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_conversations')) {
            Schema::create('whatsapp_conversations', function (Blueprint $table) {
                $table->id();
                $table->string('phone', 25)->unique();
                $table->string('wa_id', 35)->nullable()->index();
                $table->string('contact_name', 255)->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('unread_count')->default(0);
                $table->timestamp('last_message_at')->nullable()->index();
                $table->text('last_message_preview')->nullable();
                $table->timestamp('window_expires_at')->nullable()->index();
                $table->string('status', 30)->default('active')->index();
                $table->foreignId('assigned_to')->nullable()->constrained('admins')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('whatsapp_messages')) {
            Schema::create('whatsapp_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('whatsapp_conversation_id')
                    ->constrained('whatsapp_conversations')
                    ->cascadeOnDelete();
                $table->string('direction', 15)->default('inbound')->index(); // 'inbound' or 'outbound'
                $table->string('message_type', 30)->default('text')->index(); // text, image, document, audio, video, template, interactive, order
                $table->text('body')->nullable();
                $table->string('media_id', 100)->nullable();
                $table->string('media_url', 500)->nullable();
                $table->string('media_mime_type', 100)->nullable();
                $table->string('media_filename', 255)->nullable();
                $table->string('meta_message_id', 150)->nullable()->unique();
                $table->string('status', 30)->default('sent')->index(); // queued, sent, delivered, read, failed
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->foreignId('sender_admin_id')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['whatsapp_conversation_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_conversations');
    }
};
