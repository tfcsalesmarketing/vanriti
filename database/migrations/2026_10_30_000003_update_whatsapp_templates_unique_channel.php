<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_templates')) {
            // 1. Set existing null channels to vanriti
            DB::table('whatsapp_templates')
                ->whereNull('channel')
                ->orWhere('channel', '')
                ->update(['channel' => 'vanriti']);

            // 2. Update unique index to include channel
            Schema::table('whatsapp_templates', function (Blueprint $table) {
                // Drop existing unique key if it exists
                $table->dropUnique('whatsapp_templates_name_language_unique');
                
                // Add composite unique key with channel
                $table->unique(['name', 'language', 'channel'], 'whatsapp_templates_name_lang_channel_uniq');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('whatsapp_templates')) {
            Schema::table('whatsapp_templates', function (Blueprint $table) {
                $table->dropUnique('whatsapp_templates_name_lang_channel_uniq');
                $table->unique(['name', 'language'], 'whatsapp_templates_name_language_unique');
            });
        }
    }
};
