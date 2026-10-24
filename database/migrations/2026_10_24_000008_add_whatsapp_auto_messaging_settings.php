<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            [
                'group' => 'whatsapp',
                'key' => 'whatsapp_auto_welcome_enabled',
                'value' => '0',
                'label' => 'Enable Auto Welcome Message on Registration',
                'type' => 'boolean',
            ],
            [
                'group' => 'whatsapp',
                'key' => 'whatsapp_bot_auto_reply_enabled',
                'value' => '0',
                'label' => 'Enable Chatbot Auto-Replies & Menus',
                'type' => 'boolean',
            ],
            [
                'group' => 'whatsapp',
                'key' => 'whatsapp_away_message_enabled',
                'value' => '0',
                'label' => 'Enable Away Auto-Responder',
                'type' => 'boolean',
            ],
            [
                'group' => 'whatsapp',
                'key' => 'whatsapp_away_message_text',
                'value' => 'Thank you for contacting VANRITI! 🌿 Our care team is currently offline and will reply during business hours (9:00 AM - 7:00 PM IST).',
                'label' => 'Away Message Copy',
                'type' => 'textarea',
            ],
        ];

        foreach ($settings as $item) {
            Setting::firstOrCreate(
                ['key' => $item['key']],
                $item
            );
        }
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            'whatsapp_auto_welcome_enabled',
            'whatsapp_bot_auto_reply_enabled',
            'whatsapp_away_message_enabled',
            'whatsapp_away_message_text',
        ])->delete();
    }
};
