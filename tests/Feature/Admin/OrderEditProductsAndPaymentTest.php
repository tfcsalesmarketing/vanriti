<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderEditProductsAndPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function actingAdmin(): Admin
    {
        $admin = Admin::factory()->superAdmin()->create();
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    private function createSampleOrder(array $orderOverrides = []): Order
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Ayurvedic Shampoo',
            'sku' => 'VAN-SHAMPOO-01',
            'status' => 'active',
            'mrp' => 500.00,
            'selling_price' => 400.00,
            'stock' => 20,
            'gst_rate' => 18,
        ]);

        $order = Order::create(array_merge([
            'order_number' => 'VAN-TEST-'.Str::upper(Str::random(6)),
            'user_id' => $user->id,
            'billing_name' => $user->name,
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Street 1',
            'billing_city' => 'Jaipur',
            'billing_state' => 'Rajasthan',
            'billing_pincode' => '302001',
            'billing_country' => 'India',
            'shipping_name' => $user->name,
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Street 1',
            'shipping_city' => 'Jaipur',
            'shipping_state' => 'Rajasthan',
            'shipping_pincode' => '302001',
            'shipping_country' => 'India',
            'is_billing_same' => 1,
            'subtotal' => 400.00,
            'discount_amount' => 0.00,
            'coupon_discount' => 0.00,
            'shipping_charge' => 0.00,
            'taxable_amount' => 338.98,
            'tax_amount' => 61.02,
            'cgst_amount' => 30.51,
            'sgst_amount' => 30.51,
            'igst_amount' => 0.00,
            'grand_total' => 400.00,
            'amount_paid' => 0.00,
            'amount_due' => 400.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'confirmed',
            'checkout_source' => 'admin',
        ], $orderOverrides));

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 1,
            'mrp' => 500.00,
            'unit_price' => 400.00,
            'discount' => 100.00,
            'gst_rate' => 18,
            'taxable_amount' => 338.98,
            'tax_amount' => 61.02,
            'total_price' => 400.00,
        ]);

        return $order->fresh(['items']);
    }

    public function test_admin_can_view_order_edit_page(): void
    {
        $this->actingAdmin();
        $order = $this->createSampleOrder();

        $response = $this->get(route('admin.orders.edit', $order));

        $response->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Payment Method &amp; Status', false)
            ->assertSee('Order Products &amp; Line Items', false)
            ->assertSee('Ayurvedic Shampoo');
    }

    public function test_admin_can_change_payment_method_and_mark_as_paid(): void
    {
        $admin = $this->actingAdmin();
        $order = $this->createSampleOrder();

        $response = $this->post(route('admin.orders.payment', $order), [
            'payment_method' => 'upi',
            'payment_status' => 'paid',
            'payment_reference' => 'UPI-REF-998811',
            'amount_paid' => 400.00,
            'reason' => 'Customer sent GPay screenshot upon delivery',
        ]);

        $response->assertRedirect();
        $order->refresh();

        $this->assertEquals('upi', $order->payment_method);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals(400.00, (float) $order->amount_paid);
        $this->assertEquals(0.00, (float) $order->amount_due);
        $this->assertStringContainsString('Customer sent GPay screenshot', $order->internal_notes);

        $payment = Payment::where('order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('upi', $payment->method);
        $this->assertEquals('UPI-REF-998811', $payment->payment_reference);
    }

    public function test_admin_can_change_payment_method_to_cod_pending(): void
    {
        $this->actingAdmin();
        $order = $this->createSampleOrder([
            'payment_method' => 'prepaid',
            'payment_status' => 'paid',
            'amount_paid' => 400.00,
            'amount_due' => 0.00,
        ]);

        $response = $this->post(route('admin.orders.payment', $order), [
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'reason' => 'Online transaction failed at gateway, switched to COD',
        ]);

        $response->assertRedirect();
        $order->refresh();

        $this->assertEquals('cod', $order->payment_method);
        $this->assertEquals('pending', $order->payment_status);
        $this->assertEquals(0.00, (float) $order->amount_paid);
        $this->assertEquals(400.00, (float) $order->amount_due);
    }

    public function test_admin_can_add_product_to_existing_order_with_stock_deduction(): void
    {
        $this->actingAdmin();
        $order = $this->createSampleOrder();
        $existingItem = $order->items->first();

        $newProduct = Product::factory()->create([
            'name' => 'Kumkumadi Face Oil',
            'sku' => 'VAN-KUM-01',
            'status' => 'active',
            'mrp' => 800.00,
            'selling_price' => 600.00,
            'stock' => 15,
            'gst_rate' => 18,
        ]);

        $postData = [
            'adjust_stock' => 1,
            'items' => [
                [
                    'id' => $existingItem->id,
                    'product_id' => $existingItem->product_id,
                    'quantity' => 1,
                    'unit_price' => 400.00,
                    'mrp' => 500.00,
                ],
                [
                    // New item: id is null
                    'product_id' => $newProduct->id,
                    'quantity' => 2,
                    'unit_price' => 600.00,
                    'mrp' => 800.00,
                ],
            ],
        ];

        $response = $this->post(route('admin.orders.items', $order), $postData);
        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals(2, $order->items()->count());

        // Subtotal = 400 + (600 * 2) = 1600.00
        $this->assertEquals(1600.00, (float) $order->subtotal);
        $this->assertEquals(1600.00, (float) $order->grand_total);

        // Stock of newProduct should be reduced by 2 (15 - 2 = 13)
        $this->assertEquals(13, $newProduct->fresh()->stock);
    }

    public function test_admin_can_remove_product_from_existing_order_with_stock_restoration(): void
    {
        $this->actingAdmin();
        $order = $this->createSampleOrder();
        $item1 = $order->items->first();

        $product2 = Product::factory()->create([
            'name' => 'Neem Face Wash',
            'sku' => 'VAN-NEEM-01',
            'stock' => 8,
            'mrp' => 300.00,
            'selling_price' => 250.00,
            'gst_rate' => 18,
        ]);

        $item2 = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'product_name' => $product2->name,
            'sku' => $product2->sku,
            'quantity' => 2,
            'mrp' => 300.00,
            'unit_price' => 250.00,
            'gst_rate' => 18,
            'total_price' => 500.00,
        ]);

        $order->update([
            'subtotal' => 900.00,
            'grand_total' => 900.00,
            'amount_due' => 900.00,
        ]);

        // Remove item 2 by submitting only item 1 in items array
        $postData = [
            'adjust_stock' => 1,
            'items' => [
                [
                    'id' => $item1->id,
                    'product_id' => $item1->product_id,
                    'quantity' => 1,
                    'unit_price' => 400.00,
                ],
            ],
        ];

        $response = $this->post(route('admin.orders.items', $order), $postData);
        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals(1, $order->items()->count());
        $this->assertEquals(400.00, (float) $order->subtotal);
        $this->assertEquals(400.00, (float) $order->grand_total);

        // Stock of product2 should be restored by 2 (8 + 2 = 10)
        $this->assertEquals(10, $product2->fresh()->stock);
    }

    public function test_admin_can_update_item_quantity_and_unit_price(): void
    {
        $this->actingAdmin();
        $order = $this->createSampleOrder();
        $item = $order->items->first();
        $product = Product::find($item->product_id);
        $initialStock = (int) $product->stock; // 20

        // Increase quantity from 1 to 3, and change unit price from 400 to 350
        $postData = [
            'adjust_stock' => 1,
            'items' => [
                [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => 3,
                    'unit_price' => 350.00,
                    'mrp' => 500.00,
                ],
            ],
        ];

        $response = $this->post(route('admin.orders.items', $order), $postData);
        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals(1050.00, (float) $order->subtotal); // 3 * 350
        $this->assertEquals(1050.00, (float) $order->grand_total);

        // Stock should be reduced by the difference of 2 (20 - 2 = 18)
        $this->assertEquals($initialStock - 2, $product->fresh()->stock);
    }

    public function test_validation_prevents_removing_all_items(): void
    {
        $this->actingAdmin();
        $order = $this->createSampleOrder();

        $response = $this->post(route('admin.orders.items', $order), [
            'items' => [],
        ]);

        $response->assertSessionHasErrors(['items']);
    }
}
