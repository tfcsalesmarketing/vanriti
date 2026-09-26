<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLiveValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_password_visibility_toggle(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('password-toggle-btn')
            ->assertSee('ri-eye-line')
            ->assertSee('vrLoginForm');
    }

    public function test_register_page_renders_password_visibility_toggles(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('password-toggle-btn', false)
            ->assertSee('vrRegisterForm');
    }

    public function test_login_empty_email_fails_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'login',
            'login'   => '',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['valid' => false]);
        $this->assertArrayHasKey('login', $response->json('errors'));
    }

    public function test_login_valid_fields_pass_live(): void
    {
        User::factory()->create(['email' => 'user@example.com', 'password' => 'secret123']);

        $response = $this->postJson(route('auth.validate'), [
            'context' => 'login',
            'email' => 'user@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()->assertJson(['valid' => true]);
    }

    public function test_login_unknown_identifier_fails_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'login',
            'login' => 'nobody@example.com',
            'password' => 'secret123',
        ]);

        // The failure is reported under a single generic key so the endpoint
        // cannot be used to distinguish a missing account from a wrong password.
        $response->assertStatus(422);
        $this->assertArrayHasKey('password', $response->json('errors'));
    }

    public function test_login_wrong_password_fails_live(): void
    {
        User::factory()->create(['email' => 'user@example.com', 'password' => 'secret123']);

        $response = $this->postJson(route('auth.validate'), [
            'context' => 'login',
            'login' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('password', $response->json('errors'));
    }

    public function test_login_non_email_identifier_passes_live(): void
    {
        // The 'login' field accepts any string (email OR mobile number).
        // A non-email-formatted value like '9876543210' or 'not-an-email'
        // is intentionally valid at the format level; credential checking
        // only happens on actual login submit, not live validation.
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'login',
            'login'   => 'not-an-email',
        ]);

        $response->assertOk();
    }

    public function test_register_empty_name_fails_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'register',
            'name' => '',
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('name', $response->json('errors'));
    }

    public function test_register_empty_email_passes_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'register',
            'email' => '',
        ]);

        $response->assertOk()->assertJson(['valid' => true]);
    }

    public function test_register_duplicate_email_does_not_leak_live(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        // Live validation must not reveal which emails are already registered
        // (enumeration). Uniqueness is only enforced by the real register POST.
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'register',
            'email' => 'taken@example.com',
        ]);

        $response->assertOk()->assertJson(['valid' => true]);
    }

    public function test_register_duplicate_email_rejected_on_submit(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->from(route('register'))->post(route('register.submit'), [
            'name' => 'New User',
            'email' => 'taken@example.com',
            'phone' => '9876543210',
            'password' => 'Password#123',
            'password_confirmation' => 'Password#123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_register_invalid_phone_fails_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'register',
            'phone' => '123456',
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('phone', $response->json('errors'));
    }

    public function test_register_valid_phone_with_prefix_passes_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'register',
            'phone' => '+91 98765 43210',
        ]);

        $response->assertOk()->assertJson(['valid' => true]);
    }

    public function test_register_short_password_fails_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'register',
            'password' => 'abc',
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('password', $response->json('errors'));
    }

    public function test_register_password_without_number_fails_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'register',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('password', $response->json('errors'));
    }

    public function test_register_password_without_letter_fails_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'register',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('password', $response->json('errors'));
    }

    public function test_register_confirmation_mismatch_fails_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'register',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('password_confirmation', $response->json('errors'));
    }

    public function test_register_confirmation_match_passes_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'register',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertOk()->assertJson(['valid' => true]);
    }

    public function test_forgot_page_renders_live_form(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('vrForgotForm');
    }

    public function test_forgot_invalid_email_fails_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'forgot',
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('email', $response->json('errors'));
    }

    public function test_forgot_valid_email_passes_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'forgot',
            'email' => 'me@example.com',
        ]);

        $response->assertOk()->assertJson(['valid' => true]);
    }

    public function test_reset_short_password_fails_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'reset',
            'password' => 'abc',
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('password', $response->json('errors'));
    }

    public function test_reset_confirmation_mismatch_fails_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'reset',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('password_confirmation', $response->json('errors'));
    }

    public function test_reset_valid_fields_pass_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'reset',
            'email' => 'reset@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertOk()->assertJson(['valid' => true]);
    }
}
