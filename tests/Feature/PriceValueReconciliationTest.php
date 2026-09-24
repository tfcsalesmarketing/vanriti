<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Analytics\EcommerceDataService;
use App\Services\OrderService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceValueReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    protected function setPixelId(string $id = 'TEST1234'): void
    {
        Setting::updateOrCreate(['key' => 'meta_pixel_id'], [
            'value' => $id,
            'group' => 'seo',
            'label' => 'Meta Pixel ID',
            'type' => 'text',
        ]);
    }

    protected function address(): array
    {
        return [
            'full_name' => 'Test Buyer',
            'mobile' => '9876543210',
            'address_line1' => '42 MG Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560038',
            'country' => 'India',
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

    protected function makeMultiCart(User $user, array $specs): Cart
    {
        $cart = Cart::create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
        ]);
        foreach ($specs as $spec) {
            $cart->items()->create([
                'product_id' => $spec['product']->id,
                'quantity' => $spec['qty'],
                'unit_price' => $spec['product']->selling_price,
                'mrp' => $spec['product']->mrp,
                'gst_rate' => $spec['product']->gst_rate,
            ]);
        }

        return $cart->fresh();
    }

    protected function metaPurchasePayload(string $html): ?array
    {
        if (preg_match("/fbq\('track', 'Purchase', (\{.*\}), \{eventID/", $html, $m) !== 1) {
            return null;
        }

        return json_decode($m[1], true);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 1. Purchase value == grand_total including shipping (shipping < 499 threshold)
    // ──────────────────────────────────────────────────────────────────────────
    public function test_purchase_value_equals_grand_total_with_shipping(): void
    {
        $this->setPixelId();
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'REC-001',
            'selling_price' => 200.00,
            'mrp' => 200.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $cart = $this->makeCart($user, $product, 1);
        $order = app(OrderService::class)->placeOrder($user, $cart, [
            'billing' => $this->address(),
            'shipping' => $this->address(),
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
        ]);

        // Subtotal 200 < 499 free threshold → shipping 49; grand_total = 249
        $this->assertEqualsWithDelta(200.00, $order->subtotal, 0.01);
        $this->assertEqualsWithDelta(49.00, $order->shipping_charge, 0.01);
        $this->assertEqualsWithDelta(249.00, $order->grand_total, 0.01);

        $html = $this->actingAs($user, 'web')
            ->get(route('checkout.success', $order))
            ->assertOk()
            ->getContent();

        $payload = $this->metaPurchasePayload($html);
        $this->assertNotNull($payload, 'Purchase fbq payload not found.');
        $this->assertEqualsWithDelta(249.00, $payload['value'], 0.01);
        $this->assertEqualsWithDelta(200.00, $payload['contents'][0]['item_price'], 0.01);
        $this->assertSame('INR', $payload['currency']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 2. Coupon: Purchase value reflects discounted revenue but item_price stays original
    // This locks the Phase 6 semantics: item_price = product-level price, value = actual revenue.
    // ──────────────────────────────────────────────────────────────────────────
    public function test_purchase_value_reflects_coupon_but_item_price_stays_original(): void
    {
        $this->setPixelId();
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'REC-002',
            'selling_price' => 500.00,
            'mrp' => 500.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);
        Coupon::factory()->create([
            'code' => 'FLAT50',
            'discount_type' => 'fixed',
            'discount_value' => 50,
            'min_cart_value' => 0,
            'first_order_only' => false,
            'per_customer_limit' => 99,
        ]);

        $cart = $this->makeCart($user, $product, 2);
        $order = app(OrderService::class)->placeOrder($user, $cart, [
            'billing' => $this->address(),
            'shipping' => $this->address(),
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
            'coupon_code' => 'FLAT50',
        ]);

        // Subtotal 1000 ≥ 499 → free shipping; grand_total = 1000 - 50 = 950
        $this->assertEqualsWithDelta(1000.00, $order->subtotal, 0.01);
        $this->assertEqualsWithDelta(50.00, $order->coupon_discount, 0.01);
        $this->assertEqualsWithDelta(0.00, $order->shipping_charge, 0.01);
        $this->assertEqualsWithDelta(950.00, $order->grand_total, 0.01);

        $html = $this->actingAs($user, 'web')
            ->get(route('checkout.success', $order))
            ->assertOk()
            ->getContent();

        $payload = $this->metaPurchasePayload($html);
        $this->assertNotNull($payload);

        // value = actual revenue (950, coupon subtracted)
        $this->assertEqualsWithDelta(950.00, $payload['value'], 0.01);
        // item_price = original product price (500, NOT 475)
        $this->assertEqualsWithDelta(500.00, $payload['contents'][0]['item_price'], 0.01);
        $this->assertSame(2, $payload['contents'][0]['quantity']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 3. Multi-item purchase: no item lost/duplicated; content_ids + contents correct
    // ──────────────────────────────────────────────────────────────────────────
    public function test_multi_item_purchase_reconciles_without_loss_or_duplication(): void
    {
        $this->setPixelId();
        $user = User::factory()->create();
        $prodA = Product::factory()->active()->create([
            'sku' => 'REC-003A',
            'selling_price' => 300.00,
            'mrp' => 300.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);
        $prodB = Product::factory()->active()->create([
            'sku' => 'REC-003B',
            'selling_price' => 200.00,
            'mrp' => 200.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $cart = $this->makeMultiCart($user, [
            ['product' => $prodA, 'qty' => 2],
            ['product' => $prodB, 'qty' => 1],
        ]);
        $order = app(OrderService::class)->placeOrder($user, $cart, [
            'billing' => $this->address(),
            'shipping' => $this->address(),
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
        ]);

        // 300×2 + 200×1 = 800 ≥ 499 → free shipping; grand_total = 800
        $this->assertEqualsWithDelta(800.00, $order->subtotal, 0.01);
        $this->assertEqualsWithDelta(800.00, $order->grand_total, 0.01);
        $this->assertCount(2, $order->items);

        $html = $this->actingAs($user, 'web')
            ->get(route('checkout.success', $order))
            ->assertOk()
            ->getContent();

        $payload = $this->metaPurchasePayload($html);
        $this->assertNotNull($payload);
        $this->assertEqualsWithDelta(800.00, $payload['value'], 0.01);

        $skus = array_column($payload['contents'], 'id');
        $this->assertSame(['REC-003A', 'REC-003B'], $skus);

        $this->assertCount(2, $payload['content_ids']);
        $this->assertSame($skus, $payload['content_ids']);

        $prices = array_column($payload['contents'], 'item_price');
        $this->assertEqualsWithDelta(300.00, $prices[0], 0.01);
        $this->assertEqualsWithDelta(200.00, $prices[1], 0.01);

        $qtys = array_column($payload['contents'], 'quantity');
        $this->assertSame([2, 1], $qtys);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 4. InitiateCheckout value = merchandise only (no coupon, no shipping)
    //    Purchase value = grand_total (includes shipping, subtracts coupon).
    // ──────────────────────────────────────────────────────────────────────────
    public function test_initiate_checkout_value_is_merchandise_only_with_shipping_and_coupon_in_order(): void
    {
        $this->setPixelId();
        $user = User::factory()->create();
        $prodA = Product::factory()->active()->create([
            'sku' => 'REC-004A',
            'selling_price' => 200.00,
            'mrp' => 200.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);
        $prodB = Product::factory()->active()->create([
            'sku' => 'REC-004B',
            'selling_price' => 150.00,
            'mrp' => 150.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);
        Coupon::factory()->create([
            'code' => 'SAVE25',
            'discount_type' => 'fixed',
            'discount_value' => 25,
            'min_cart_value' => 0,
            'first_order_only' => false,
            'per_customer_limit' => 99,
        ]);

        $cart = $this->makeMultiCart($user, [
            ['product' => $prodA, 'qty' => 1],
            ['product' => $prodB, 'qty' => 1],
        ]);

        // Checkout page renders with merchandise value (no coupon/session applied)
        $checkoutHtml = $this->actingAs($user, 'web')
            ->get(route('checkout.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('InitiateCheckout', $checkoutHtml);
        $this->assertStringContainsString('"content_ids":["REC-004A","REC-004B"]', $checkoutHtml);
        // InitiateCheckout.value = 350 (merchandise only)
        $this->assertStringContainsString('"value":350', $checkoutHtml);

        // Place order with coupon + shipping
        $order = app(OrderService::class)->placeOrder($user, $cart, [
            'billing' => $this->address(),
            'shipping' => $this->address(),
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
            'coupon_code' => 'SAVE25',
        ]);

        // 350 < 499 → shipping 49; grand_total = 350 + 49 - 25 = 374
        $this->assertEqualsWithDelta(350.00, $order->subtotal, 0.01);
        $this->assertEqualsWithDelta(49.00, $order->shipping_charge, 0.01);
        $this->assertEqualsWithDelta(25.00, $order->coupon_discount, 0.01);
        $this->assertEqualsWithDelta(374.00, $order->grand_total, 0.01);

        $successHtml = $this->actingAs($user, 'web')
            ->get(route('checkout.success', $order))
            ->assertOk()
            ->getContent();

        $payload = $this->metaPurchasePayload($successHtml);
        $this->assertNotNull($payload);
        // Purchase.value = 374 (includes shipping, subtracts coupon)
        $this->assertEqualsWithDelta(374.00, $payload['value'], 0.01);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 5. Decimal prices: no float drift, rounding to 2dp
    // ──────────────────────────────────────────────────────────────────────────
    public function test_decimal_price_rounding_no_float_drift(): void
    {
        $this->setPixelId();
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'REC-005',
            'selling_price' => 49.99,
            'mrp' => 49.99,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $cart = $this->makeCart($user, $product, 3);
        $order = app(OrderService::class)->placeOrder($user, $cart, [
            'billing' => $this->address(),
            'shipping' => $this->address(),
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
        ]);

        $this->assertEqualsWithDelta(149.97, $order->subtotal, 0.01);
        $this->assertEqualsWithDelta(198.97, $order->grand_total, 0.01);
        $this->assertSame(149.97, round($order->subtotal, 2));

        $html = $this->actingAs($user, 'web')
            ->get(route('checkout.success', $order))
            ->assertOk()
            ->getContent();

        $payload = $this->metaPurchasePayload($html);
        $this->assertNotNull($payload);
        $this->assertEqualsWithDelta(198.97, $payload['value'], 0.01);
        $this->assertEqualsWithDelta(49.99, $payload['contents'][0]['item_price'], 0.01);
        $this->assertSame(3, $payload['contents'][0]['quantity']);

        // No floating-point artefact
        $this->assertStringNotContainsString('99999', $html);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 6. Price changed after order creation: Purchase payload stays historical
    // ──────────────────────────────────────────────────────────────────────────
    public function test_purchase_immune_to_later_price_change(): void
    {
        $this->setPixelId();
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'REC-006',
            'selling_price' => 300.00,
            'mrp' => 300.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $cart = $this->makeCart($user, $product, 1);
        $order = app(OrderService::class)->placeOrder($user, $cart, [
            'billing' => $this->address(),
            'shipping' => $this->address(),
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
        ]);

        // Mutate the product after order is placed
        $product->update(['selling_price' => 999.00, 'sku' => 'REC-006-X']);
        $this->assertEqualsWithDelta(999.00, $product->fresh()->selling_price, 0.01);

        // Order still carries historical snapshot
        $orderItem = $order->fresh()->items()->first();
        $this->assertSame('REC-006', $orderItem->sku);
        $this->assertEqualsWithDelta(300.00, $orderItem->unit_price, 0.01);

        $html = $this->actingAs($user, 'web')
            ->get(route('checkout.success', $order))
            ->assertOk()
            ->getContent();

        $payload = $this->metaPurchasePayload($html);
        $this->assertNotNull($payload);
        $this->assertEqualsWithDelta(300.00, $payload['contents'][0]['item_price'], 0.01);
        $this->assertSame('REC-006', $payload['contents'][0]['id']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 7. GST-inclusive: tax embedded in price, NOT added on top
    // ──────────────────────────────────────────────────────────────────────────
    public function test_gst_is_embedded_in_value_not_added_on_top(): void
    {
        $this->setPixelId();
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'REC-007',
            'selling_price' => 600.00,
            'mrp' => 600.00,
            'gst_rate' => 18.0,
            'stock' => 10,
        ]);

        $cart = $this->makeCart($user, $product, 1);
        $order = app(OrderService::class)->placeOrder($user, $cart, [
            'billing' => $this->address(),
            'shipping' => $this->address(),
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
        ]);

        // 600 ≥ 499 → free shipping; grand_total = 600 (GST-inclusive, not 600 + tax)
        $this->assertEqualsWithDelta(600.00, $order->subtotal, 0.01);
        $this->assertEqualsWithDelta(600.00, $order->grand_total, 0.01);

        // GST is informational only (for GST filing)
        $expectedTax = round(600.00 - round(600.00 / 1.18, 2), 2);
        $this->assertEqualsWithDelta($expectedTax, $order->tax_amount, 0.01);
        $this->assertGreaterThan(0, $order->tax_amount);

        // GA4 purchase: value = grand_total (no + tax)
        $ga4 = app(EcommerceDataService::class)->purchase($order);
        $this->assertEqualsWithDelta(600.00, $ga4['ecommerce']['value'], 0.01);
        $this->assertEqualsWithDelta($expectedTax, $ga4['ecommerce']['tax'], 0.01);
        $this->assertSame('INR', $ga4['ecommerce']['currency']);

        // Browser Meta Purchase: value = grand_total (NOT 600 + tax)
        $html = $this->actingAs($user, 'web')
            ->get(route('checkout.success', $order))
            ->assertOk()
            ->getContent();

        $payload = $this->metaPurchasePayload($html);
        $this->assertNotNull($payload);
        $this->assertEqualsWithDelta(600.00, $payload['value'], 0.01);
        $this->assertEqualsWithDelta(600.00, $payload['contents'][0]['item_price'], 0.01);
    }
}
