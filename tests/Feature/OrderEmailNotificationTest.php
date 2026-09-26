<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    protected function addressData(string $paymentMethod = 'cod'): array
    {
        return [
            'shipping_name' => 'Aarav Mehta',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '42 MG Road',
            'shipping_address_line2' => 'Indiranagar',
            'shipping_landmark' => 'Near Metro',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'shipping_country' => 'India',
            'billing_same' => '1',
            'shipping_method' => 'standard',
            'payment_method' => $paymentMethod,
            'notes' => 'Please deliver after 6pm',
        ];
    }

    protected function makeCheckout(User $user): void
    {
        $product = Product::factory()->active()->create([
            'selling_price' => 800.00,
            'mrp' => 1000.00,
            'gst_rate' => 18,
            'stock' => 25,
        ]);

        Inventory::create([
            'stockable_type' => Product::class,
            'stockable_id' => $product->id,
            'stock_on_hand' => $product->stock,
            'low_stock_threshold' => $product->low_stock_threshold ?? 5,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 2]);
    }

    protected function enableRazorpay(): void
    {
        Setting::updateOrCreate(['key' => 'online_payment_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_key_id'], ['value' => 'rzp_test_key']);
        Setting::updateOrCreate(['key' => 'razorpay_key_secret'], ['value' => \Illuminate\Support\Facades\Crypt::encryptString('rzp_test_secret')]);
    }

    protected function fakeRazorpayOrder(): void
    {
        Http::fake([
            'api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_EZ6G0001',
                'amount' => 160000,
                'currency' => 'INR',
                'receipt' => 'VAN-CHK-0001',
            ], 200),
        ]);
    }

    protected function adminWithPermission(string $roleSlug): Admin
    {
        $admin = Admin::factory()->create();
        $admin->roles()->attach(Role::where('slug', $roleSlug)->firstOrFail());

        return $admin;
    }

    public function test_cod_checkout_sends_order_placed_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $this->makeCheckout($user);

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData());

        $order = Order::where('user_id', $user->id)->firstOrFail();

        Notification::assertSentTo(
            $user,
            OrderStatusNotification::class,
            fn (OrderStatusNotification $notification): bool => $notification->status === 'pending'
                && $notification->order->is($order)
        );
    }

    public function test_razorpay_order_creation_sends_pending_notification(): void
    {
        Notification::fake();

        $this->enableRazorpay();
        $this->fakeRazorpayOrder();

        $user = User::factory()->create();
        $this->makeCheckout($user);

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('razorpay'));

        $order = Order::where('user_id', $user->id)->firstOrFail();

        Notification::assertSentTo(
            $user,
            OrderStatusNotification::class,
            fn (OrderStatusNotification $notification): bool => $notification->status === 'pending'
                && $notification->order->is($order)
        );
    }

    public function test_razorpay_verify_success_sends_payment_confirmed_notification(): void
    {
        Notification::fake();

        $this->enableRazorpay();
        $this->fakeRazorpayOrder();

        $user = User::factory()->create();
        $this->makeCheckout($user);

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('razorpay'));

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $signature = hash_hmac('sha256', 'order_EZ6G0001'.'|'.'pay_TESTL1', 'rzp_test_secret');

        $this->actingAs($user, 'web')->post(route('checkout.verify'), [
            'razorpay_order_id' => 'order_EZ6G0001',
            'razorpay_payment_id' => 'pay_TESTL1',
            'razorpay_signature' => $signature,
            'order_id' => $order->id,
        ])->assertRedirect(route('checkout.success', $order));

        Notification::assertSentTo(
            $user,
            OrderStatusNotification::class,
            fn (OrderStatusNotification $notification): bool => $notification->status === 'payment_confirmed'
                && $notification->order->is($order)
        );
    }

    public function test_razorpay_verify_failure_sends_payment_failed_notification(): void
    {
        Notification::fake();

        $this->enableRazorpay();
        $this->fakeRazorpayOrder();

        $user = User::factory()->create();
        $this->makeCheckout($user);

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('razorpay'));

        $order = Order::where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user, 'web')->post(route('checkout.verify'), [
            'razorpay_order_id' => 'order_EZ6G0001',
            'razorpay_payment_id' => 'pay_TESTL1',
            'razorpay_signature' => 'invalid-signature',
            'order_id' => $order->id,
        ])->assertRedirect(route('checkout.failed', $order));

        Notification::assertSentTo(
            $user,
            OrderStatusNotification::class,
            fn (OrderStatusNotification $notification): bool => $notification->status === 'payment_failed'
                && $notification->order->is($order)
        );
    }

    public function test_admin_confirms_order_sends_confirmed_notification(): void
    {
        Notification::fake();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->adminWithPermission('support');

        $user = User::factory()->create();
        $this->makeCheckout($user);
        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData());

        $order = Order::where('user_id', $user->id)->firstOrFail();

        $admin = $this->adminWithPermission('support');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.orders.status', $order), ['order_status' => 'confirmed'])
            ->assertSessionHas('success');

        Notification::assertSentTo(
            $user,
            OrderStatusNotification::class,
            fn (OrderStatusNotification $notification): bool => $notification->status === 'confirmed'
                && $notification->order->is($order)
        );
    }

    public function test_label_generation_sends_ready_to_dispatch_notification(): void
    {
        Notification::fake();

        $this->seed(RolesAndPermissionsSeeder::class);

        Http::fake([
            'shipping-api.com/*' => Http::response([
                'result' => '1',
                'data' => [['label' => 'data:image/png;base64,iVBORw0KGgo=']],
            ], 200),
        ]);

        $admin = $this->adminWithPermission('support');

        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'VAN-2026-990101',
            'user_id' => $user->id,
            'billing_name' => $user->name,
            'billing_mobile' => '9876543210',
            'billing_address_line1' => '42 MG Road',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => $user->name,
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '42 MG Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'subtotal' => 1600,
            'grand_total' => 1600,
            'taxable_amount' => 1355.93,
            'tax_amount' => 244.07,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
        ]);
        $order->shipments()->create([
            'awb_number' => 'AWB12345',
            'courier' => 'Delhivery',
            'status' => 'packed',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.shipmojo.label', $order))
            ->assertOk();

        Notification::assertSentTo(
            $user,
            OrderStatusNotification::class,
            fn (OrderStatusNotification $notification): bool => $notification->status === 'packed'
                && $notification->order->is($order)
        );
    }

    public function test_tracking_event_delivered_sends_delivered_notification(): void
    {
        Notification::fake();

        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = $this->adminWithPermission('support');

        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'VAN-2026-990202',
            'user_id' => $user->id,
            'billing_name' => $user->name,
            'billing_mobile' => '9876543210',
            'billing_address_line1' => '42 MG Road',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => $user->name,
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '42 MG Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'subtotal' => 1600,
            'grand_total' => 1600,
            'taxable_amount' => 1355.93,
            'tax_amount' => 244.07,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'order_status' => 'shipped',
        ]);
        $shipment = $order->shipments()->create([
            'awb_number' => 'AWB9999',
            'courier' => 'Delhivery',
            'status' => 'shipped',
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.shipments.tracking', $shipment), [
                'status' => 'delivered',
                'description' => 'Handed over to customer',
                'location' => 'Bengaluru',
                'event_date' => now()->toDateString(),
            ])
            ->assertSessionHas('success');

        Notification::assertSentTo(
            $user,
            OrderStatusNotification::class,
            fn (OrderStatusNotification $notification): bool => $notification->status === 'delivered'
                && $notification->order->is($order)
        );
    }

    public function test_shipped_notification_renders_branded_template_with_tracking(): void
    {
        $user = User::factory()->create(['name' => 'Aarav Mehta']);
        $order = Order::create([
            'order_number' => 'VAN-2026-000111',
            'user_id' => $user->id,
            'billing_name' => $user->name,
            'billing_mobile' => '9876543210',
            'billing_address_line1' => '42 MG Road',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => $user->name,
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '42 MG Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'subtotal' => 1600,
            'grand_total' => 1600,
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'order_status' => 'shipped',
        ]);
        $order->shipments()->create([
            'awb_number' => 'AWB99111',
            'courier' => 'BlueDart',
            'status' => 'shipped',
            'estimated_delivery' => now()->addDays(3),
        ]);

        $notification = new OrderStatusNotification($order, 'shipped');
        $mail = $notification->toMail($user);
        $html = $mail->render();

        $this->assertSame('Order Shipped - '.$order->order_number, $mail->subject);
        $this->assertStringContainsString('On Its Way!', $html);
        $this->assertStringContainsString('Hi Aarav,', $html);
        $this->assertStringContainsString('AWB99111', $html);
        $this->assertStringContainsString('BlueDart', $html);
        $this->assertStringContainsString('Track My Order', $html);
        $this->assertStringContainsString($order->order_number, $html);
    }

    public function test_payment_failed_notification_renders_retry_copy(): void
    {
        $user = User::factory()->create(['name' => 'Aarav Mehta']);
        $order = Order::create([
            'order_number' => 'VAN-2026-000222',
            'user_id' => $user->id,
            'billing_name' => $user->name,
            'billing_mobile' => '9876543210',
            'billing_address_line1' => '42 MG Road',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => $user->name,
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '42 MG Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'subtotal' => 1600,
            'grand_total' => 1600,
            'payment_method' => 'razorpay',
            'payment_status' => 'failed',
            'order_status' => 'pending',
        ]);

        $notification = new OrderStatusNotification($order, 'payment_failed');
        $mail = $notification->toMail($user);
        $html = $mail->render();

        $this->assertSame('Payment Failed - '.$order->order_number, $mail->subject);
        $this->assertStringContainsString('No amount was charged', $html);
        $this->assertStringContainsString($order->order_number, $html);
    }
}
