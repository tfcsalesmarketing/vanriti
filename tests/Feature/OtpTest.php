<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $seed = [
            ['key' => 'whatsapp_otp_enabled',     'value' => '1',                'group' => 'whatsapp', 'label' => 'Enable WhatsApp OTP',   'type' => 'boolean'],
            ['key' => 'whatsapp_phone_number_id', 'value' => '9988776655',        'group' => 'whatsapp', 'label' => 'Phone Number ID',       'type' => 'text'],
            ['key' => 'whatsapp_template_name',   'value' => 'vanriti_otp',      'group' => 'whatsapp', 'label' => 'OTP Template',          'type' => 'text'],
        ];

        foreach ($seed as $s) {
            \App\Models\Setting::updateOrCreate(['key' => $s['key']], $s);
        }

        \App\Models\Setting::updateOrCreate(['key' => 'whatsapp_access_token'], [
            'group' => 'whatsapp',
            'value' => \Illuminate\Support\Facades\Crypt::encryptString('fake-test-token'),
            'label' => 'WhatsApp Access Token',
            'type' => 'password',
        ]);
    }

    public function test_otp_send_hits_whatsapp_and_persists(): void
    {
        User::factory()->create(['status' => 'active', 'phone' => '9876543210']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.abc123']]], 200)]);

        $response = $this->postJson('/otp/send', [
            'phone' => '9876543210',
            'purpose' => 'login',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('otp_codes', [
            'phone' => '9876543210',
            'purpose' => 'login',
            'channel' => 'whatsapp',
        ]);
    }

    public function test_verify_marks_user_phone_verified(): void
    {
        $user = User::factory()->create(['status' => 'active', 'phone' => '9876543210']);

        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.abc']]], 200)]);

        $this->postJson('/otp/send', ['phone' => '9876543210', 'purpose' => 'login']);

        $recorded = Http::recorded();
        $sentCode = $recorded[0][0]['template']['components'][0]['parameters'][0]['text'] ?? null;

        $verify = $this->postJson('/otp/verify', [
            'phone' => '9876543210',
            'code' => $sentCode,
            'purpose' => 'login',
        ]);

        $verify->assertOk();
        $verify->assertJson(['result' => '1']);

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_otp_ignored_when_feature_disabled(): void
    {
        User::factory()->create(['status' => 'active', 'phone' => '9876543210']);
        \App\Models\Setting::where('key', 'whatsapp_otp_enabled')->update(['value' => '0']);

        $response = $this->postJson('/otp/send', [
            'phone' => '9876543210',
            'purpose' => 'login',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['result' => '0']);
    }
}
