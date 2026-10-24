<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'whatsapp_waba_id'],
            [
                'value' => '',
                'group' => 'whatsapp',
                'label' => 'WhatsApp Business Account (WABA) ID',
                'type' => 'text',
            ]
        );
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'whatsapp_waba_id')->delete();
    }
};
