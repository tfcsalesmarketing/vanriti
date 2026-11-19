<?php

namespace Tests\Feature;

use App\Console\Commands\StorefrontDownCommand;
use App\Console\Commands\StorefrontUpCommand;
use App\Http\Middleware\StorefrontMaintenanceMode;
use App\Models\Admin;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class StorefrontMaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);

        // Ensure maintenance file is cleaned up before and after each test
        $this->clearMaintenanceFile();
    }

    protected function tearDown(): void
    {
        $this->clearMaintenanceFile();
        parent::tearDown();
    }

    protected function clearMaintenanceFile(): void
    {
        $file = StorefrontMaintenanceMode::maintenanceFilePath();
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    public function test_storefront_loads_normally_when_maintenance_is_off(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertDontSee("We're Brewing Fresh Formulations");
    }

    public function test_admin_routes_are_accessible_when_maintenance_is_off(): void
    {
        $response = $this->get(route('admin.login'));
        $response->assertOk();
    }

    public function test_storefront_shows_branded_maintenance_page_when_down(): void
    {
        Artisan::call('storefront:down');

        $response = $this->get('/');
        $response->assertStatus(503);
        $response->assertSee("We're Brewing Fresh Formulations", false);
        $response->assertSee('Buy on', false);
        $response->assertSee('Amazon', false);
        $response->assertSee('Flipkart', false);
        $response->assertSee('Chat on WhatsApp', false);
        $response->assertHeader('Retry-After', '300');

        // Shop route also returns 503
        $shopResponse = $this->get('/shop');
        $shopResponse->assertStatus(503);
    }

    public function test_admin_panel_remains_accessible_when_storefront_is_in_maintenance(): void
    {
        Artisan::call('storefront:down');

        // Admin login page must remain 200 OK
        $response = $this->get(route('admin.login'));
        $response->assertOk();
        $response->assertDontSee("We're Brewing Fresh Formulations");
    }

    public function test_admin_user_can_bypass_maintenance_mode(): void
    {
        Artisan::call('storefront:down');

        $admin = Admin::factory()->create([
            'status' => 'active',
        ]);
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        // Authenticated admin should see live storefront (200 OK)
        $response = $this->actingAs($admin, 'admin')->get('/');
        $response->assertOk();
        $response->assertDontSee("We're Brewing Fresh Formulations");
    }

    public function test_preview_parameter_sets_bypass_cookie(): void
    {
        Artisan::call('storefront:down');

        // Visiting with ?preview=vanriti redirects and sets the bypass cookie
        $response = $this->get('/?preview=vanriti');
        $response->assertRedirect('http://localhost');
        $response->assertCookie(StorefrontMaintenanceMode::BYPASS_COOKIE, '1');

        // Subsequent request with the cookie loads the live site
        $cookieResponse = $this->withCookie(StorefrontMaintenanceMode::BYPASS_COOKIE, '1')->get('/');
        $cookieResponse->assertOk();
    }

    public function test_preview_exit_clears_bypass_cookie(): void
    {
        Artisan::call('storefront:down');

        $response = $this->withCookie(StorefrontMaintenanceMode::BYPASS_COOKIE, '1')->get('/?preview=exit');
        $response->assertRedirect('http://localhost');
        $response->assertCookieExpired(StorefrontMaintenanceMode::BYPASS_COOKIE);
    }

    public function test_storefront_up_command_restores_normal_site(): void
    {
        Artisan::call('storefront:down');
        $this->get('/')->assertStatus(503);

        Artisan::call('storefront:up');
        $this->get('/')->assertOk();
        $this->get('/')->assertDontSee("We're Brewing Fresh Formulations");
    }
}
