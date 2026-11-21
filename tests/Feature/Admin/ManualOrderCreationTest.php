<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ManualOrderCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Queue::fake([\App\Jobs\SendWhatsAppOrderConfirmation::class, \App\Jobs\PushOrderToShipMojo::class]);
    }

    private function actingAdmin(): Admin
    {
        $admin = Admin::factory()->superAdmin()->create();
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_admin_with_manage_orders_permission_can_view_create_order_page(): void
    {
        $this->actingAdmin();

        $response = $this->get(route('admin.orders.create'));

        $response->assertOk()
            ->assertSee('Create Manual Order')
            ->assertSee('Customer Details')
            ->assertSee('Order Items &amp; Products', false);
    }

    public function test_admin_can_search_customers_via_ajax(): void
    {
        $this->actingAdmin();

        $user = User::factory()->create([
            'name' => 'Meera Patel',
            'phone' => '9876500001',
            'email' => 'meera@example.com',
        ]);

        $response = $this->getJson(route('admin.orders.search-customers', ['q' => 'Meera']));

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $user->id,
                'name' => 'Meera Patel',
            ]);
    }

    public function test_admin_can_search_products_via_ajax(): void
    {
        $this->actingAdmin();

        $product = Product::factory()->create([
            'name' => 'Kumkumadi Ayurvedic Face Serum',
            'sku' => 'VAN-TEST-KUMKUM',
            'status' => 'active',
            'selling_price' => 799.00,
            'stock' => 15,
        ]);

        $response = $this->getJson(route('admin.orders.search-products', ['q' => 'Kumkumadi']));

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $product->id,
                'name' => 'Kumkumadi Ayurvedic Face Serum',
                'sku' => 'VAN-TEST-KUMKUM',
            ]);
    }

    public function test_admin_can_lookup_pincode_via_ajax(): void
    {
        $this->actingAdmin();

        $response = $this->getJson(route('admin.orders.lookup-pincode', ['pincode' => '302001']));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pincode', '302001');
    }

    public function test_admin_can_create_manual_order_for_new_customer(): void
    {
        $admin = $this->actingAdmin();

        $product = Product::factory()->create([
            'name' => 'Rose Water Mist',
            'sku' => 'VAN-ROSE-100',
            'status' => 'active',
            'mrp' => 500.00,
            'selling_price' => 399.00,
            'stock' => 25,
            'gst_rate' => 18,
        ]);

        $postData = [
            'customer_type' => 'new',
            'customer_name' => 'Aarav Sharma',
            'customer_phone' => '9876543210',
            'customer_email' => 'aarav@example.com',
            'save_customer_address' => 1,

            'shipping_name' => 'Aarav Sharma',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'B-12, Green Park',
            'shipping_city' => 'Jaipur',
            'shipping_state' => 'Rajasthan',
            'shipping_pincode' => '302016',
            'is_billing_same' => 1,

            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 399.00,
                    'mrp' => 500.00,
                ],
            ],

            'discount_type' => 'none',
            'shipping_charge' => 50.00,

            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'confirmed',
            'notes' => 'Walk-in phone booking',

            'reduce_stock' => 1,
            'send_whatsapp' => 1,
            'push_shipmojo' => 0,
        ];

        $response = $this->post(route('admin.orders.store'), $postData);

        // Verify order created and redirected to order show
        $order = Order::latest('id')->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('admin.orders.show', $order));

        $this->assertEquals('admin', $order->checkout_source);
        $this->assertEquals('confirmed', $order->order_status);
        $this->assertEquals('cod', $order->payment_method);
        $this->assertEquals('pending', $order->payment_status);
        $this->assertEquals(798.00, (float) $order->subtotal);
        $this->assertEquals(50.00, (float) $order->shipping_charge);
        $this->assertEquals(848.00, (float) $order->grand_total);
        $this->assertStringContainsString('Walk-in phone booking', $order->internal_notes);

        // Verify user was created
        $newUser = User::where('phone', '9876543210')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('Aarav Sharma', $newUser->name);
        $this->assertEquals('aarav@example.com', $newUser->email);
        $this->assertEquals($newUser->id, $order->user_id);

        // Verify address saved for customer
        $this->assertTrue($newUser->addresses()->where('pincode', '302016')->exists());

        // Verify inventory stock was reduced (25 - 2 = 23)
        $this->assertEquals(23, $product->fresh()->stock);

        // Verify order status history was recorded with admin ID
        $this->assertTrue(
            $order->statusHistories()->where('admin_id', $admin->id)->where('status', 'confirmed')->exists()
        );
    }

    public function test_admin_can_create_manual_order_for_existing_customer(): void
    {
        $this->actingAdmin();

        $existingUser = User::factory()->create([
            'name' => 'Kavita Joshi',
            'phone' => '9123456789',
            'email' => 'kavita@example.com',
        ]);

        $product = Product::factory()->create([
            'name' => 'Herbal Hair Oil',
            'sku' => 'VAN-HAIR-OIL',
            'status' => 'active',
            'mrp' => 600.00,
            'selling_price' => 450.00,
            'stock' => 10,
            'gst_rate' => 18,
        ]);

        $postData = [
            'customer_type' => 'existing',
            'user_id' => $existingUser->id,
            'customer_name' => $existingUser->name,
            'customer_phone' => $existingUser->phone,
            'customer_email' => $existingUser->email,

            'shipping_name' => 'Kavita Joshi',
            'shipping_mobile' => '9123456789',
            'shipping_address_line1' => 'Plot 44, Vaishali Nagar',
            'shipping_city' => 'Jaipur',
            'shipping_state' => 'Rajasthan',
            'shipping_pincode' => '302021',
            'is_billing_same' => 1,

            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 450.00,
                ],
            ],

            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'confirmed',
            'reduce_stock' => 1,
        ];

        $response = $this->post(route('admin.orders.store'), $postData);

        $order = Order::latest('id')->first();
        $response->assertRedirect(route('admin.orders.show', $order));

        $this->assertEquals($existingUser->id, $order->user_id);
        $this->assertEquals(9, $product->fresh()->stock);
    }

    public function test_manual_order_does_not_reduce_stock_when_reduce_stock_is_disabled(): void
    {
        $this->actingAdmin();

        $user = User::factory()->create();
        $product = Product::factory()->create([
            'status' => 'active',
            'selling_price' => 200.00,
            'stock' => 50,
        ]);

        $postData = [
            'customer_type' => 'existing',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '9998887776',
            'shipping_name' => $user->name,
            'shipping_mobile' => '9998887776',
            'shipping_address_line1' => 'Address 1',
            'shipping_city' => 'Delhi',
            'shipping_state' => 'Delhi',
            'shipping_pincode' => '110001',
            'is_billing_same' => 1,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'unit_price' => 200.00,
                ],
            ],
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'confirmed',
            'reduce_stock' => 0, // Disabled
        ];

        $response = $this->post(route('admin.orders.store'), $postData);
        $response->assertRedirect();

        // Stock must still be 50
        $this->assertEquals(50, $product->fresh()->stock);
    }

    public function test_manual_order_creates_payment_record_when_status_is_paid(): void
    {
        $this->actingAdmin();

        $user = User::factory()->create();
        $product = Product::factory()->create([
            'status' => 'active',
            'selling_price' => 1200.00,
            'stock' => 10,
        ]);

        $postData = [
            'customer_type' => 'existing',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '9998887776',
            'shipping_name' => $user->name,
            'shipping_mobile' => '9998887776',
            'shipping_address_line1' => 'Address 1',
            'shipping_city' => 'Delhi',
            'shipping_state' => 'Delhi',
            'shipping_pincode' => '110001',
            'is_billing_same' => 1,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 1200.00,
                ],
            ],
            'payment_method' => 'upi',
            'payment_status' => 'paid',
            'payment_reference' => 'UPI-UTR-998822',
            'order_status' => 'confirmed',
        ];

        $response = $this->post(route('admin.orders.store'), $postData);
        $order = Order::latest('id')->first();
        $response->assertRedirect(route('admin.orders.show', $order));

        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals(1200.00, (float) $order->amount_paid);
        $this->assertEquals(0.00, (float) $order->amount_due);

        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('upi', $payment->method);
        $this->assertEquals(1200.00, (float) $payment->amount);
        $this->assertEquals('UPI-UTR-998822', $payment->payment_reference);
    }

    public function test_manual_order_supports_admin_custom_unit_price_and_discount(): void
    {
        $this->actingAdmin();

        $user = User::factory()->create();
        $product = Product::factory()->create([
            'status' => 'active',
            'mrp' => 1000.00,
            'selling_price' => 850.00,
            'stock' => 20,
        ]);

        $postData = [
            'customer_type' => 'existing',
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => '9998887776',
            'shipping_name' => $user->name,
            'shipping_mobile' => '9998887776',
            'shipping_address_line1' => 'Address 1',
            'shipping_city' => 'Mumbai',
            'shipping_state' => 'Maharashtra',
            'shipping_pincode' => '400001',
            'is_billing_same' => 1,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    // Admin gives special wholesale quote: 700 instead of 850
                    'unit_price' => 700.00,
                    'mrp' => 1000.00,
                ],
            ],
            // Plus an additional flat discount of 100
            'discount_type' => 'fixed',
            'discount_value' => 100.00,
            'shipping_charge' => 0.00,

            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'confirmed',
        ];

        $response = $this->post(route('admin.orders.store'), $postData);
        $order = Order::latest('id')->first();
        $response->assertRedirect(route('admin.orders.show', $order));

        // Subtotal = 700 * 2 = 1400. Grand total = 1400 - 100 = 1300
        $this->assertEquals(1400.00, (float) $order->subtotal);
        $this->assertEquals(1300.00, (float) $order->grand_total);
    }

    public function test_validation_rejects_empty_items_or_invalid_pincode(): void
    {
        $this->actingAdmin();

        $response = $this->post(route('admin.orders.store'), [
            'customer_type' => 'new',
            'customer_name' => 'Test User',
            'customer_phone' => '9998887776',
            'shipping_name' => 'Test User',
            'shipping_mobile' => '9998887776',
            'shipping_address_line1' => 'Test Address',
            'shipping_city' => 'Jaipur',
            'shipping_state' => 'Rajasthan',
            'shipping_pincode' => '123', // Invalid pincode length
            'items' => [], // Empty items
        ]);

        $response->assertSessionHasErrors(['items', 'shipping_pincode']);
    }
}
