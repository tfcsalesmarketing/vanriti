<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            // A code is now addressed by email OR phone, so neither side is
            // mandatory on its own. The (phone, purpose) index already exists
            // and keeps serving the WhatsApp channel.
            $table->string('phone')->nullable()->change();
            $table->string('email')->nullable()->after('phone');
            $table->index(['email', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropIndex(['email', 'purpose']);
            $table->dropColumn('email');
            $table->string('phone')->nullable(false)->change();
        });
    }
};
