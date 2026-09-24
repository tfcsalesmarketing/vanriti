<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['key' => 'shipmojo_webhook_enabled', 'value' => '0', 'group' => 'shipmojo', 'label' => 'Enable ShipMojo Webhook Sync', 'type' => 'boolean'],
            ['key' => 'shipmojo_webhook_secret',  'value' => '',  'group' => 'shipmojo', 'label' => 'ShipMojo Webhook Secret',    'type' => 'password'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insertOrIgnore($setting);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['shipmojo_webhook_enabled', 'shipmojo_webhook_secret'])->delete();
    }
};