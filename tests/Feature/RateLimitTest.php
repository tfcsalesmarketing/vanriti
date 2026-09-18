<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_allows_five_attempts_then_returns_429(): void
    {
        User::factory()->create(['email' => 'rate-target@example.com', 'password' => 'Secret#123']);

        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.10'])
                ->post(route('login.submit'), [
                    'email' => 'rate-target@example.com',
                    'password' => 'wrong-password',
                ])
                ->assertSessionHasErrors('email');
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.10'])
            ->post(route('login.submit'), [
                'email' => 'rate-target@example.com',
                'password' => 'wrong-password',
            ])
            ->assertStatus(429);
    }

    public function test_register_allows_five_attempts_then_returns_429(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.20'])
                ->post(route('register.submit'), [
                    'name' => 'Rate User '.$i,
                    'email' => "rate-register-$i@example.com",
                    'phone' => '987654321'.$i,
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ])
                ->assertRedirect(route('account.dashboard'));
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.20'])
            ->post(route('register.submit'), [
                'name' => 'Rate User X',
                'email' => 'rate-register-x@example.com',
                'phone' => '9876543210',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertStatus(429);
    }

    public function test_reset_password_allows_five_attempts_then_returns_429(): void
    {
        $payload = [
            'token' => 'bogus',
            'email' => 'rate-reset@example.com',
            'password' => 'NewPass#456',
            'password_confirmation' => 'NewPass#456',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.30'])
                ->from(route('password.reset', ['token' => 'bogus']))
                ->post(route('password.store'), $payload)
                ->assertSessionHasErrors('email');
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.30'])
            ->post(route('password.store'), $payload)
            ->assertStatus(429);
    }

    public function test_field_validation_allows_sixty_attempts_then_returns_429(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.40'])
                ->postJson(route('auth.validate'), ['email' => 'rate-validate@example.com', 'context' => 'login']);

            if ($response->status() === 429) {
                break;
            }
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.40'])
            ->postJson(route('auth.validate'), ['email' => 'rate-validate@example.com', 'context' => 'login'])
            ->assertStatus(429);
    }
}
