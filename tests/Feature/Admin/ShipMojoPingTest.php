<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Setting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesFulfilmentData;
use Tests\TestCase;

class ShipMojoPingTest extends TestCase
{
    use RefreshDatabase, CreatesFulfilmentData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actingAdminWithSettings(): Admin
    {
        $admin = Admin::factory()->create();
        $admin->givePermissionTo('manage-settings');
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_admin_with_manage_settings_can_call_ping(): void
    {
        $this->enableShipmojo();
        $this->actingAdminWithSettings();

        Http::fake([
            'shipping-api.com/app/api/v1/get-warehouses' => Http::response([
                'result' => '1',
                'data' => [
                    ['id' => '1', 'name' => 'Main Warehouse'],
                ],
            ], 200),
        ]);

        $response = $this->getJson(route('admin.shipmojo.ping'));

        $response->assertOk();
        $response->assertJson([
            'result' => '1',
            'message' => 'Connected to ShipMojo successfully!',
        ]);
    }

    public function test_ping_with_custom_keys_via_query_params(): void
    {
        $this->actingAdminWithSettings();

        Http::fake([
            'shipping-api.com/app/api/v1/get-warehouses' => Http::response([
                'result' => '1',
                'data' => [],
            ], 200),
        ]);

        $response = $this->getJson(route('admin.shipmojo.ping', [
            'public_key' => 'custom_pub_key',
            'private_key' => 'custom_priv_key',
        ]));

        $response->assertOk();
        $response->assertJson([
            'result' => '1',
        ]);
    }

    public function test_ping_returns_informative_error_when_keys_missing(): void
    {
        $this->actingAdminWithSettings();

        $response = $this->getJson(route('admin.shipmojo.ping'));

        $response->assertOk();
        $response->assertJson([
            'result' => '0',
            'message' => 'Please enter and save your ShipMojo Public Key and Private Key first.',
        ]);
    }
}
