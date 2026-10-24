<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_campaigns')) {
            Schema::create('whatsapp_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->string('template_name', 120);
                $table->string('template_lang', 10)->default('en');
                $table->string('target_segment', 50)->default('all'); // all, abandoned_cart, buyers, inactive, custom
                $table->json('parameters')->nullable();
                $table->string('header_image_url', 500)->nullable();
                $table->string('status', 30)->default('draft')->index(); // draft, scheduled, processing, completed, failed
                $table->unsignedInteger('total_recipients')->default(0);
                $table->unsignedInteger('sent_count')->default(0);
                $table->unsignedInteger('delivered_count')->default(0);
                $table->unsignedInteger('read_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->timestamp('scheduled_at')->nullable()->index();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('whatsapp_campaign_recipients')) {
            Schema::create('whatsapp_campaign_recipients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('whatsapp_campaign_id')
                    ->constrained('whatsapp_campaigns')
                    ->cascadeOnDelete();
                $table->string('phone', 25)->index();
                $table->string('customer_name', 255)->nullable();
                $table->string('status', 30)->default('pending')->index(); // pending, sent, delivered, read, failed
                $table->string('meta_message_id', 150)->nullable()->index();
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['whatsapp_campaign_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_campaign_recipients');
        Schema::dropIfExists('whatsapp_campaigns');
    }
};
