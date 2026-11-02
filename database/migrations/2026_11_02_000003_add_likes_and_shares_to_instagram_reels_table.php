<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instagram_reels', function (Blueprint $table) {
            if (! Schema::hasColumn('instagram_reels', 'likes_count')) {
                $table->unsignedInteger('likes_count')->default(0)->after('views_count');
            }
            if (! Schema::hasColumn('instagram_reels', 'shares_count')) {
                $table->unsignedInteger('shares_count')->default(0)->after('likes_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('instagram_reels', function (Blueprint $table) {
            if (Schema::hasColumn('instagram_reels', 'likes_count')) {
                $table->dropColumn('likes_count');
            }
            if (Schema::hasColumn('instagram_reels', 'shares_count')) {
                $table->dropColumn('shares_count');
            }
        });
    }
};
