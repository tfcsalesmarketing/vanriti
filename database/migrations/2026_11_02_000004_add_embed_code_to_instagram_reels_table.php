<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instagram_reels', function (Blueprint $table) {
            if (! Schema::hasColumn('instagram_reels', 'embed_code')) {
                $table->text('embed_code')->nullable()->after('instagram_shortcode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('instagram_reels', function (Blueprint $table) {
            if (Schema::hasColumn('instagram_reels', 'embed_code')) {
                $table->dropColumn('embed_code');
            }
        });
    }
};

