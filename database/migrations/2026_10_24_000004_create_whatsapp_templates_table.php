<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('whatsapp_templates')) {
            Schema::create('whatsapp_templates', function (Blueprint $table) {
                $table->id();
                $table->string('meta_template_id', 120)->nullable()->index();
                $table->string('name', 150)->index();
                $table->string('category', 40)->default('MARKETING'); // MARKETING, UTILITY, AUTHENTICATION
                $table->string('status', 30)->default('PENDING')->index(); // APPROVED, PENDING, REJECTED, PAUSED, DISABLED
                $table->string('language', 10)->default('en');
                $table->string('header_type', 20)->nullable(); // TEXT, IMAGE, DOCUMENT, VIDEO
                $table->text('header_text')->nullable();
                $table->text('body_text');
                $table->text('footer_text')->nullable();
                $table->json('buttons')->nullable();
                $table->text('rejected_reason')->nullable();
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();

                $table->unique(['name', 'language']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
