<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            // WhatsApp OTP (Meta Cloud API) — Feature Gating
            ['key' => 'whatsapp_otp_enabled',            'value' => '0', 'group' => 'whatsapp', 'label' => 'Enable WhatsApp OTP',         'type' => 'boolean'],
            ['key' => 'whatsapp_phone_number_id',        'value' => '',  'group' => 'whatsapp', 'label' => 'WhatsApp Phone Number ID',    'type' => 'text'],
            ['key' => 'whatsapp_access_token',           'value' => '',  'group' => 'whatsapp', 'label' => 'WhatsApp Access Token',       'type' => 'password'],
            ['key' => 'whatsapp_template_name',          'value' => '',  'group' => 'whatsapp', 'label' => 'WhatsApp OTP Template Name',  'type' => 'text'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->insertOrIgnore($setting);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('group', 'whatsapp')->delete();
    }
};
