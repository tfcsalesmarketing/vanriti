<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OtpPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $seed = [
            ['key' => 'whatsapp_otp_enabled', 'value' => '1', 'group' => 'whatsapp', 'label' => 'Enable WhatsApp OTP', 'type' => 'boolean'],
            ['key' => 'whatsapp_phone_number_id', 'value' => '9988776655', 'group' => 'whatsapp', 'label' => 'Phone Number ID', 'type' => 'text'],
            ['key' => 'whatsapp_template_name', 'value' => 'vanriti_otp', 'group' => 'whatsapp', 'label' => 'OTP Template', 'type' => 'text'],
        ];

        foreach ($seed as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }

        Setting::updateOrCreate(['key' => 'whatsapp_access_token'], [
            'group' => 'whatsapp',
            'value' => \Illuminate\Support\Facades\Crypt::encryptString('fake-test-token'),
            'label' => 'WhatsApp Access Token',
            'type' => 'password',
        ]);
    }

    public function test_phone_only_user_can_reset_password_via_otp(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'phone' => '9876543210',
            'email' => null,
            'password' => Hash::make('OldPass#123'),
        ]);

        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.abc']]], 200)]);

        $this->postJson('/otp/send', ['phone' => '9876543210', 'purpose' => 'reset_password']);

        $recorded = Http::recorded();
        $sentCode = $recorded[0][0]['template']['components'][0]['parameters'][0]['text'] ?? null;
        $this->assertNotNull($sentCode);

        $verify = $this->postJson('/otp/verify', [
            'phone' => '9876543210',
            'code' => $sentCode,
            'purpose' => 'reset_password',
        ]);

        $verify->assertStatus(302);
        $verify->assertRedirect();

        $resetUrl = $verify->headers->get('Location');

        $this->assertStringContainsString(url('reset-password/'), $resetUrl);

        $token = last(explode('/', $resetUrl));

        $this->get($resetUrl)
            ->assertOk()
            ->assertSee('vrResetForm')
            ->assertSee('name="phone"', false);

        $response = $this->from($resetUrl)->post(route('password.store'), [
            'token' => $token,
            'phone' => '9876543210',
            'password' => 'NewPass#456',
            'password_confirmation' => 'NewPass#456',
        ]);

        $response->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($user, 'web');

        $user->refresh();
        $this->assertTrue(Hash::check('NewPass#456', $user->password));

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => '9876543210']);
    }

    public function test_phone_only_reset_rejects_invalid_token(): void
    {
        User::factory()->create([
            'status' => 'active',
            'phone' => '9876543210',
            'email' => null,
            'password' => Hash::make('OldPass#123'),
        ]);

        $response = $this->from(route('password.reset', ['token' => 'bogus']))
            ->post(route('password.store'), [
                'token' => 'bogus',
                'phone' => '9876543210',
                'password' => 'NewPass#456',
                'password_confirmation' => 'NewPass#456',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }
}