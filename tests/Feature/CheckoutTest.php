<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
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

    public function test_guest_is_redirected_to_login_on_checkout(): void
    {
        $this->get(route('checkout.index'))->assertRedirect(route('login'));
    }

    public function test_full_cod_checkout_creates_order_with_correct_totals(): void
    {
        $user = User::factory()->create();
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

        $response = $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData());

        $response->assertRedirect();

        $order = Order::where('user_id', $user->id)->firstOrFail();

        $this->assertMatchesRegularExpression('/^VAN-\d{4}-\d{6}$/', $order->order_number);
        $this->assertSame('pending', $order->order_status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('cod', $order->payment_method);

        // subtotal = 800 * 2 = 1600 (incl. 18% GST), free shipping (>= 499)
        // taxable = 1600 / 1.18 = 1355.93; GST embedded = 244.07; payable = 1600
        $this->assertEqualsWithDelta(1600.00, (float) $order->subtotal, 0.01);
        $this->assertEqualsWithDelta(1355.93, (float) $order->taxable_amount, 0.01);
        $this->assertEqualsWithDelta(244.07, (float) $order->tax_amount, 0.01);
        $this->assertEqualsWithDelta(0.00, (float) $order->shipping_charge, 0.01);
        $this->assertEqualsWithDelta(1600.00, (float) $order->grand_total, 0.01);
        $this->assertEqualsWithDelta(1600.00, (float) $order->amount_due, 0.01);

        // Karnataka != business state (Haryana) -> IGST
        $this->assertEqualsWithDelta(0.00, (float) $order->cgst_amount, 0.01);
        $this->assertEqualsWithDelta(0.00, (float) $order->sgst_amount, 0.01);
        $this->assertEqualsWithDelta(244.07, (float) $order->igst_amount, 0.01);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 23]);

        $this->assertDatabaseHas('inventory_transactions', [
            'stockable_type' => Product::class,
            'stockable_id' => $product->id,
            'type' => 'sale',
            'quantity_change' => -2,
        ]);

        $payment = Payment::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('pending', $payment->status);
        $this->assertSame('cod', $payment->method);

        $cart = Cart::where('owner_type', User::class)->where('owner_id', $user->id)->firstOrFail();
        $this->assertSame(0, $cart->items()->count());
    }

    public function test_cod_checkout_with_coupon_applies_discount(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 1000.00,
            'mrp' => 1000.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $coupon = \App\Models\Coupon::factory()->create([
            'code' => 'FLAT100',
            'discount_type' => 'fixed',
            'discount_value' => 100,
            'min_cart_value' => 0,
            'first_order_only' => false,
            'per_customer_limit' => 5,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1]);
        $this->actingAs($user, 'web')->withSession(['cart_coupon' => ['code' => 'FLAT100', 'discount' => 100.0]])
            ->post(route('checkout.store'), $this->addressData());

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertEqualsWithDelta(100.00, (float) $order->coupon_discount, 0.01);
        $this->assertSame((int) $coupon->id, (int) $order->coupon_id);
        $this->assertEqualsWithDelta(900.00, (float) $order->amount_due, 0.01);

        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
        ]);
    }

    public function test_out_of_stock_product_cannot_be_checked_out(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 200.00,
            'mrp' => 200.00,
            'gst_rate' => 0,
            'stock' => 0,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1]);

        $response = $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData());

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_with_invalid_pincode_fails(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 200.00,
            'mrp' => 200.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1]);

        $data = $this->addressData();
        $data['shipping_pincode'] = '123';

        $response = $this->actingAs($user, 'web')->post(route('checkout.store'), $data);
        $response->assertSessionHasErrors('shipping_pincode');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_purchase_event_fires_on_success_for_paid_and_pending_orders(): void
    {
        Setting::updateOrCreate(['key' => 'meta_pixel_id'], [
            'value' => 'TEST1234',
            'group' => 'seo',
            'label' => 'Meta Pixel ID',
            'type' => 'text',
        ]);

        foreach (['paid', 'pending'] as $paymentStatus) {
            $user = User::factory()->create();
            $order = $this->makePurchaseOrder($user, $paymentStatus);

            $response = $this->actingAs($user, 'web')->get(route('checkout.success', $order));

            $response->assertOk();
            $response->assertSee("fbq('init', 'TEST1234')", false);
            $response->assertSee("fbq('track', 'Purchase'", false);
            $response->assertSee('"transaction_id":"' . $order->order_number . '"', false);
            $response->assertSee('"currency":"INR"', false);
            $response->assertSee('"value":1600', false);
            $response->assertSee('"id":"' . $order->items()->first()->sku . '"', false);
        }
    }

    public function test_purchase_event_not_fired_without_pixel_setting(): void
    {
        $user = User::factory()->create();
        $order = $this->makePurchaseOrder($user, 'paid');

        $response = $this->actingAs($user, 'web')->get(route('checkout.success', $order));

        $response->assertOk();
        $response->assertDontSee('fbq(', false);
        $response->assertDontSee('connect.facebook.net', false);
    }

    public function test_purchase_event_not_fired_for_failed_orders(): void
    {
        Setting::updateOrCreate(['key' => 'meta_pixel_id'], [
            'value' => 'TEST1234',
            'group' => 'seo',
            'label' => 'Meta Pixel ID',
            'type' => 'text',
        ]);

        $user = User::factory()->create();
        $order = $this->makePurchaseOrder($user, 'failed');

        $response = $this->actingAs($user, 'web')->get(route('checkout.success', $order));

        $response->assertOk();
        $response->assertDontSee("fbq('track', 'Purchase'", false);
    }

    public function test_razorpay_checkout_keeps_cart_until_payment_verified(): void
    {
        $this->enableRazorpay();
        $this->fakeRazorpayOrder();

        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 800.00,
            'mrp' => 1000.00,
            'gst_rate' => 18,
            'stock' => 25,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 2]);

        // Order creation alone must NOT clear the cart: the payment dialog can be
        // abandoned and the customer should still find their items.
        $create = $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('razorpay'));
        $create->assertOk()->assertJson(['success' => true]);

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $cart = Cart::where('owner_type', User::class)->where('owner_id', $user->id)->firstOrFail();
        $this->assertSame(1, $cart->items()->count());

        $signature = hash_hmac('sha256', 'order_EZ6G0001'.'|'.'pay_TESTL1', 'rzp_test_secret');

        $this->actingAs($user, 'web')->post(route('checkout.verify'), [
            'razorpay_order_id' => 'order_EZ6G0001',
            'razorpay_payment_id' => 'pay_TESTL1',
            'razorpay_signature' => $signature,
            'order_id' => $order->id,
        ])->assertRedirect(route('checkout.success', $order));

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame(0, $cart->fresh()->items()->count());
    }

    public function test_abandoned_razorpay_checkout_resumes_same_order(): void
    {
        $this->enableRazorpay();
        $this->fakeRazorpayOrder();

        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 800.00,
            'mrp' => 1000.00,
            'gst_rate' => 18,
            'stock' => 25,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 2]);

        $first = $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('razorpay'));
        $first->assertOk()->assertJson(['success' => true]);

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('processing', $order->payment_status);

        // The customer re-submits the same (unchanged) cart after abandoning the
        // dialog: the same pending order must be resumed, not duplicated.
        $second = $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('razorpay'));
        $second->assertOk()->assertJson([
            'success' => true,
            'order_id' => $order->id,
        ]);

        $this->assertSame(1, Order::where('user_id', $user->id)->count());
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_stale_submit_after_cod_redirects_to_pending_order_not_empty_cart_error(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 800.00,
            'mrp' => 1000.00,
            'gst_rate' => 18,
            'stock' => 25,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 2]);

        // First COD commit clears the cart (legacy behaviour) and succeeds.
        $first = $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('cod'));
        $first->assertRedirect();

        $order = Order::where('user_id', $user->id)->firstOrFail();

        // A re-submission (browser back / double send) with an already-empty cart
        // must land on the pending order instead of flashing "Your cart is empty."
        $second = $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('cod'));
        $second->assertRedirect(route('checkout.pending', $order));
        $this->assertSame(1, Order::where('user_id', $user->id)->count());
        $second->assertSessionMissing('errors');
    }

    public function test_stale_submit_without_pending_order_keeps_empty_cart_error(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 800.00,
            'mrp' => 1000.00,
            'gst_rate' => 18,
            'stock' => 25,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 2]);

        // No order exists: an empty-cart submission is a genuine error.
        $cart = Cart::where('owner_type', User::class)->where('owner_id', $user->id)->firstOrFail();
        $cart->items()->delete();

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('cod'))
            ->assertSessionHasErrors('checkout');

        $this->assertDatabaseCount('orders', 0);
    }

    protected function enableRazorpay(): void
    {
        Setting::updateOrCreate(['key' => 'online_payment_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_key_id'], ['value' => 'rzp_test_key']);
        Setting::updateOrCreate(['key' => 'razorpay_key_secret'], ['value' => 'rzp_test_secret']);
    }

    protected function fakeRazorpayOrder(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'api.razorpay.com/v1/orders' => \Illuminate\Support\Facades\Http::response([
                'id' => 'order_EZ6G0001',
                'amount' => 160000,
                'currency' => 'INR',
                'receipt' => 'VAN-CHK-0001',
            ], 200),
        ]);
    }

    protected function makePurchaseOrder(User $user, string $paymentStatus = 'paid'): Order
    {
        $product = Product::factory()->create([
            'selling_price' => 800.00,
            'mrp' => 1000.00,
        ]);

        $order = Order::create([
            'order_number' => 'VAN-0001-' . str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT),
            'user_id' => $user->id,
            'billing_name' => 'Aarav Mehta',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => '42 MG Road',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => 'Aarav Mehta',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '42 MG Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'subtotal' => 1600,
            'grand_total' => 1600,
            'payment_method' => 'razorpay',
            'payment_status' => $paymentStatus,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => 'VNRT-CHK-'.$paymentStatus,
            'quantity' => 2,
            'mrp' => 1000.00,
            'unit_price' => 800.00,
            'gst_rate' => 18,
            'tax_amount' => 244.07,
            'total_price' => 1600.00,
        ]);

        return $order;
    }
}