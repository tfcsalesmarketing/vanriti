<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Role;
use App\Models\Setting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    private function superAdmin(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        return $admin;
    }

    public function test_admin_settings_index_renders_with_tabs(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertSee('Store Configuration & Integrations');
        $response->assertSee('Store Profile & SEO');
        $response->assertSee('Orders, Shipping & Tax');
        $response->assertSee('Payments & Razorpay');
        $response->assertSee('ShipMojo Logistics');
        $response->assertSee('WhatsApp Meta API');
    }

    public function test_admin_settings_update_persists_settings(): void
    {
        $admin = $this->superAdmin();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.update'), [
                'store_name' => 'Vanriti Ayurveda',
                'min_order_amount' => '299',
                'return_window_days' => '7',
            ]);

        $response->assertRedirect(route('admin.settings.index'));
        $this->assertSame('Vanriti Ayurveda', setting('store_name'));
        $this->assertSame('299', setting('min_order_amount'));
        $this->assertSame('7', setting('return_window_days'));
    }
}
