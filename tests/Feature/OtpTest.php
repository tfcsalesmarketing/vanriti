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

    public function test_register_otp_send_rejects_already_registered_phone(): void
    {
        User::factory()->create(['status' => 'active', 'phone' => '9876543210']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.abc']]], 200)]);

        $response = $this->postJson('/otp/send', [
            'phone' => '9876543210',
            'purpose' => 'register',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'result' => '0',
            'message' => 'This mobile number is already registered.',
        ]);

        $this->assertDatabaseMissing('otp_codes', [
            'phone' => '9876543210',
            'purpose' => 'register',
        ]);

        Http::assertNothingSent();
    }

    public function test_register_otp_send_rejects_duplicate_across_phone_spellings(): void
    {
        User::factory()->create(['status' => 'active', 'phone' => '919876543210']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.abc']]], 200)]);

        $response = $this->postJson('/otp/send', [
            'phone' => '9876543210',
            'purpose' => 'register',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'result' => '0',
            'message' => 'This mobile number is already registered.',
        ]);

        $this->assertDatabaseMissing('otp_codes', [
            'phone' => '9876543210',
            'purpose' => 'register',
        ]);

        Http::assertNothingSent();
    }

    public function test_register_otp_send_still_succeeds_for_available_phone(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.abc']]], 200)]);

        $response = $this->postJson('/otp/send', [
            'phone' => '9876543210',
            'purpose' => 'register',
        ]);

        $response->assertOk();
        $response->assertJson(['result' => '1']);

        $this->assertDatabaseHas('otp_codes', [
            'phone' => '9876543210',
            'purpose' => 'register',
            'channel' => 'whatsapp',
        ]);

        Http::assertSentCount(1);
    }

    public function test_register_otp_send_rejects_duplicate_email(): void
    {
        User::factory()->create(['status' => 'active', 'phone' => '9123456789', 'email' => 'john@example.com']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.abc']]], 200)]);

        $response = $this->postJson('/otp/send', [
            'phone' => '9876543210',
            'email' => 'john@example.com',
            'purpose' => 'register',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'result' => '0',
            'message' => 'This email is already registered.',
            'errors' => ['email' => ['This email is already registered.']],
        ]);

        $this->assertDatabaseMissing('otp_codes', [
            'phone' => '9876543210',
            'purpose' => 'register',
        ]);

        Http::assertNothingSent();
    }

    public function test_register_otp_send_rejects_duplicate_email_case_insensitive(): void
    {
        User::factory()->create(['status' => 'active', 'phone' => '9123456789', 'email' => 'Jessica.Rodriguez@Example.COM']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.abc']]], 200)]);

        $response = $this->postJson('/otp/send', [
            'phone' => '9876543210',
            'email' => '  jessica.rodriguez@example.com  ',
            'purpose' => 'register',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'result' => '0',
            'message' => 'This email is already registered.',
            'errors' => ['email' => ['This email is already registered.']],
        ]);

        Http::assertNothingSent();
    }

    public function test_register_otp_send_succeeds_with_available_email(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.abc']]], 200)]);

        $response = $this->postJson('/otp/send', [
            'phone' => '9876543210',
            'email' => 'new@example.com',
            'purpose' => 'register',
        ]);

        $response->assertOk();
        $response->assertJson(['result' => '1']);

        $this->assertDatabaseHas('otp_codes', [
            'phone' => '9876543210',
            'purpose' => 'register',
            'channel' => 'whatsapp',
        ]);

        Http::assertSentCount(1);
    }

    public function test_register_otp_send_reports_phone_duplicate_first(): void
    {
        User::factory()->create(['status' => 'active', 'phone' => '9876543210', 'email' => 'taken@example.com']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.abc']]], 200)]);

        $response = $this->postJson('/otp/send', [
            'phone' => '9876543210',
            'email' => 'taken@example.com',
            'purpose' => 'register',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'result' => '0',
            'message' => 'This mobile number is already registered.',
            'errors' => ['phone' => ['This mobile number is already registered.']],
        ]);

        Http::assertNothingSent();
    }

    public function test_login_otp_send_still_hides_unknown_numbers(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.abc']]], 200)]);

        $response = $this->postJson('/otp/send', [
            'phone' => '9876543210',
            'purpose' => 'login',
        ]);

        $response->assertStatus(202);
        $response->assertJson(['result' => '1']);

        $this->assertDatabaseMissing('otp_codes', [
            'phone' => '9876543210',
            'purpose' => 'login',
        ]);

        Http::assertNothingSent();
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

    public function test_fast_checkout_sends_otp_to_unregistered_number_and_auto_creates_user(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.fc123']]], 200)]);

        // 1. New phone number requests fast_checkout OTP
        $sendResponse = $this->postJson('/otp/send', [
            'phone' => '9812345678',
            'purpose' => 'fast_checkout',
        ]);

        $sendResponse->assertOk();
        $sendResponse->assertJson(['result' => '1']);

        $this->assertDatabaseHas('otp_codes', [
            'phone' => '9812345678',
            'purpose' => 'fast_checkout',
            'channel' => 'whatsapp',
        ]);

        $recorded = Http::recorded();
        $sentCode = $recorded[0][0]['template']['components'][0]['parameters'][0]['text'] ?? null;
        $this->assertNotNull($sentCode);

        // 2. Verifying fast_checkout OTP auto-provisions user, logs them in, and returns redirect to checkout
        $verifyResponse = $this->postJson('/otp/verify', [
            'phone' => '9812345678',
            'code' => $sentCode,
            'purpose' => 'fast_checkout',
        ]);

        $verifyResponse->assertOk();
        $verifyResponse->assertJson([
            'result' => '1',
            'redirect' => route('checkout.index'),
        ]);

        $this->assertDatabaseHas('users', [
            'phone' => '9812345678',
            'status' => 'active',
        ]);

        $createdUser = User::where('phone', '9812345678')->first();
        $this->assertNotNull($createdUser->phone_verified_at);
        $this->assertAuthenticatedAs($createdUser);
    }

    public function test_fast_checkout_logs_in_existing_user(): void
    {
        $existing = User::factory()->create([
            'name' => 'Existing Customer',
            'phone' => '9876500000',
            'status' => 'active',
        ]);

        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.fc456']]], 200)]);

        $this->postJson('/otp/send', [
            'phone' => '9876500000',
            'purpose' => 'fast_checkout',
        ]);

        $recorded = Http::recorded();
        $sentCode = $recorded[0][0]['template']['components'][0]['parameters'][0]['text'] ?? null;

        $verifyResponse = $this->postJson('/otp/verify', [
            'phone' => '9876500000',
            'code' => $sentCode,
            'purpose' => 'fast_checkout',
        ]);

        $verifyResponse->assertOk();
        $verifyResponse->assertJson([
            'result' => '1',
            'redirect' => route('checkout.index'),
            'needs_name' => false,
        ]);

        $this->assertAuthenticatedAs($existing);
    }

    public function test_save_name_updates_authenticated_user_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Customer 5678',
            'phone' => '9812345678',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson('/otp/save-name', [
            'name' => 'Ananya Roy',
        ]);

        $response->assertOk();
        $response->assertJson([
            'result' => '1',
            'name' => 'Ananya Roy',
        ]);

        $this->assertEquals('Ananya Roy', $user->fresh()->name);
    }
}
