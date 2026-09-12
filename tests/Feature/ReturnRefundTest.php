<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\RefundTransaction;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\OrderService;
use App\Services\RefundService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnRefundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    protected function deliveredOrder(User $user, Product $product, int $qty = 1): Order
    {
        $cart = Cart::create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
        ]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => $qty,
            'unit_price' => $product->selling_price,
            'mrp' => $product->mrp,
            'gst_rate' => $product->gst_rate,
        ]);

        $order = app(OrderService::class)->placeOrder($user, $cart->fresh(), [
            'billing' => [
                'full_name' => 'Aarav Mehta', 'mobile' => '9876543210',
                'address_line1' => '42 MG Road', 'city' => 'Bengaluru',
                'state' => 'Karnataka', 'pincode' => '560038', 'country' => 'India',
            ],
            'shipping' => [
                'full_name' => 'Aarav Mehta', 'mobile' => '9876543210',
                'address_line1' => '42 MG Road', 'city' => 'Bengaluru',
                'state' => 'Karnataka', 'pincode' => '560038', 'country' => 'India',
            ],
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
            'coupon_code' => null,
            'notes' => null,
        ]);

        $order->update(['order_status' => 'delivered']);

        return $order->fresh();
    }

    public function test_create_return_creates_return_request_and_items(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 250.00,
            'mrp' => 250.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);
        $orderItem = $order->items()->firstOrFail();

        $response = $this->actingAs($user, 'web')
            ->post(route('account.order.return', $order), [
                'reason' => 'Not as expected',
                'description' => 'The product color was different.',
                'items' => [$orderItem->id],
            ]);

        $response->assertRedirect(route('account.order', $order));

        $returnRequest = ReturnRequest::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('requested', $returnRequest->status);
        $this->assertSame((int) $user->id, (int) $returnRequest->user_id);

        $this->assertDatabaseHas('return_items', [
            'return_request_id' => $returnRequest->id,
            'order_item_id' => $orderItem->id,
        ]);

        // Refund auto-created
        $refund = Refund::where('return_request_id', $returnRequest->id)->firstOrFail();
        $this->assertSame('requested', $refund->status);
    }

    public function test_guest_cannot_create_return_for_another_users_order(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 200.00,
            'mrp' => 200.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);

        $response = $this->actingAs($other, 'web')
            ->post(route('account.order.return', $order), [
                'reason' => 'test',
                'items' => [$order->items()->firstOrFail()->id],
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('return_requests', 0);
    }

    public function test_non_delivered_order_cannot_be_returned(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 200.00,
            'mrp' => 200.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id, 'quantity' => 1,
            'unit_price' => $product->selling_price, 'mrp' => $product->mrp, 'gst_rate' => 0,
        ]);

        $order = app(OrderService::class)->placeOrder($user, $cart->fresh(), [
            'billing' => ['full_name' => 'A', 'mobile' => '9876543210', 'address_line1' => '1', 'city' => 'C', 'state' => 'S', 'pincode' => '560038'],
            'shipping' => ['full_name' => 'A', 'mobile' => '9876543210', 'address_line1' => '1', 'city' => 'C', 'state' => 'S', 'pincode' => '560038'],
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
            'coupon_code' => null,
            'notes' => null,
        ]);
        // order_status is 'pending', not deliverable

        $response = $this->actingAs($user, 'web')
            ->post(route('account.order.return', $order), [
                'reason' => 'test',
                'items' => [$order->items()->firstOrFail()->id],
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('return_requests', 0);
    }

    public function test_refund_service_create_from_return_creates_refund(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 400.00,
            'mrp' => 400.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);
        $returnRequest = ReturnRequest::create([
            'return_number' => 'RTR-2026-TEST01',
            'order_id' => $order->id,
            'user_id' => $user->id,
            'reason' => 'Damaged',
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        $refund = app(RefundService::class)->createFromReturn($returnRequest, $user, 400.00, 'full', 'Damaged product');

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'return_request_id' => $returnRequest->id,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'amount' => 400.00,
            'status' => 'requested',
        ]);
    }

    public function test_refund_service_complete_updates_order_and_records_transaction(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 500.00,
            'mrp' => 500.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);

        // Create a paid payment so gatewayFor can resolve cod
        Payment::create([
            'order_id' => $order->id,
            'method' => 'cod',
            'amount' => $order->amount_due,
            'status' => 'paid',
        ]);

        $returnRequest = ReturnRequest::create([
            'return_number' => 'RTR-2026-TEST02',
            'order_id' => $order->id,
            'user_id' => $user->id,
            'reason' => 'Damaged',
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        $refund = app(RefundService::class)->createFromReturn($returnRequest, $user, 500.00, 'full', 'Damaged');
        $completed = app(RefundService::class)->complete($refund);

        $this->assertSame('completed', $completed->status);

        $this->assertDatabaseHas('refund_transactions', [
            'refund_id' => $refund->id,
            'status' => 'success',
            'amount' => 500.00,
        ]);

        $order->refresh();
        $this->assertSame('refunded', $order->payment_status);
    }
}
