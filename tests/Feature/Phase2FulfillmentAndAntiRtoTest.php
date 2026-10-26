<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Models\User;
use App\Services\CodConfirmationService;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesFulfilmentData;
use Tests\TestCase;

class Phase2FulfillmentAndAntiRtoTest extends TestCase
{
    use RefreshDatabase, CreatesFulfilmentData;

    public function test_customer_can_view_own_tax_invoice(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder([
            'user_id' => $user->id,
            'order_number' => 'VR-INV-1001',
            'order_status' => 'confirmed',
            'subtotal' => 1000,
            'tax_amount' => 50,
            'grand_total' => 1050,
            'shipping_state' => 'Maharashtra',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Rose Silk Saree',
            'sku' => 'RSS-01',
            'unit_price' => 1000,
            'quantity' => 1,
            'total_price' => 1000,
        ]);

        $response = $this->actingAs($user)->get(route('account.order.invoice', $order));

        $response->assertStatus(200);
        $response->assertSee('TAX INVOICE');
        $response->assertSee('VR-INV-1001');
        $response->assertSee('Rose Silk Saree');
    }

    public function test_customer_cannot_view_another_customers_tax_invoice(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $order = $this->createOrder([
            'user_id' => $user1->id,
            'order_number' => 'VR-INV-1002',
        ]);

        $response = $this->actingAs($user2)->get(route('account.order.invoice', $order));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_invoice_and_manifest(): void
    {
        $admin = Admin::factory()->superAdmin()->create();

        $order = $this->createOrder([
            'order_number' => 'VR-INV-1003',
            'order_status' => 'packed',
        ]);

        Shipment::create([
            'order_id' => $order->id,
            'courier' => 'Delhivery',
            'tracking_number' => 'DEL123456789',
            'status' => 'packed',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.invoice', $order))
            ->assertStatus(200)
            ->assertSee('TAX INVOICE');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.manifest'))
            ->assertStatus(200)
            ->assertSee('COURIER HANDOVER MANIFEST')
            ->assertSee('DEL123456789');
    }

    public function test_cod_confirmation_service_confirms_order_and_cancels_properly(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 10,
        ]);

        $order = $this->createOrder([
            'order_number' => 'VR-COD-2001',
            'payment_method' => 'cod',
            'order_status' => 'pending',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => 'Banarasi Dupatta',
            'sku' => $variant->sku,
            'unit_price' => 500,
            'quantity' => 2,
            'total_price' => 1000,
        ]);

        // Mock WhatsApp Cloud API so no live HTTP calls happen
        $this->mock(WhatsAppCloudApiService::class, function ($mock) {
            $mock->shouldReceive('sendTextMessage')->andReturn(['messages' => [['id' => 'wamid.123']]]);
        });

        // Test Customer Confirms
        $confirmResult = app(CodConfirmationService::class)->handleCustomerConfirm('VR-COD-2001', '9876543210');
        $this->assertTrue($confirmResult);

        $order->refresh();
        $this->assertSame('processing', $order->order_status);

        // Put to pending again to test Cancellation
        $order->update(['order_status' => 'pending']);
        $cancelResult = app(CodConfirmationService::class)->handleCustomerCancel('VR-COD-2001', '9876543210');
        $this->assertTrue($cancelResult);

        $order->refresh();
        $this->assertSame('cancelled', $order->order_status);

        // Stock restored (10 + 2 = 12)
        $variant->refresh();
        $this->assertSame(12, $variant->stock);
    }
}
