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
        // 1. Push Subscriptions Table (VAPID / Web Push endpoints)
        if (! Schema::hasTable('push_subscriptions')) {
            Schema::create('push_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('session_id', 100)->nullable()->index();
                $table->text('endpoint');
                $table->string('endpoint_hash', 32)->unique();
                $table->text('public_key');
                $table->string('auth_token', 255);
                $table->string('content_encoding', 30)->default('aes128gcm');
                $table->string('device_type', 20)->default('mobile'); // mobile, desktop, tablet
                $table->string('browser', 30)->default('chrome');
                $table->string('status', 20)->default('active'); // active, unsubscribed, expired
                $table->timestamp('last_active_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'device_type']);
            });
        }

        // 2. Push Broadcast Campaigns Table
        if (! Schema::hasTable('push_campaigns')) {
            Schema::create('push_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('body');
                $table->string('url', 500);
                $table->string('icon_url', 500)->nullable();
                $table->string('image_url', 500)->nullable();
                $table->string('target_audience', 50)->default('all'); // all, abandoned_cart, customers
                $table->string('status', 20)->default('draft'); // draft, sending, completed, failed
                $table->unsignedInteger('total_targeted')->default(0);
                $table->unsignedInteger('total_sent')->default(0);
                $table->unsignedInteger('total_failed')->default(0);
                $table->unsignedInteger('total_clicked')->default(0);
                $table->foreignId('sent_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'created_at']);
            });
        }

        // 3. Add guest_phone column to carts table for Side-Cart Lead Capture
        if (Schema::hasTable('carts') && ! Schema::hasColumn('carts', 'guest_phone')) {
            Schema::table('carts', function (Blueprint $table) {
                $table->string('guest_phone', 20)->nullable()->after('session_id')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_campaigns');
        Schema::dropIfExists('push_subscriptions');

        if (Schema::hasTable('carts') && Schema::hasColumn('carts', 'guest_phone')) {
            Schema::table('carts', function (Blueprint $table) {
                $table->dropColumn('guest_phone');
            });
        }
    }
};

