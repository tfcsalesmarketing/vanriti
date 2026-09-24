<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ForgotPasswordFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_forgot_password_page(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('vrForgotForm');
    }

    public function test_sending_reset_link_creates_token_and_notifies_with_branded_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'resetme@example.com']);

        $response = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'resetme@example.com']);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'resetme@example.com']);

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class
        );
    }

    public function test_forgot_password_for_existing_email_succeeds_when_reset_email_fails_to_send(): void
    {
        Event::listen(MessageSending::class, fn () => throw new \RuntimeException('smtp unreachable'));

        $user = User::factory()->create(['email' => 'mailfail-reset@example.com']);

        $response = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'mailfail-reset@example.com']);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('success');
        $response->assertSessionMissing('errors');
    }

    public function test_forgot_password_accepts_five_requests_then_rate_limits(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->from(route('password.request'))
                ->post(route('password.email'), ['email' => "rate$i@example.com"]);

            $response->assertSessionHas('success');
        }

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'rate-sixth@example.com'])
            ->assertStatus(429);
    }

    public function test_forgot_password_for_unknown_email_does_not_reveal_existence(): void
    {
        $response = $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'nobody@example.com']);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('success');
        $response->assertSessionMissing('errors');
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'nobody@example.com']);
    }

    public function test_reset_email_link_uses_the_request_host(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'name' => 'Aarav Mehta',
            'email' => 'hostlink@example.com',
        ]);

        $this->post(route('password.email'), ['email' => 'hostlink@example.com'])
            ->assertSessionHas('success');

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use ($user): bool {
                $html = $notification->toMail($user)->render();
                $expectedUrl = url(route('password.reset', [
                    'token' => $notification->token,
                    'email' => $user->email,
                ], false));

                return str_contains($html, $expectedUrl);
            }
        );
    }

    public function test_reset_email_uses_premium_branded_template(): void
    {
        $user = User::factory()->create([
            'name' => 'Aarav Mehta',
            'email' => 'brand@example.com',
        ]);

        $notification = new ResetPasswordNotification('top-secret-token');
        $mail = $notification->toMail($user);
        $html = $mail->render();

        $this->assertSame('Reset Your VANRITI Password', $mail->subject);
        $this->assertStringContainsString('Hi Aarav,', $html);
        $this->assertStringContainsString('Reset My Password', $html);
        $this->assertStringContainsString('top-secret-token', $html);
        $this->assertStringContainsString('expires in 60 minutes', $html);
        $this->assertStringContainsString('brand@example.com', $html);
        $this->assertStringContainsString('eduse-uploadedfiles.s3.ap-south-1.amazonaws.com', $html);
    }

    public function test_reset_page_renders_and_accepts_valid_token(): void
    {
        $user = User::factory()->create([
            'name' => 'Aarav Mehta',
            'email' => 'resetflow@example.com',
            'password' => 'OldPass#123',
        ]);

        $token = Password::broker('users')->createToken($user);

        $this->get(route('password.reset', ['token' => $token]))
            ->assertOk()
            ->assertSee('vrResetForm')
            ->assertSee('password-toggle-btn');

        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'resetflow@example.com',
            'password' => 'NewPass#456',
            'password_confirmation' => 'NewPass#456',
        ]);

        $response->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($user, 'web');

        $user->refresh();
        $this->assertTrue(Hash::check('NewPass#456', $user->password));

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'resetflow@example.com']);
    }

    public function test_invalid_reset_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'invalidtoken@example.com']);

        $response = $this->from(route('password.reset', ['token' => 'bogus']))
            ->post(route('password.store'), [
                'token' => 'bogus',
                'email' => 'invalidtoken@example.com',
                'password' => 'NewPass#456',
                'password_confirmation' => 'NewPass#456',
            ]);

        $response->assertRedirect(route('password.reset', ['token' => 'bogus']));
        $response->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }
}
