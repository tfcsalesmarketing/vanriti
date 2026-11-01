<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrepaidDiscountOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);

        Setting::updateOrCreate(
            ['key' => 'prepaid_discount_enabled'],
            ['group' => 'payment', 'value' => '1', 'type' => 'boolean']
        );
        Setting::updateOrCreate(
            ['key' => 'prepaid_discount_percentage'],
            ['group' => 'payment', 'value' => '2', 'type' => 'number']
        );
    }

    protected function createProduct(float $price = 1000.0, int $stock = 20): Product
    {
        return Product::factory()->active()->create([
            'selling_price' => $price,
            'mrp' => $price + 200,
            'gst_rate' => 18,
            'stock' => $stock,
        ]);
    }

    protected function orderData(string $paymentMethod = 'razorpay', ?string $couponCode = null): array
    {
        return [
            'billing' => [
                'full_name' => 'Priya Sharma',
                'mobile' => '9876543210',
                'address_line1' => 'Flat 101, Palm Meadows',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560066',
                'country' => 'India',
            ],
            'shipping' => [
                'full_name' => 'Priya Sharma',
                'mobile' => '9876543210',
                'address_line1' => 'Flat 101, Palm Meadows',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560066',
                'country' => 'India',
            ],
            'shipping_method' => 'standard',
            'payment_method' => $paymentMethod,
            'coupon_code' => $couponCode,
            'notes' => 'Please ring the doorbell',
        ];
    }

    protected function makeCart(User $user, Product $product, int $qty = 1): Cart
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

    public function test_razorpay_order_receives_2_percent_prepaid_discount(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $product = $this->createProduct(price: 1000.0);
        $cart = $this->makeCart($user, $product, 1);

        /** @var OrderService $service */
        $service = app(OrderService::class);
        $order = $service->placeOrder($user, $cart, $this->orderData('razorpay'), clearCart: false);

        // Subtotal = 1000.00
        // 2% Prepaid Discount = 20.00
        // Shipping is 0 (order value > 499 threshold)
        // Grand Total = 1000 - 20 = 980.00
        $this->assertEquals(1000.00, (float) $order->subtotal);
        $this->assertEquals(20.00, (float) $order->prepaid_discount);
        $this->assertEquals(0.00, (float) $order->coupon_discount);
        $this->assertEquals(980.00, (float) $order->grand_total);
        $this->assertEquals(980.00, (float) $order->amount_due);
        $this->assertTrue($order->hasPrepaidDiscount());
    }

    public function test_cod_order_pays_standard_price_without_prepaid_discount(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $product = $this->createProduct(price: 1000.0);
        $cart = $this->makeCart($user, $product, 1);

        /** @var OrderService $service */
        $service = app(OrderService::class);
        $order = $service->placeOrder($user, $cart, $this->orderData('cod'), clearCart: false);

        // Subtotal = 1000.00
        // Prepaid Discount = 0.00
        // Grand Total = 1000.00
        $this->assertEquals(1000.00, (float) $order->subtotal);
        $this->assertEquals(0.00, (float) $order->prepaid_discount);
        $this->assertEquals(1000.00, (float) $order->grand_total);
        $this->assertEquals(1000.00, (float) $order->amount_due);
        $this->assertFalse($order->hasPrepaidDiscount());
    }

    public function test_prepaid_discount_stacks_correctly_with_coupon_before_coupon_calculation(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $product = $this->createProduct(price: 1000.0);
        $cart = $this->makeCart($user, $product, 1);

        $coupon = Coupon::factory()->create([
            'code' => 'SAVE100',
            'discount_type' => 'fixed',
            'discount_value' => 100,
            'min_cart_value' => 0,
            'first_order_only' => false,
            'per_customer_limit' => 5,
        ]);

        /** @var OrderService $service */
        $service = app(OrderService::class);
        $order = $service->placeOrder($user, $cart, $this->orderData('razorpay', 'SAVE100'), clearCart: false);

        // Subtotal = 1000.00
        // Prepaid discount (2% of 1000 before coupon) = 20.00
        // Coupon discount = 100.00
        // Grand total = 1000 - 100 - 20 = 880.00
        $this->assertEquals(1000.00, (float) $order->subtotal);
        $this->assertEquals(20.00, (float) $order->prepaid_discount);
        $this->assertEquals(100.00, (float) $order->coupon_discount);
        $this->assertEquals(880.00, (float) $order->grand_total);
        $this->assertEquals(880.00, (float) $order->amount_due);
    }

    public function test_prepaid_discount_disabled_setting_gives_zero_discount(): void
    {
        Setting::updateOrCreate(
            ['key' => 'prepaid_discount_enabled'],
            ['group' => 'payment', 'value' => '0', 'type' => 'boolean']
        );

        $user = User::factory()->create(['status' => 'active']);
        $product = $this->createProduct(price: 1000.0);
        $cart = $this->makeCart($user, $product, 1);

        /** @var OrderService $service */
        $service = app(OrderService::class);
        $order = $service->placeOrder($user, $cart, $this->orderData('razorpay'), clearCart: false);

        $this->assertEquals(0.00, (float) $order->prepaid_discount);
        $this->assertEquals(1000.00, (float) $order->grand_total);
    }

    public function test_gst_taxable_and_tax_amounts_reconcile_with_prepaid_discount(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $product = $this->createProduct(price: 1000.0);
        $cart = $this->makeCart($user, $product, 1);

        /** @var OrderService $service */
        $service = app(OrderService::class);
        $order = $service->placeOrder($user, $cart, $this->orderData('razorpay'), clearCart: false);

        // Subtotal = 1000, Prepaid Discount = 20 -> Net subtotal = 980.
        // Taxable + Tax should equal Net subtotal (980) down to the paisa.
        $reconciledSum = round((float) $order->taxable_amount + (float) $order->tax_amount, 2);
        $expectedNet = round((float) $order->subtotal - (float) $order->prepaid_discount, 2);

        $this->assertEquals($expectedNet, $reconciledSum);
        $this->assertEquals((float) $order->tax_amount, round((float) $order->cgst_amount + (float) $order->sgst_amount + (float) $order->igst_amount, 2));
    }
}
