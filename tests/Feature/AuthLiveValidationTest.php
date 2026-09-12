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
            'email' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['valid' => false]);
        $this->assertArrayHasKey('email', $response->json('errors'));
    }

    public function test_login_valid_fields_pass_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'login',
            'email' => 'user@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()->assertJson(['valid' => true]);
    }

    public function test_login_invalid_email_fails_live(): void
    {
        $response = $this->postJson(route('auth.validate'), [
            'context' => 'login',
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('email', $response->json('errors'));
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

    public function test_register_duplicate_email_fails_live(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson(route('auth.validate'), [
            'context' => 'register',
            'email' => 'taken@example.com',
        ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('email', $response->json('errors'));
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
