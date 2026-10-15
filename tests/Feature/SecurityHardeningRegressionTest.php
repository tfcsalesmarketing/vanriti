<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Notifications\RefundStatusNotification;
use App\Notifications\ReturnStatusNotification;
use App\Services\InventoryService;
use App\Services\NotificationService;
use App\Services\Payments\PaymentService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesFulfilmentData;
use Tests\TestCase;

class SecurityHardeningRegressionTest extends TestCase
{
    use CreatesFulfilmentData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    private function pricedProduct(int $stock = 10): Product
    {
        return Product::factory()->active()->create([
            'selling_price' => 100,
            'mrp' => 100,
            'gst_rate' => 0,
            'stock' => $stock,
        ]);
    }

    // --- Fix #1: NotificationService missing imports -------------------------

    public function test_return_and_refund_notifications_are_sent_without_class_errors(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = $this->createOrder(['user_id' => $user->id]);

        $returnRequest = ReturnRequest::create([
            'return_number' => 'RET-TEST-1',
            'order_id' => $order->id,
            'user_id' => $user->id,
            'reason' => 'Damaged',
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        $refund = Refund::create([
            'refund_number' => 'REF-TEST-1',
            'order_id' => $order->id,
            'user_id' => $user->id,
            'return_request_id' => $returnRequest->id,
            'amount' => 100,
            'type' => 'partial',
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        $service = app(NotificationService::class);

        $service->returnRequested($returnRequest);
        $service->returnStatusChanged($returnRequest, 'approved');
        $service->refundStatusChanged($refund);

        Notification::assertSentTo($user, ReturnStatusNotification::class);
        Notification::assertSentTo($user, RefundStatusNotification::class);
    }

    // --- Fix #4: admin password/change behind admin.auth ----------------------

    public function test_guest_password_change_redirects_to_admin_login(): void
    {
        $this->post(route('admin.password.change'), [
            'current_password' => 'whatever',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ])->assertRedirect(route('admin.login'));
    }

    public function test_authenticated_admin_can_change_password(): void
    {
        $admin = Admin::factory()->superAdmin()->create(['password' => 'oldpassword']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.password.change'), [
                'current_password' => 'oldpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('newpassword123', $admin->fresh()->password));
    }

    public function test_admin_password_change_rejects_wrong_current_password(): void
    {
        $admin = Admin::factory()->superAdmin()->create(['password' => 'oldpassword']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.password.change'), [
                'current_password' => 'wrongpassword',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ])
            ->assertSessionHasErrors('current_password');
    }

    // --- Fix #2 / #3: InventoryService row locking + adjustments --------------

    public function test_with_reference_creates_inventory_row_and_decrements_stock(): void
    {
        $product = $this->pricedProduct(10);
        $order = $this->createOrder();

        app(InventoryService::class)->withReference('sale', $product, $order, -3, 'Test sale');

        $this->assertDatabaseHas('inventories', [
            'stockable_type' => Product::class,
            'stockable_id' => $product->id,
            'stock_on_hand' => 7,
        ]);
        $this->assertSame(7, (int) $product->fresh()->stock);
        $this->assertDatabaseHas('inventory_transactions', [
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'type' => 'sale',
            'quantity_change' => -3,
        ]);
    }

    public function test_with_reference_reuses_existing_inventory_row(): void
    {
        $product = $this->pricedProduct(10);
        $order = $this->createOrder();
        $service = app(InventoryService::class);

        $service->withReference('sale', $product, $order, -2, 'First');
        $service->withReference('sale', $product, $order, -3, 'Second');

        $this->assertDatabaseCount('inventories', 1);
        $this->assertSame(5, (int) $product->fresh()->stock);
    }

    public function test_adjust_creates_inventory_row_when_missing(): void
    {
        $product = $this->pricedProduct(5);

        $inventory = app(InventoryService::class)->adjust('purchase', $product, 4, 'Restock');

        $this->assertSame(9, (int) $inventory->stock_on_hand);
        $this->assertSame(9, (int) $product->fresh()->stock);
        $this->assertDatabaseCount('inventories', 1);
    }

    public function test_adjust_rejects_negative_stock_and_rolls_back(): void
    {
        $product = $this->pricedProduct(2);

        try {
            app(InventoryService::class)->adjust('sale', $product, -5, 'Oversell attempt');
            $this->fail('Expected an insufficient stock exception.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Insufficient stock', $e->getMessage());
        }

        $this->assertSame(2, (int) $product->fresh()->stock);
        $this->assertDatabaseCount('inventories', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    // --- Fix #3: PaymentService::markPaid consistency -------------------------

    public function test_mark_paid_marks_payment_and_order_paid(): void
    {
        $order = $this->createOrder([
            'grand_total' => 500,
            'amount_paid' => 0,
            'amount_due' => 500,
            'payment_status' => 'pending',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'razorpay',
            'amount' => 500,
            'status' => 'pending',
        ]);

        app(PaymentService::class)->markPaid($payment);

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertEqualsWithDelta(500, (float) $order->amount_paid, 0.01);
        $this->assertEqualsWithDelta(0, (float) $order->amount_due, 0.01);
    }

    public function test_partial_mark_paid_keeps_order_pending(): void
    {
        $order = $this->createOrder([
            'grand_total' => 500,
            'amount_paid' => 0,
            'amount_due' => 500,
            'payment_status' => 'pending',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'razorpay',
            'amount' => 200,
            'status' => 'pending',
        ]);

        app(PaymentService::class)->markPaid($payment);

        $order->refresh();
        $this->assertSame('pending', $order->payment_status);
        $this->assertEqualsWithDelta(200, (float) $order->amount_paid, 0.01);
        $this->assertEqualsWithDelta(300, (float) $order->amount_due, 0.01);
    }
}
