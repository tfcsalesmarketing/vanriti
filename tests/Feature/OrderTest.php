<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    protected function orderData(): array
    {
        return [
            'billing' => [
                'full_name' => 'Aarav Mehta',
                'mobile' => '9876543210',
                'address_line1' => '42 MG Road',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560038',
                'country' => 'India',
            ],
            'shipping' => [
                'full_name' => 'Aarav Mehta',
                'mobile' => '9876543210',
                'address_line1' => '42 MG Road',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560038',
                'country' => 'India',
            ],
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
            'coupon_code' => null,
            'notes' => null,
        ];
    }

    protected function makeCart(User $user, Product $product, int $qty): Cart
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

        return $cart->fresh();
    }

    public function test_place_order_with_coupon_records_usage_and_correct_amount_due(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 700.00,
            'mrp' => 1000.00,
            'gst_rate' => 18,
            'stock' => 20,
        ]);

        $coupon = Coupon::factory()->create([
            'code' => 'SAVE50',
            'discount_type' => 'fixed',
            'discount_value' => 50,
            'min_cart_value' => 0,
            'first_order_only' => false,
            'per_customer_limit' => 5,
        ]);

        $cart = $this->makeCart($user, $product, 1);
        $data = $this->orderData();
        $data['coupon_code'] = 'SAVE50';

        $order = app(OrderService::class)->placeOrder($user, $cart, $data);

        // subtotal 700 incl. 18% GST, coupon 50, shipping free (>=499)
        // taxable 700 / 1.18 = 593.22; GST embedded = 106.78; proportional coupon
        // split: taxable 550.85 + GST 99.15 = 650.00 (the discounted total).
        $this->assertEqualsWithDelta(700.00, (float) $order->subtotal, 0.01);
        $this->assertEqualsWithDelta(550.85, (float) $order->taxable_amount, 0.01);
        $this->assertEqualsWithDelta(99.15, (float) $order->tax_amount, 0.01);
        $this->assertEqualsWithDelta(50.00, (float) $order->coupon_discount, 0.01);
        $this->assertEqualsWithDelta(650.00, (float) $order->amount_due, 0.01);

        // Karnataka != business state (Haryana) -> IGST
        $this->assertEqualsWithDelta(0.00, (float) $order->cgst_amount, 0.01);
        $this->assertEqualsWithDelta(0.00, (float) $order->sgst_amount, 0.01);
        $this->assertEqualsWithDelta(99.15, (float) $order->igst_amount, 0.01);

        // Coupon snapshot columns
        $this->assertSame('SAVE50', $order->coupon_code);
        $this->assertSame('fixed', $order->coupon_type);
        $this->assertEqualsWithDelta(50.00, (float) $order->coupon_value, 0.01);

        // Coupon usage recorded
        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
        ]);
    }

    public function test_intra_state_order_records_cgst_and_sgst(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 500.00,
            'mrp' => 500.00,
            'gst_rate' => 5,
            'stock' => 10,
        ]);

        $cart = $this->makeCart($user, $product, 1);
        $data = $this->orderData();
        $data['shipping']['state'] = 'Haryana';
        $data['billing']['state'] = 'Haryana';

        $order = app(OrderService::class)->placeOrder($user, $cart, $data);

        // 500 inclusive of 5% GST -> taxable 476.19, GST 23.81; free shipping (>=499)
        $this->assertTrue($order->isIntraState());
        $this->assertEqualsWithDelta(476.19, (float) $order->taxable_amount, 0.01);
        $this->assertEqualsWithDelta(23.81, (float) $order->tax_amount, 0.01);
        $this->assertEqualsWithDelta(11.91, (float) $order->cgst_amount, 0.01);
        $this->assertEqualsWithDelta(11.90, (float) $order->sgst_amount, 0.01);
        $this->assertEqualsWithDelta(0.00, (float) $order->igst_amount, 0.01);
        $this->assertEqualsWithDelta(500.00, (float) $order->grand_total, 0.01);
        $this->assertEqualsWithDelta(500.00, (float) $order->amount_due, 0.01);
    }

    public function test_coupon_is_split_proportionally_so_invoice_reconciles(): void
    {
        $user = User::factory()->create();
        $a = Product::factory()->active()->create([
            'selling_price' => 200.00, 'mrp' => 200.00, 'gst_rate' => 5, 'stock' => 10,
        ]);
        $b = Product::factory()->active()->create([
            'selling_price' => 100.00, 'mrp' => 100.00, 'gst_rate' => 12, 'stock' => 10,
        ]);

        $coupon = Coupon::factory()->create([
            'code' => 'FLAT50',
            'discount_type' => 'fixed',
            'discount_value' => 50,
            'min_cart_value' => 0,
            'first_order_only' => false,
            'per_customer_limit' => 5,
        ]);

        $cart = $this->makeCart($user, $a, 1);
        $cart->items()->create([
            'product_id' => $b->id,
            'quantity' => 1,
            'unit_price' => $b->selling_price,
            'mrp' => $b->mrp,
            'gst_rate' => $b->gst_rate,
        ]);

        $data = $this->orderData();
        $data['coupon_code'] = 'FLAT50';

        $order = app(OrderService::class)->placeOrder($user, $cart, $data);

        // subtotal 300, coupon 50, standard shipping (300 < 499) = 49 -> total 299
        $this->assertEqualsWithDelta(300.00, (float) $order->subtotal, 0.01);
        $this->assertEqualsWithDelta(50.00, (float) $order->coupon_discount, 0.01);
        $this->assertEqualsWithDelta(49.00, (float) $order->shipping_charge, 0.01);
        $this->assertEqualsWithDelta(299.00, (float) $order->grand_total, 0.01);
        $this->assertEqualsWithDelta(299.00, (float) $order->amount_due, 0.01);

        // Invoice reconciles: taxable + GST + shipping == grand total
        $this->assertEqualsWithDelta(
            (float) $order->grand_total,
            (float) $order->taxable_value + (float) $order->tax_amount + (float) $order->shipping_charge,
            0.01
        );

        $itemA = $order->items()->where('product_id', $a->id)->first();
        $itemB = $order->items()->where('product_id', $b->id)->first();

        // 200 * (250/300) = 166.67 net -> taxable 158.73
        $this->assertEqualsWithDelta(158.73, (float) $itemA->taxable_amount, 0.01);
        // 100 * (250/300) = 83.33 net -> taxable 74.40
        $this->assertEqualsWithDelta(74.40, (float) $itemB->taxable_amount, 0.01);

        $this->assertSame('FLAT50', $order->coupon_code);
        $this->assertSame('fixed', $order->coupon_type);
        $this->assertEqualsWithDelta(50.00, (float) $order->coupon_value, 0.01);
    }

    public function test_place_order_throws_when_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 200.00,
            'mrp' => 200.00,
            'gst_rate' => 0,
            'stock' => 3,
        ]);

        $cart = $this->makeCart($user, $product, 10);

        $this->expectException(\RuntimeException::class);
        app(OrderService::class)->placeOrder($user, $cart, $this->orderData());

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_cancel_order_restores_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 300.00,
            'mrp' => 300.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $cart = $this->makeCart($user, $product, 2);
        $order = app(OrderService::class)->placeOrder($user, $cart, $this->orderData());

        // stock should be 8 after order
        $this->assertSame(8, (int) $product->fresh()->stock);

        $this->actingAs($user, 'web')
            ->post(route('account.order.cancel', $order), ['reason' => 'Changed my mind'])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('cancelled', $order->order_status);

        // Stock restored
        $this->assertSame(10, (int) $product->fresh()->stock);

        $this->assertDatabaseHas('inventory_transactions', [
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'type' => 'reversal',
            'quantity_change' => 2,
        ]);
    }

    public function test_order_number_matches_format_after_place_order(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 150.00,
            'mrp' => 150.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $cart = $this->makeCart($user, $product, 1);
        $order = app(OrderService::class)->placeOrder($user, $cart, $this->orderData());

        $this->assertMatchesRegularExpression('/^VAN-\d{4}-\d{6}$/', $order->order_number);
        $this->assertNotSame('TEMP', $order->order_number);
    }

    public function test_order_service_cart_is_emptied_after_place_order(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 120.00,
            'mrp' => 120.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $cart = $this->makeCart($user, $product, 1);
        app(OrderService::class)->placeOrder($user, $cart, $this->orderData());

        $this->assertSame(0, $cart->fresh()->items()->count());
    }
}
