<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\OtpEmailNotification;
use App\Services\EmailOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The email-or-mobile "forgot password" flow:
 * identifier -> 6-digit code (WhatsApp for a number, email for an address)
 * -> new password -> confirmation. The customer is never signed in.
 */
class ForgotPasswordOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function enableWhatsapp(): void
    {
        foreach ([
            ['key' => 'whatsapp_otp_enabled', 'value' => '1', 'group' => 'whatsapp', 'label' => 'Enable WhatsApp OTP', 'type' => 'boolean'],
            ['key' => 'whatsapp_phone_number_id', 'value' => '9988776655', 'group' => 'whatsapp', 'label' => 'Phone Number ID', 'type' => 'text'],
            ['key' => 'whatsapp_template_name', 'value' => 'vanriti_otp', 'group' => 'whatsapp', 'label' => 'OTP Template', 'type' => 'text'],
        ] as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        Setting::updateOrCreate(['key' => 'whatsapp_access_token'], [
            'group' => 'whatsapp',
            'value' => Crypt::encryptString('fake-test-token'),
            'label' => 'WhatsApp Access Token',
            'type' => 'password',
        ]);
    }

    public function test_forgot_page_accepts_an_email_or_a_mobile_number(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('vrForgotForm')
            ->assertSee('name="identifier"', false)
            ->assertSee('Email or Mobile Number');
    }

    public function test_email_identifier_sends_a_code_by_email_and_moves_to_verification(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'otp-email@example.com']);

        $response = $this->from(route('password.request'))
            ->post(route('password.email'), ['identifier' => 'OTP-Email@Example.com']);

        $response->assertRedirect(route('password.otp.verify'));
        $response->assertSessionHas('success');

        $this->assertSame('otp-email@example.com', session('password_reset.identifier'));
        $this->assertSame('email', session('password_reset.channel'));

        Notification::assertSentOnDemand(
            OtpEmailNotification::class,
            fn (OtpEmailNotification $n) => strlen($n->code) === 6
        );
        $this->assertDatabaseHas('otp_codes', [
            'email' => 'otp-email@example.com',
            'channel' => 'email',
            'purpose' => 'reset_password',
            'used_at' => null,
        ]);

        $this->get(route('password.otp.verify'))
            ->assertOk()
            ->assertSee('Enter Verification Code')
            ->assertSee('your email address');
    }

    public function test_mobile_identifier_sends_a_code_over_whatsapp(): void
    {
        $this->enableWhatsapp();
        Notification::fake();

        User::factory()->create(['phone' => '9876543210', 'email' => 'has-email@example.com']);

        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.abc']]], 200)]);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['identifier' => '98765 43210'])
            ->assertRedirect(route('password.otp.verify'));

        $this->assertSame('9876543210', session('password_reset.identifier'));
        $this->assertSame('whatsapp', session('password_reset.channel'));

        $sentCode = Http::recorded()[0][0]['template']['components'][0]['parameters'][0]['text'] ?? null;
        $this->assertNotNull($sentCode);

        // The inbox is untouched when WhatsApp is the channel.
        Notification::assertNothingSent();
    }

    public function test_mobile_identifier_falls_back_to_email_when_whatsapp_is_disabled(): void
    {
        Notification::fake();

        User::factory()->create(['phone' => '9876543210', 'email' => 'fallback@example.com']);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['identifier' => '9876543210'])
            ->assertRedirect(route('password.otp.verify'));

        $this->assertSame('fallback@example.com', session('password_reset.identifier'));
        $this->assertSame('email', session('password_reset.channel'));

        Notification::assertSentOnDemand(OtpEmailNotification::class);
    }

    public function test_malformed_identifier_is_rejected(): void
    {
        $this->from(route('password.request'))
            ->post(route('password.email'), ['identifier' => '12345'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('identifier');

        $this->assertNull(session('password_reset.identifier'));
    }

    public function test_unknown_identifier_is_answered_identically_and_sends_nothing(): void
    {
        Notification::fake();

        $this->from(route('password.request'))
            ->post(route('password.email'), ['identifier' => 'nobody@example.com'])
            ->assertRedirect(route('password.otp.verify'))
            ->assertSessionHas('success');

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('otp_codes', ['email' => 'nobody@example.com']);
    }

    public function test_verification_step_is_rejected_without_a_pending_request(): void
    {
        $this->get(route('password.otp.verify'))->assertRedirect(route('password.request'));
        $this->post(route('password.otp.check'), ['code' => '123456'])->assertRedirect(route('password.request'));
    }

    public function test_wrong_code_is_rejected_with_a_generic_message(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'wrongcode@example.com']);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['identifier' => 'wrongcode@example.com']);

        $this->from(route('password.otp.verify'))
            ->post(route('password.otp.check'), ['code' => '000000'])
            ->assertRedirect(route('password.otp.verify'))
            ->assertSessionHasErrors('code');

        $this->assertSame(
            ['That code is not valid or has expired. Please request a new one.'],
            session('errors')->get('code')
        );

        $this->assertNull(session('password_reset.user_id'));
    }

    public function test_customer_can_set_a_new_password_and_is_not_logged_in(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'complete@example.com',
            'password' => Hash::make('OldPass#123'),
        ]);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['identifier' => 'complete@example.com']);

        $code = $this->capturedEmailCode();

        $this->post(route('password.otp.check'), ['code' => $code])
            ->assertRedirect(route('password.new'));

        $this->get(route('password.new'))
            ->assertOk()
            ->assertSee('Set New Password')
            ->assertSee('password-toggle-btn');

        $response = $this->from(route('password.new'))->post(route('password.update'), [
            'password' => 'NewPass#456',
            'password_confirmation' => 'NewPass#456',
        ]);

        $response->assertRedirect(route('password.complete'));

        $this->assertGuest('web');
        $this->assertTrue(Hash::check('NewPass#456', $user->fresh()->password));

        // The page can only be reached right after a successful change.
        $this->get(route('password.complete'))
            ->assertOk()
            ->assertSee('Password Changed')
            ->assertSee(route('login'));

        $this->get(route('password.complete'))->assertRedirect(route('login'));
    }

    public function test_new_password_page_requires_a_verified_code(): void
    {
        $this->get(route('password.new'))->assertRedirect(route('password.request'));

        $this->from(route('password.new'))
            ->post(route('password.update'), [
                'password' => 'NewPass#456',
                'password_confirmation' => 'NewPass#456',
            ])
            ->assertRedirect(route('password.request'));
    }

    public function test_mismatched_confirmation_is_rejected(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'mismatch@example.com', 'password' => Hash::make('OldPass#123')]);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['identifier' => 'mismatch@example.com']);
        $this->post(route('password.otp.check'), ['code' => $this->capturedEmailCode()]);

        $this->from(route('password.new'))
            ->post(route('password.update'), [
                'password' => 'NewPass#456',
                'password_confirmation' => 'Different#789',
            ])
            ->assertSessionHasErrors('password_confirmation');

        $this->assertTrue(Hash::check('OldPass#123', $user->fresh()->password));
    }

    public function test_weak_new_password_is_rejected(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'weak@example.com', 'password' => Hash::make('OldPass#123')]);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['identifier' => 'weak@example.com']);
        $this->post(route('password.otp.check'), ['code' => $this->capturedEmailCode()]);

        $this->from(route('password.new'))
            ->post(route('password.update'), [
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('OldPass#123', User::query()->where('email', 'weak@example.com')->firstOrFail()->password));
    }

    public function test_a_used_code_cannot_be_replayed(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'replay@example.com', 'password' => Hash::make('OldPass#123')]);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['identifier' => 'replay@example.com']);

        $code = $this->capturedEmailCode();

        $this->post(route('password.otp.check'), ['code' => $code])->assertRedirect(route('password.new'));

        $this->from(route('password.otp.verify'))
            ->post(route('password.otp.check'), ['code' => $code])
            ->assertRedirect(route('password.otp.verify'))
            ->assertSessionHasErrors('code');
    }

    public function test_changing_the_password_burns_any_other_live_reset_code(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'burn@example.com', 'password' => Hash::make('OldPass#123')]);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['identifier' => 'burn@example.com']);

        $this->post(route('password.otp.check'), ['code' => $this->capturedEmailCode()])
            ->assertRedirect(route('password.new'));

        // A second code reaches the same account from another device while this
        // browser already sits on the new-password step.
        $this->travel(61)->seconds();
        app(EmailOtpService::class)->sendOtp('burn@example.com', 'reset_password');
        $spare = $this->capturedEmailCode();
        $this->assertNotNull(
            OtpCode::query()->where('email', 'burn@example.com')->whereNull('used_at')->latest('id')->first()
        );

        $this->from(route('password.new'))->post(route('password.update'), [
            'password' => 'NewPass#456',
            'password_confirmation' => 'NewPass#456',
        ])->assertRedirect(route('password.complete'));

        // Every reset code for the account is burnt, so the spare code still
        // sitting in the other device's inbox cannot be used to get back in.
        $this->assertNull(
            OtpCode::query()->where('email', 'burn@example.com')->whereNull('used_at')->latest('id')->first()
        );

        $this->assertSame(
            '0',
            app(EmailOtpService::class)->verifyOtp('burn@example.com', $spare, 'reset_password')['result']
        );
    }

    public function test_suspended_account_cannot_finish_a_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => Hash::make('OldPass#123'),
        ]);

        $this->from(route('password.request'))
            ->post(route('password.email'), ['identifier' => 'suspended@example.com']);
        $this->post(route('password.otp.check'), ['code' => $this->capturedEmailCode()])
            ->assertRedirect(route('password.new'));

        $user->forceFill(['status' => 'suspended'])->save();

        $this->from(route('password.new'))->post(route('password.update'), [
            'password' => 'NewPass#456',
            'password_confirmation' => 'NewPass#456',
        ])->assertRedirect(route('password.request'));

        $this->assertTrue(Hash::check('OldPass#123', $user->fresh()->password));
    }

    public function test_forgot_password_accepts_five_requests_then_rate_limits(): void
    {
        Notification::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->from(route('password.request'))
                ->post(route('password.email'), ['identifier' => "rate{$i}@example.com"])
                ->assertRedirect(route('password.otp.verify'));
        }

        $this->from(route('password.request'))
            ->post(route('password.email'), ['identifier' => 'rate-sixth@example.com'])
            ->assertStatus(429);
    }

    /**
     * Pull the plain 6-digit code off the last faked on-demand notification;
     * the stored value is only ever a hash.
     */
    protected function capturedEmailCode(): string
    {
        $code = null;

        Notification::assertSentOnDemand(
            OtpEmailNotification::class,
            function (OtpEmailNotification $notification) use (&$code): bool {
                $code = $notification->code;

                return true;
            }
        );

        $this->assertNotNull($code, 'No email OTP notification was dispatched.');

        return $code;
    }
}
