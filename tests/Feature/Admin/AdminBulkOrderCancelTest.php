<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\OrderStatusNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesFulfilmentData;
use Tests\TestCase;

class AdminBulkOrderCancelTest extends TestCase
{
    use RefreshDatabase, CreatesFulfilmentData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Notification::fake();
        $this->actingAdmin();
    }

    private function actingAdmin(): void
    {
        $this->actingAs(Admin::factory()->superAdmin()->create(), 'admin');
    }

    private function cancellablePaidOrder(int $stock = 10, int $qty = 2): Order
    {
        $product = Product::factory()->active()->create([
            'selling_price' => 500,
            'mrp' => 500,
            'gst_rate' => 0,
            'stock' => $stock,
        ]);

        $order = $this->createOrder(['order_status' => 'confirmed', 'grand_total' => $qty * 500]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => $qty,
            'unit_price' => 500,
            'total_price' => $qty * 500,
            'gst_rate' => 0,
        ]);

        app(\App\Services\InventoryService::class)->withReference(
            'sale',
            $product,
            $order,
            -$qty,
            'Stock deducted for order '.$order->order_number
        );

        return $order;
    }

    public function test_bulk_cancel_restores_stock_creates_refund_and_notifies(): void
    {
        $order = $this->cancellablePaidOrder();

        $this->post(route('admin.orders.bulk.cancel'), [
            'order_ids' => [$order->id],
        ])->assertRedirect()->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('cancelled', $order->order_status);
        $this->assertSame('Cancelled via admin bulk action', $order->cancellation_reason);

        $item = $order->items()->first()->product;
        $this->assertSame(10, (int) $item->fresh()->stock);
        $this->assertDatabaseHas('inventory_transactions', [
            'stockable_type' => Product::class,
            'stockable_id' => $item->id,
            'reference_type' => Order::class,
            'type' => 'reversal',
        ]);

        $this->assertDatabaseHas('refunds', ['order_id' => $order->id, 'amount' => 1000]);

        Notification::assertSentTo($order->user, OrderStatusNotification::class);

        $this->assertTrue(
            ActivityLog::where('entity_id', $order->id)
                ->where('action', 'order_status_changed')
                ->where('description', 'Order cancelled via admin bulk action.')
                ->exists()
        );
    }

    public function test_bulk_cancel_mixed_results_reports_warning(): void
    {
        $cancellable = $this->cancellablePaidOrder();
        $delivered = $this->createOrder(['order_status' => 'delivered']);

        $this->post(route('admin.orders.bulk.cancel'), [
            'order_ids' => [$cancellable->id, $delivered->id],
        ])->assertRedirect()->assertSessionHas('warning');

        $this->assertSame('cancelled', $cancellable->fresh()->order_status);
        $this->assertSame('delivered', $delivered->fresh()->order_status);
    }

    public function test_bulk_cancel_validates_order_ids(): void
    {
        $this->post(route('admin.orders.bulk.cancel'), [
            'order_ids' => [],
        ])->assertSessionHasErrors('order_ids');
    }
}