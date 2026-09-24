<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore([
            'key' => 'whatsapp_welcome_template_name',
            'value' => 'welcome_message',
            'group' => 'whatsapp',
            'label' => 'WhatsApp Welcome Template Name',
            'type' => 'text',
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'whatsapp_welcome_template_name')->delete();
    }
};
