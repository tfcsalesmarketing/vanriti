<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guest checkout: an order no longer requires an account.
     *
     * - orders.user_id becomes nullable (guest orders carry guest_* columns
     *   instead). The cascade-on-delete FK stays in place, so deleting a user
     *   still removes their orders; guest rows (user_id NULL) are untouched.
     * - access_token stores ONLY the SHA-256 hash of a 40-char random token.
     *   The raw token lives in the placing browser's session, never in the DB.
     * - refunds.user_id becomes nullable so cancelling a paid guest order can
     *   still produce the refund record finance needs (RefundService::createForOrder
     *   writes user_id from the order when no user is attached).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('guest_name')->nullable()->after('user_id');
            $table->string('guest_email')->nullable()->after('guest_name');
            $table->string('guest_mobile')->nullable()->after('guest_email');
            $table->string('guest_session_id', 64)->nullable()->after('guest_mobile')->index();
            $table->string('access_token', 64)->nullable()->after('guest_session_id')->unique();
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['access_token']);
            $table->dropIndex(['guest_session_id']);
            $table->dropColumn(['guest_name', 'guest_email', 'guest_mobile', 'guest_session_id', 'access_token']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
