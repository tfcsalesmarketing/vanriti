<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
