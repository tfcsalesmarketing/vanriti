<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [
            [
                'key' => 'whatsapp_away_timing_mode',
                'value' => 'outside_hours',
                'group' => 'whatsapp',
                'type' => 'text',
                'label' => 'Away Message Mode (outside_hours / always)',
            ],
            [
                'key' => 'whatsapp_business_hours_start',
                'value' => '10:00',
                'group' => 'whatsapp',
                'type' => 'text',
                'label' => 'Business Hours Start (24h format, e.g. 10:00)',
            ],
            [
                'key' => 'whatsapp_business_hours_end',
                'value' => '19:00',
                'group' => 'whatsapp',
                'type' => 'text',
                'label' => 'Business Hours End (24h format, e.g. 19:00)',
            ],
            [
                'key' => 'whatsapp_business_days',
                'value' => 'Mon,Tue,Wed,Thu,Fri,Sat',
                'group' => 'whatsapp',
                'type' => 'text',
                'label' => 'Business Working Days (comma-separated: Mon,Tue,Wed,Thu,Fri,Sat,Sun)',
            ],
            [
                'key' => 'whatsapp_timezone',
                'value' => 'Asia/Kolkata',
                'group' => 'whatsapp',
                'type' => 'text',
                'label' => 'Store Timezone (e.g. Asia/Kolkata)',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                    'label' => $setting['label'],
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::whereIn('key', [
            'whatsapp_away_timing_mode',
            'whatsapp_business_hours_start',
            'whatsapp_business_hours_end',
            'whatsapp_business_days',
            'whatsapp_timezone',
        ])->delete();
    }
};
