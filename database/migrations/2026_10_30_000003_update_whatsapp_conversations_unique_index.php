<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_conversations')) {
            $driver = DB::getDriverName();

            if ($driver === 'sqlite') {
                Schema::table('whatsapp_conversations', function (Blueprint $table) {
                    try {
                        $table->dropUnique('whatsapp_conversations_phone_unique');
                    } catch (\Throwable) {}
                    try {
                        $table->unique(['phone', 'channel'], 'whatsapp_conv_phone_channel_unique');
                    } catch (\Throwable) {}
                });
            } else {
                $existingPhoneUnique = DB::select("SHOW INDEXES FROM whatsapp_conversations WHERE Key_name = 'whatsapp_conversations_phone_unique'");
                if (! empty($existingPhoneUnique)) {
                    DB::statement('ALTER TABLE whatsapp_conversations DROP INDEX whatsapp_conversations_phone_unique');
                }

                $existingComposite = DB::select("SHOW INDEXES FROM whatsapp_conversations WHERE Key_name = 'whatsapp_conv_phone_channel_unique'");
                if (empty($existingComposite)) {
                    DB::statement('ALTER TABLE whatsapp_conversations ADD UNIQUE KEY whatsapp_conv_phone_channel_unique (phone, channel)');
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('whatsapp_conversations')) {
            $driver = DB::getDriverName();

            if ($driver === 'sqlite') {
                Schema::table('whatsapp_conversations', function (Blueprint $table) {
                    try {
                        $table->dropUnique('whatsapp_conv_phone_channel_unique');
                    } catch (\Throwable) {}
                    try {
                        $table->unique('phone', 'whatsapp_conversations_phone_unique');
                    } catch (\Throwable) {}
                });
            } else {
                $existingComposite = DB::select("SHOW INDEXES FROM whatsapp_conversations WHERE Key_name = 'whatsapp_conv_phone_channel_unique'");
                if (! empty($existingComposite)) {
                    DB::statement('ALTER TABLE whatsapp_conversations DROP INDEX whatsapp_conv_phone_channel_unique');
                }

                $existingPhoneUnique = DB::select("SHOW INDEXES FROM whatsapp_conversations WHERE Key_name = 'whatsapp_conversations_phone_unique'");
                if (empty($existingPhoneUnique)) {
                    DB::statement('ALTER TABLE whatsapp_conversations ADD UNIQUE KEY whatsapp_conversations_phone_unique (phone)');
                }
            }
        }
    }
};
