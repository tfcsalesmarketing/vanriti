<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The session, cache and queue stores are all database-backed, so a stopped
 * MySQL server used to surface as a raw 500 on the password/OTP routes: the
 * failure happened inside StartSession or the throttle middleware, before any
 * controller could attach a friendly message (and a redirect with a flash was
 * impossible because the session itself was the thing that was broken).
 */
class DatabaseDownTest extends TestCase
{
    use RefreshDatabase;

    protected string $originalDefault;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDefault = (string) config('database.default');

        $this->enableHealthCheck();
    }

    protected function tearDown(): void
    {
        DB::purge('broken');
        config(['database.default' => $this->originalDefault]);

        $this->disableHealthCheck();

        parent::tearDown();
    }

    public function test_the_forgot_password_page_returns_service_unavailable_when_the_database_is_down(): void
    {
        $this->breakDatabase();

        $this->get(route('password.request'))
            ->assertStatus(503)
            ->assertSee('Service Unavailable')
            ->assertDontSee('Whoops');
    }

    public function test_a_forgot_password_submission_returns_service_unavailable_when_the_database_is_down(): void
    {
        $this->breakDatabase();

        $this->post(route('password.email'), ['identifier' => 'someone@example.com'])
            ->assertStatus(503)
            ->assertSee('Service Unavailable');
    }

    public function test_a_query_failure_on_an_otp_route_renders_the_friendly_page_instead_of_a_500(): void
    {
        Route::middleware('web')->post('forgot-password/__broken-db', function () {
            throw new QueryException(
                'mysql',
                'select * from users where email = ?',
                [],
                new \PDOException('SQLSTATE[HY000] [2002] Connection refused'),
            );
        });

        $this->post('/forgot-password/__broken-db')
            ->assertStatus(503)
            ->assertSee('Service Unavailable')
            ->assertDontSee('select * from users');
    }

    public function test_the_health_check_leaves_working_pages_untouched(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('vrForgotForm');
    }

    protected function breakDatabase(): void
    {
        config([
            'database.connections.broken' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => '3307',
                'database' => 'vanriti',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ],
            'database.default' => 'broken',
        ]);

        DB::purge('broken');
    }

    protected function enableHealthCheck(): void
    {
        $_ENV['DB_HEALTH_CHECK'] = 'true';
        $_SERVER['DB_HEALTH_CHECK'] = 'true';
        putenv('DB_HEALTH_CHECK=true');
    }

    protected function disableHealthCheck(): void
    {
        unset($_ENV['DB_HEALTH_CHECK'], $_SERVER['DB_HEALTH_CHECK']);
        putenv('DB_HEALTH_CHECK');
    }
}
