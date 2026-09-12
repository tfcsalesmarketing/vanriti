<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            // ShipMojo Integration
            ['key' => 'shipmojo_enabled',              'value' => '0',      'group' => 'shipmojo', 'label' => 'Enable ShipMojo',              'type' => 'boolean'],
            ['key' => 'shipmojo_public_key',           'value' => '',       'group' => 'shipmojo', 'label' => 'ShipMojo Public Key',           'type' => 'text'],
            ['key' => 'shipmojo_private_key',          'value' => '',       'group' => 'shipmojo', 'label' => 'ShipMojo Private Key',          'type' => 'password'],
            ['key' => 'shipmojo_warehouse_id',         'value' => '',       'group' => 'shipmojo', 'label' => 'Default Warehouse ID',          'type' => 'text'],
            ['key' => 'shipmojo_warehouse_pincode',    'value' => '',       'group' => 'shipmojo', 'label' => 'Warehouse Pincode (for rates)', 'type' => 'text'],
            ['key' => 'shipmojo_auto_push',            'value' => '0',      'group' => 'shipmojo', 'label' => 'Auto-Push Orders to ShipMojo', 'type' => 'boolean'],
            ['key' => 'shipmojo_auto_assign',          'value' => '1',      'group' => 'shipmojo', 'label' => 'Auto-Assign Courier After Push', 'type' => 'boolean'],
            ['key' => 'shipmojo_default_weight_grams', 'value' => '500',    'group' => 'shipmojo', 'label' => 'Default Weight (grams)',        'type' => 'number'],
            ['key' => 'shipmojo_default_length',       'value' => '20',     'group' => 'shipmojo', 'label' => 'Default Box Length (cm)',       'type' => 'number'],
            ['key' => 'shipmojo_default_width',        'value' => '15',     'group' => 'shipmojo', 'label' => 'Default Box Width (cm)',        'type' => 'number'],
            ['key' => 'shipmojo_default_height',       'value' => '10',     'group' => 'shipmojo', 'label' => 'Default Box Height (cm)',       'type' => 'number'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insertOrIgnore($setting);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('group', 'shipmojo')->delete();
    }
};
