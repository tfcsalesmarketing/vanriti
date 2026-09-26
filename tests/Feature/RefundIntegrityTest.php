<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\OrderService;
use App\Services\Payments\PaymentService;
use App\Services\RefundService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RefundIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    protected function placeOrder(User $user, Product $product, int $qty = 1, string $method = 'cod'): Order
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

        return app(OrderService::class)->placeOrder($user, $cart->fresh(), [
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
            'payment_method' => $method,
            'coupon_code' => null,
            'notes' => null,
        ]);
    }

    protected function deliveredOrder(User $user, Product $product, int $qty = 1): Order
    {
        $order = $this->placeOrder($user, $product, $qty);
        $order->update(['order_status' => 'delivered']);

        return $order->fresh();
    }

    public function test_return_items_are_refunded_proportional_to_coupon_discount(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 400.00, 'mrp' => 400.00, 'gst_rate' => 0, 'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);
        // Rs.100 off a Rs.400 order => factor 0.75.
        $order->update(['subtotal' => 400.00, 'coupon_discount' => 100.00]);

        $item = $order->items()->firstOrFail();

        $this->actingAs($user, 'web')
            ->post(route('account.order.return', $order), [
                'reason' => 'Not as expected',
                'items' => [$item->id],
            ])
            ->assertRedirect(route('account.order', $order));

        $this->assertDatabaseHas('return_items', [
            'order_item_id' => $item->id,
            'refund_amount' => 300.00,
        ]);

        // No refund exists until an admin approves the return.
        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_same_order_item_cannot_be_returned_twice(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 400.00, 'mrp' => 400.00, 'gst_rate' => 0, 'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);
        $item = $order->items()->firstOrFail();

        $this->actingAs($user, 'web')
            ->post(route('account.order.return', $order), ['reason' => 'First', 'items' => [$item->id]])
            ->assertRedirect(route('account.order', $order));

        $this->actingAs($user, 'web')
            ->post(route('account.order.return', $order), ['reason' => 'Second', 'items' => [$item->id]])
            ->assertSessionHas('error');

        $this->assertSame(1, ReturnRequest::where('order_id', $order->id)->count());
    }

    public function test_cancelling_a_paid_order_creates_a_refund_for_amount_paid(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 500.00, 'mrp' => 500.00, 'gst_rate' => 0, 'stock' => 10,
        ]);

        $order = $this->placeOrder($user, $product, 1, 'razorpay');

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'razorpay',
            'amount' => $order->amount_due,
            'status' => 'pending',
        ]);

        app(PaymentService::class)->markPaid($payment);
        $order->refresh();
        $this->assertSame('paid', $order->payment_status);

        $this->actingAs($user, 'web')
            ->post(route('account.order.cancel', $order), ['reason' => 'Changed my mind'])
            ->assertRedirect();

        $refund = Refund::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('requested', $refund->status);
        $this->assertSame('full', $refund->type);
        $this->assertEqualsWithDelta((float) $order->amount_paid, (float) $refund->amount, 0.01);
    }

    public function test_pending_order_cancel_does_not_create_a_refund(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 500.00, 'mrp' => 500.00, 'gst_rate' => 0, 'stock' => 10,
        ]);

        $order = $this->placeOrder($user, $product, 1, 'razorpay');

        $this->actingAs($user, 'web')
            ->post(route('account.order.cancel', $order), ['reason' => 'Changed my mind'])
            ->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_completion_cannot_exceed_the_amount_paid(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 500.00, 'mrp' => 500.00, 'gst_rate' => 0, 'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);

        Payment::create([
            'order_id' => $order->id,
            'method' => 'cod',
            'amount' => $order->amount_due,
            'status' => 'paid',
        ]);

        $service = app(RefundService::class);

        // Completion requires an approved refund, so both are approved first.
        $first = $service->approve($service->createForOrder($order, $user, 300.00, 'partial', 'a'));
        $second = $service->approve($service->createForOrder($order, $user, 300.00, 'partial', 'b'));

        $service->complete($first);

        $this->assertSame('completed', $first->fresh()->status);
        $this->assertSame('partially_refunded', $order->fresh()->payment_status);

        try {
            $service->complete($second);
            $this->fail('Expected the cumulative refund cap to be enforced.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('exceed', $e->getMessage());
        }

        $this->assertNotSame('completed', $second->fresh()->status);
        $this->assertSame('partially_refunded', $order->fresh()->payment_status);
    }

    public function test_admin_approval_creates_a_single_refund(): void
    {
        Notification::fake();

        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = Admin::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'manager')->firstOrFail());

        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 400.00, 'mrp' => 400.00, 'gst_rate' => 0, 'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);
        $item = $order->items()->firstOrFail();

        $this->actingAs($user, 'web')
            ->post(route('account.order.return', $order), ['reason' => 'Damaged', 'items' => [$item->id]]);

        $returnRequest = ReturnRequest::where('order_id', $order->id)->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.returns.approve', $returnRequest))
            ->assertRedirect();

        $this->assertDatabaseCount('refunds', 1);
        $this->assertSame($returnRequest->id, Refund::first()->return_request_id);

        // Replaying the approval must not create a second refund.
        $this->actingAs($admin, 'admin')
            ->post(route('admin.returns.approve', $returnRequest->fresh()));

        $this->assertDatabaseCount('refunds', 1);
    }
}
