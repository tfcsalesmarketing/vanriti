<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Product;
use App\Models\User;
use App\Services\CouponService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCart(User $user, float $subtotal): Cart
    {
        $cart = Cart::create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
        ]);

        $product = Product::factory()->active()->create([
            'selling_price' => $subtotal,
            'mrp' => $subtotal,
            'gst_rate' => 0,
            'stock' => 100,
        ]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->selling_price,
            'mrp' => $product->mrp,
            'gst_rate' => 0,
        ]);

        return $cart->fresh();
    }

    public function test_valid_percentage_coupon_applies_discount(): void
    {
        $user = User::factory()->create();
        $cart = $this->makeCart($user, 1000);

        $coupon = Coupon::factory()->create([
            'code' => 'PERCENT10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_cart_value' => 0,
            'max_discount' => null,
            'first_order_only' => false,
            'per_customer_limit' => 1,
        ]);

        $result = app(CouponService::class)->validate('PERCENT10', $cart, $user);

        $this->assertTrue($result['valid']);
        $this->assertEqualsWithDelta(100.00, $result['discount'], 0.01);
        $this->assertSame($coupon->id, $result['coupon']->id);
    }

    public function test_percentage_coupon_respects_max_discount(): void
    {
        $user = User::factory()->create();
        $cart = $this->makeCart($user, 1000);

        Coupon::factory()->create([
            'code' => 'MAXCAP',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'min_cart_value' => 0,
            'max_discount' => 80,
            'first_order_only' => false,
            'per_customer_limit' => 1,
        ]);

        $result = app(CouponService::class)->validate('MAXCAP', $cart, $user);

        $this->assertTrue($result['valid']);
        $this->assertEqualsWithDelta(80.00, $result['discount'], 0.01);
    }

    public function test_fixed_coupon_applies_fixed_discount(): void
    {
        $user = User::factory()->create();
        $cart = $this->makeCart($user, 1000);

        Coupon::factory()->create([
            'code' => 'FIX200',
            'discount_type' => 'fixed',
            'discount_value' => 200,
            'min_cart_value' => 0,
            'max_discount' => null,
            'first_order_only' => false,
            'per_customer_limit' => 1,
        ]);

        $result = app(CouponService::class)->validate('FIX200', $cart, $user);

        $this->assertTrue($result['valid']);
        $this->assertEqualsWithDelta(200.00, $result['discount'], 0.01);
    }

    public function test_below_minimum_order_coupon_is_invalid(): void
    {
        $user = User::factory()->create();
        $cart = $this->makeCart($user, 400);

        Coupon::factory()->create([
            'code' => 'MIN499',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_cart_value' => 499,
            'first_order_only' => false,
            'per_customer_limit' => 1,
        ]);

        $result = app(CouponService::class)->validate('MIN499', $cart, $user);

        $this->assertFalse($result['valid']);
        $this->assertSame(0, $result['discount']);
    }

    public function test_expired_coupon_is_invalid(): void
    {
        $user = User::factory()->create();
        $cart = $this->makeCart($user, 1000);

        Coupon::factory()->create([
            'code' => 'EXPIRED',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_cart_value' => 0,
            'starts_at' => Carbon::now()->subDays(30),
            'expires_at' => Carbon::now()->subDay(),
            'first_order_only' => false,
            'per_customer_limit' => 1,
        ]);

        $result = app(CouponService::class)->validate('EXPIRED', $cart, $user);

        $this->assertFalse($result['valid']);
    }

    public function test_inactive_coupon_is_invalid(): void
    {
        $user = User::factory()->create();
        $cart = $this->makeCart($user, 1000);

        Coupon::factory()->create([
            'code' => 'INACTIVE',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_cart_value' => 0,
            'is_active' => false,
            'first_order_only' => false,
            'per_customer_limit' => 1,
        ]);

        $result = app(CouponService::class)->validate('INACTIVE', $cart, $user);

        $this->assertFalse($result['valid']);
    }

    public function test_coupon_at_usage_limit_is_invalid(): void
    {
        $user = User::factory()->create();
        $cart = $this->makeCart($user, 1000);

        $coupon = Coupon::factory()->create([
            'code' => 'LIMITED',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_cart_value' => 0,
            'usage_limit' => 1,
            'first_order_only' => false,
            'per_customer_limit' => 10,
        ]);

        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => User::factory()->create()->id,
            'order_id' => null,
            'discount_amount' => 10,
        ]);

        $result = app(CouponService::class)->validate('LIMITED', $cart, $user);

        $this->assertFalse($result['valid']);
    }

    public function test_per_customer_limit_is_enforced(): void
    {
        $user = User::factory()->create();
        $cart = $this->makeCart($user, 1000);

        $coupon = Coupon::factory()->create([
            'code' => 'PERUSER',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_cart_value' => 0,
            'usage_limit' => null,
            'per_customer_limit' => 1,
            'first_order_only' => false,
        ]);

        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => null,
            'discount_amount' => 10,
        ]);

        $result = app(CouponService::class)->validate('PERUSER', $cart, $user);

        $this->assertFalse($result['valid']);
    }

    public function test_record_usage_increments_coupon_usage_count(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::factory()->create([
            'code' => 'RECORD',
            'discount_type' => 'fixed',
            'discount_value' => 50,
            'min_cart_value' => 0,
            'usage_limit' => null,
            'per_customer_limit' => 10,
            'first_order_only' => false,
        ]);

        $order = \App\Models\Order::create([
            'user_id' => $user->id,
            'order_number' => 'VAN-2026-000001',
            'billing_name' => 'A',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => '1',
            'billing_city' => 'C',
            'billing_state' => 'S',
            'billing_pincode' => '560038',
            'shipping_name' => 'A',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '1',
            'shipping_city' => 'C',
            'shipping_state' => 'S',
            'shipping_pincode' => '560038',
            'subtotal' => 100,
            'grand_total' => 100,
            'amount_due' => 100,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $this->assertSame(0, $coupon->usages()->count());

        app(CouponService::class)->recordUsage($coupon, $user, $order->id, 50.00);

        $this->assertSame(1, $coupon->usages()->count());

        $usage = $coupon->usages()->firstOrFail();
        $this->assertSame((int) $user->id, (int) $usage->user_id);
        $this->assertSame((int) $order->id, (int) $usage->order_id);
        $this->assertEqualsWithDelta(50.00, (float) $usage->discount_amount, 0.01);
    }
}
