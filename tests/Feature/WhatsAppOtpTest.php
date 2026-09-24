<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WhatsAppOtpTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed ShipMojo + ShipMojo-style service credentials a ShipMojo
        // way, exactly like the app does through the settings table.
        $settings = [
            // WhatsApp (Meta Cloud API) group
            ['key' => 'whatsapp_otp_enabled',        'value' => '1', 'group' => 'whatsapp', 'label' => 'Enable WhatsApp OTP',   'type' => 'boolean'],
            ['key' => 'whatsapp_phone_number_id',    'value' => '123456789', 'group' => 'whatsapp', 'label' => 'Phone Number ID', 'type' => 'text'],
            ['key' => 'whatsapp_template_name',      'value' => 'vanriti_otp', 'group' => 'whatsapp', 'label' => 'OTP Template',    'type' => 'text'],
        ];

        foreach ($settings as $s) {
            \App\Models\Setting::updateOrCreate(['key' => $s['key']], $s);
        }

        \App\Models\Setting::updateOrCreate(['key' => 'whatsapp_welcome_template_name'], [
            'group' => 'whatsapp',
            'value' => 'welcome_message',
            'label' => 'WhatsApp Welcome Template Name',
            'type' => 'text',
        ]);

        \App\Models\Setting::updateOrCreate(['key' => 'whatsapp_access_token'], [
            'group' => 'whatsapp',
            'value' => \Illuminate\Support\Facades\Crypt::encryptString('fake-test-token'),
            'label' => 'WhatsApp Access Token',
            'type' => 'password',
        ]);
    }

    public function test_send_welcome_formats_payload_with_parameter_name(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response(['messages' => [['id' => 'wamid.welcome123']]], 200),
        ]);

        $service = app(\App\Services\WhatsAppOtpService::class);
        $result = $service->sendWelcome('9876543210', 'Aarav');

        $this->assertSame('1', $result['result']);

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            $data = $request->data();
            $param = $data['template']['components'][0]['parameters'][0] ?? [];

            return $request->url() === 'https://graph.facebook.com/v21.0/123456789/messages'
                && $data['template']['name'] === 'welcome_message'
                && ($param['parameter_name'] ?? null) === 'name'
                && ($param['text'] ?? null) === 'Aarav';
        });
    }

    public function test_send_welcome_uses_fallback_when_name_is_empty(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response(['messages' => [['id' => 'wamid.welcome123']]], 200),
        ]);

        $service = app(\App\Services\WhatsAppOtpService::class);
        $result = $service->sendWelcome('9876543210', '');

        $this->assertSame('1', $result['result']);

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            $param = $request->data()['template']['components'][0]['parameters'][0] ?? [];

            return ($param['text'] ?? null) === 'Customer';
        });
    }
}
