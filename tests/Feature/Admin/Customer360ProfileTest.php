<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Customer360ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected User $vipCustomer;
    protected User $prospectCustomer;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);

        $this->admin = Admin::factory()->create([
            'status' => 'active',
        ]);
        $this->admin->roles()->attach(Role::where('slug', 'super-admin')->firstOrFail());

        // Configure WhatsApp credentials in settings
        \App\Models\Setting::updateOrCreate(['key' => 'whatsapp_phone_number_id'], ['value' => '109876543210987', 'group' => 'whatsapp']);
        \App\Models\Setting::updateOrCreate(['key' => 'whatsapp_orders_enabled'], ['value' => '1', 'group' => 'whatsapp']);
        \App\Models\Setting::updateOrCreate(['key' => 'whatsapp_access_token'], [
            'value' => \Illuminate\Support\Facades\Crypt::encryptString('mock_meta_access_token_123'),
            'group' => 'whatsapp',
        ]);

        $this->product = Product::create([
            'name' => 'Ayurvedic Radiance Oil',
            'slug' => 'ayurvedic-radiance-oil',
            'sku' => 'AYUR-OIL-01',
            'status' => 'active',
            'mrp' => 1499.00,
            'selling_price' => 1299.00,
            'stock' => 50,
        ]);

        // 1. VIP Customer (2 orders, total spent >= 2500)
        $this->vipCustomer = User::factory()->create([
            'name' => 'Aarav Sharma',
            'email' => 'aarav.sharma@example.com',
            'phone' => '9876543210',
            'status' => 'active',
        ]);

        $order1 = Order::create([
            'user_id' => $this->vipCustomer->id,
            'order_number' => 'VAN-2026-VIP01',
            'order_status' => 'delivered',
            'payment_status' => 'paid',
            'payment_method' => 'razorpay',
            'subtotal' => 1299.00,
            'grand_total' => 1299.00,
            'billing_name' => 'Aarav Sharma',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Flat 401, Lotus Towers',
            'billing_city' => 'Mumbai',
            'billing_state' => 'Maharashtra',
            'billing_pincode' => '400001',
            'billing_country' => 'India',
            'shipping_name' => 'Aarav Sharma',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Flat 401, Lotus Towers',
            'shipping_city' => 'Mumbai',
            'shipping_state' => 'Maharashtra',
            'shipping_pincode' => '400001',
            'shipping_country' => 'India',
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'price' => 1299.00,
            'quantity' => 1,
            'subtotal' => 1299.00,
            'total' => 1299.00,
        ]);

        $order2 = Order::create([
            'user_id' => $this->vipCustomer->id,
            'order_number' => 'VAN-2026-VIP02',
            'order_status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'razorpay',
            'subtotal' => 1350.00,
            'grand_total' => 1350.00,
            'billing_name' => 'Aarav Sharma',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Flat 401, Lotus Towers',
            'billing_city' => 'Mumbai',
            'billing_state' => 'Maharashtra',
            'billing_pincode' => '400001',
            'billing_country' => 'India',
            'shipping_name' => 'Aarav Sharma',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Flat 401, Lotus Towers',
            'shipping_city' => 'Mumbai',
            'shipping_state' => 'Maharashtra',
            'shipping_pincode' => '400001',
            'shipping_country' => 'India',
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'price' => 1350.00,
            'quantity' => 1,
            'subtotal' => 1350.00,
            'total' => 1350.00,
        ]);

        // 2. Prospect Customer (0 orders)
        $this->prospectCustomer = User::factory()->create([
            'name' => 'Priya Patel',
            'email' => 'priya.patel@example.com',
            'phone' => '9123456789',
            'status' => 'active',
        ]);

        // 3. Approved template
        WhatsAppTemplate::create([
            'name' => 'vanriti_welcome_v1',
            'category' => 'marketing',
            'language' => 'en',
            'status' => 'APPROVED',
            'body_text' => 'Hello {{1}}, welcome to Vanriti Ayurveda!',
        ]);
    }

    public function test_admin_can_view_customers_directory_with_kpi_cards(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.customers.index'));

        $response->assertStatus(200);
        $response->assertSee('Customer 360° Directory');
        $response->assertSee('Total Customers');
        $response->assertSee('Active Shoppers');
        $response->assertSee('VIP Champions');
        $response->assertSee('WhatsApp Reachable');
        $response->assertSee('Aarav Sharma');
        $response->assertSee('Priya Patel');
        $response->assertSee('VIP Champion');
    }

    public function test_admin_can_filter_customers_by_segment(): void
    {
        // Filter VIP
        $responseVip = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.customers.index', ['segment' => 'vip']));

        $responseVip->assertStatus(200);
        $responseVip->assertSee('Aarav Sharma');
        $responseVip->assertDontSee('Priya Patel');

        // Filter Prospect
        $responseProspect = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.customers.index', ['segment' => 'prospect']));

        $responseProspect->assertStatus(200);
        $responseProspect->assertSee('Priya Patel');
        $responseProspect->assertDontSee('Aarav Sharma');
    }

    public function test_admin_can_toggle_customer_status_via_ajax(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.customers.toggle', $this->vipCustomer->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'inactive',
        ]);

        $this->assertEquals('inactive', $this->vipCustomer->fresh()->status);
    }

    public function test_admin_can_view_customer_360_profile_with_clv_and_purchase_history(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.customers.show', $this->vipCustomer->id));

        $response->assertStatus(200);
        $response->assertSee('Aarav Sharma');
        $response->assertSee('VIP Champion');
        $response->assertSee('Lifetime Value (CLV)');
        $response->assertSee('2,649'); // 1299 + 1350
        $response->assertSee('VAN-2026-VIP01');
        $response->assertSee('VAN-2026-VIP02');
        $response->assertSee('Ayurvedic Radiance Oil');
        $response->assertSee('Open in WhatsApp Inbox');
    }

    public function test_admin_can_send_whatsapp_template_to_customer(): void
    {
        Http::fake([
            'https://graph.facebook.com/v21.0/*' => Http::response([
                'messages' => [['id' => 'wamid.HBgLMjA5ODc2NTE2ODIVAgARGBIzQ0Q5M0RBMkM2RUI5QUIA']],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson(route('admin.customers.send-whatsapp', $this->vipCustomer->id), [
                'type' => 'template',
                'template_name' => 'vanriti_welcome_v1',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('whatsapp_conversations', [
            'user_id' => $this->vipCustomer->id,
            'phone' => '919876543210',
        ]);

        $this->assertDatabaseHas('whatsapp_messages', [
            'message_type' => 'template',
            'direction' => 'outbound',
        ]);
    }

    public function test_admin_can_jump_to_inbox_with_customer_selected(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.whatsapp.inbox', ['user_id' => $this->vipCustomer->id]));

        $response->assertStatus(200);
        $response->assertSee('919876543210');
    }
}
