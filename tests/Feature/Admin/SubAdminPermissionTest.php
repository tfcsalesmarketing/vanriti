<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubAdminPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_whatsapp_live_chat_agent_can_only_access_inbox(): void
    {
        // 1. Create a WhatsApp Agent sub-admin
        $role = Role::firstOrCreate(
            ['slug' => 'whatsapp-agent'],
            ['name' => 'WhatsApp Agent', 'is_system' => false]
        );
        $role->permissions()->sync(
            Permission::whereIn('slug', ['view-dashboard', 'view-whatsapp-inbox', 'manage-whatsapp-inbox'])->pluck('id')
        );

        $agent = Admin::factory()->create(['status' => 'active', 'is_super_admin' => false]);
        $agent->roles()->attach($role);

        // 2. Can access dashboard and WhatsApp inbox
        $this->actingAs($agent, 'admin')->get(route('admin.dashboard'))->assertStatus(200);
        $this->actingAs($agent, 'admin')->get(route('admin.whatsapp.inbox'))->assertStatus(200);

        // 3. FORBIDDEN from accessing Products
        $this->actingAs($agent, 'admin')->get(route('admin.products.index'))->assertStatus(403);

        // 4. FORBIDDEN from accessing Orders
        $this->actingAs($agent, 'admin')->get(route('admin.orders.index'))->assertStatus(403);

        // 5. FORBIDDEN from accessing WhatsApp Campaigns
        $this->actingAs($agent, 'admin')->get(route('admin.whatsapp.campaigns.index'))->assertStatus(403);

        // 6. FORBIDDEN from accessing WhatsApp Bot Rules
        $this->actingAs($agent, 'admin')->get(route('admin.whatsapp.bot.index'))->assertStatus(403);

        // 7. FORBIDDEN from accessing Settings
        $this->actingAs($agent, 'admin')->get(route('admin.settings.index'))->assertStatus(403);
    }

    public function test_read_only_orders_admin_cannot_mutate_order_status(): void
    {
        // 1. Create an admin with only 'view-orders' permission
        $role = Role::create(['name' => 'Order Viewer', 'slug' => 'order-viewer', 'is_system' => false]);
        $role->permissions()->attach(Permission::where('slug', 'view-orders')->firstOrFail());

        $viewer = Admin::factory()->create(['status' => 'active', 'is_super_admin' => false]);
        $viewer->roles()->attach($role);

        // 2. Can view orders index
        $this->actingAs($viewer, 'admin')->get(route('admin.orders.index'))->assertStatus(200);

        // 3. Attempting to update order status returns 403 Forbidden
        $customer = \App\Models\User::factory()->create([
            'phone' => '9876543210',
            'name' => 'John Doe',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-111111',
            'user_id' => $customer->id,
            'billing_name' => 'John Doe',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => '123 MG Road',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560001',
            'billing_country' => 'India',
            'shipping_name' => 'John Doe',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '123 MG Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560001',
            'shipping_country' => 'India',
            'subtotal' => 1299.00,
            'grand_total' => 1299.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $this->actingAs($viewer, 'admin')
            ->post(route('admin.orders.status', $order->id), ['order_status' => 'confirmed'])
            ->assertStatus(403);
    }

    public function test_manage_orders_admin_can_mutate_order_status(): void
    {
        // 1. Create an admin with 'manage-orders' permission
        $role = Role::create(['name' => 'Order Manager', 'slug' => 'order-manager', 'is_system' => false]);
        $role->permissions()->attach(Permission::where('slug', 'manage-orders')->firstOrFail());

        $manager = Admin::factory()->create(['status' => 'active', 'is_super_admin' => false]);
        $manager->roles()->attach($role);

        // 2. Can view and update order status
        $customer = \App\Models\User::factory()->create([
            'phone' => '9876543211',
            'name' => 'Jane Doe',
        ]);
        $order = Order::create([
            'order_number' => 'ORD-222222',
            'user_id' => $customer->id,
            'billing_name' => 'Jane Doe',
            'billing_mobile' => '9876543211',
            'billing_address_line1' => '123 MG Road',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560001',
            'billing_country' => 'India',
            'shipping_name' => 'Jane Doe',
            'shipping_mobile' => '9876543211',
            'shipping_address_line1' => '123 MG Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560001',
            'shipping_country' => 'India',
            'subtotal' => 1299.00,
            'grand_total' => 1299.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $this->actingAs($manager, 'admin')->get(route('admin.orders.index'))->assertStatus(200);

        $response = $this->actingAs($manager, 'admin')
            ->post(route('admin.orders.status', $order->id), ['order_status' => 'confirmed']);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('confirmed', $order->fresh()->order_status);
    }
}
