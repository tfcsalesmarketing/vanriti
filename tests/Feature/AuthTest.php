<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_login_page(): void
    {
        $response = $this->get(route('login'));
        $response->assertOk();
    }

    public function test_user_can_register_and_is_redirected_to_dashboard(): void
    {
        $response = $this->post(route('register.submit'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '9876543210',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('account.dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'status' => 'active',
        ]);

        $this->assertAuthenticatedAs(User::where('email', 'jane@example.com')->firstOrFail(), 'web');
    }

    public function test_registration_password_confirmation_mismatch_fails(): void
    {
        $response = $this->post(route('register.submit'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '9876543210',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
    }

    public function test_user_can_login_successfully(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $response = $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web');
        $response = $this->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest('web');
    }

    public function test_welcome_notification_is_sent_on_registration(): void
    {
        Notification::fake();

        $this->post(route('register.submit'), [
            'name' => 'Jane Doe',
            'email' => 'welcome-notify@example.com',
            'phone' => '9876543210',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('account.dashboard'));

        Notification::assertSentTo(
            User::where('email', 'welcome-notify@example.com')->firstOrFail(),
            WelcomeNotification::class
        );
    }

    public function test_registration_succeeds_when_welcome_email_fails_to_send(): void
    {
        Event::listen(MessageSending::class, fn () => throw new \RuntimeException('smtp unreachable'));
        Log::spy();

        $response = $this->post(route('register.submit'), [
            'name' => 'Jane Doe',
            'email' => 'mailfail@example.com',
            'phone' => '9876543210',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticated('web');
        $this->assertDatabaseHas('users', ['email' => 'mailfail@example.com']);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_registration_welcome_email_uses_branded_template(): void
    {
        $user = User::factory()->make([
            'name' => 'Aarav Mehta',
            'email' => 'welcome@example.com',
        ]);

        $notification = new WelcomeNotification;
        $mail = $notification->toMail($user);
        $html = $mail->render();

        $this->assertSame('Welcome to VANRITI', $mail->subject);
        $this->assertStringContainsString('Hi Aarav,', $html);
        $this->assertStringContainsString('Start Shopping', $html);
        $this->assertStringContainsString('welcome@example.com', $html);
        $this->assertStringContainsString('eduse-uploadedfiles.s3.ap-south-1.amazonaws.com', $html);
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->get(route('login'))
            ->assertRedirect(route('account.dashboard'));
    }

    public function test_authenticated_user_is_redirected_from_register(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->get(route('register'))
            ->assertRedirect(route('account.dashboard'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_protected_account_route(): void
    {
        $this->get(route('account.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_account_nav_shows_logout_button_for_authenticated_user(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->actingAs($user, 'web')
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee(route('logout'))
            ->assertSee('Log Out');
    }

    public function test_passwords_are_hashed(): void
    {
        User::create([
            'name' => 'Hash Test',
            'email' => 'hash@example.com',
            'password' => 'Secret#2026',
            'status' => 'active',
        ]);

        $user = User::where('email', 'hash@example.com')->firstOrFail();
        $this->assertNotSame('Secret#2026', $user->password);
        $this->assertTrue(Hash::check('Secret#2026', $user->password));
    }
}
