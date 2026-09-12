<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Refund;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\Analytics\EcommerceDataService;
use App\Services\OrderService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcommerceDataServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_product_maps_to_ga4_item(): void
    {
        $parent = Category::factory()->create(['name' => 'Personal Care']);
        $category = Category::factory()->create(['name' => 'Skincare', 'parent_id' => $parent->id]);

        $product = Product::factory()->active()->create([
            'sku' => 'VNRT049',
            'name' => 'VANRITI Neem Powder',
            'selling_price' => 99.00,
            'mrp' => 199.00,
        ]);
        $product->categories()->syncWithoutDetaching([$category->id => ['is_primary' => true]]);

        $item = app(EcommerceDataService::class)->item($product, null, 2);

        $this->assertSame('VNRT049', $item['item_id']);
        $this->assertSame('VANRITI Neem Powder', $item['item_name']);
        $this->assertSame('VANRITI', $item['item_brand']);
        $this->assertSame('Skincare', $item['item_category']);
        $this->assertSame('Personal Care', $item['item_category2']);
        $this->assertEqualsWithDelta(99.00, $item['price'], 0.01);
        $this->assertSame(2, $item['quantity']);
        $this->assertArrayNotHasKey('item_variant', $item);
    }

    public function test_product_with_selected_variant_uses_variant_data(): void
    {
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT001',
            'name' => 'VANRITI Face Wash',
            'selling_price' => 120.00,
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Tea Tree 150 ml',
            'sku' => 'VNRT001-TT150',
            'selling_price' => 140.00,
            'status' => 'active',
        ]);

        $item = app(EcommerceDataService::class)->item($product, $variant, 1);

        $this->assertSame('VNRT001-TT150', $item['item_id']);
        $this->assertSame('Tea Tree 150 ml', $item['item_variant']);
        $this->assertEqualsWithDelta(140.00, $item['price'], 0.01);
    }

    public function test_product_with_variants_and_no_selection_uses_default_variant(): void
    {
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT002',
            'selling_price' => 90.00,
        ]);

        $default = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Rose 100 ml',
            'sku' => 'VNRT002-ROSE',
            'selling_price' => 95.00,
            'is_default' => true,
            'status' => 'active',
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Charcoal 100 ml',
            'sku' => 'VNRT002-CHAR',
            'selling_price' => 97.00,
            'status' => 'active',
        ]);

        $item = app(EcommerceDataService::class)->item($product, null, 1);

        $this->assertSame('VNRT002-ROSE', $item['item_id']);
        $this->assertSame('Rose 100 ml', $item['item_variant']);
        $this->assertEqualsWithDelta(95.00, $item['price'], 0.01);
        $this->assertSame($default->id, $product->activeVariants->first(fn ($v) => $v->is_default)->id);
    }

    public function test_cart_item_uses_resolved_price_and_variant_sku(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT003',
            'name' => 'VANRITI Hair Oil',
            'selling_price' => 150.00,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Bhringraj 200 ml',
            'sku' => 'VNRT003-BH200',
            'selling_price' => 180.00,
            'status' => 'active',
        ]);

        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => 180.00,
            'mrp' => 250.00,
            'gst_rate' => 5,
        ]);

        $items = app(EcommerceDataService::class)->fromCart($cart);

        $this->assertCount(1, $items);
        $this->assertSame('VNRT003-BH200', $items[0]['item_id']);
        $this->assertSame('VANRITI Hair Oil', $items[0]['item_name']);
        $this->assertSame('Bhringraj 200 ml', $items[0]['item_variant']);
        $this->assertEqualsWithDelta(180.00, $items[0]['price'], 0.01);
        $this->assertSame(2, $items[0]['quantity']);
    }

    public function test_purchase_payload_uses_persisted_order_and_items(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT005',
            'name' => 'VANRITI Green Tea',
            'selling_price' => 300.00,
            'mrp' => 300.00,
            'gst_rate' => 5,
            'stock' => 10,
        ]);

        $cart = $this->makeCart($user, $product, 2);
        $order = app(OrderService::class)->placeOrder($user, $cart, $this->orderData());

        $service = app(EcommerceDataService::class);
        $payload = $service->purchase($order);

        $this->assertSame('purchase', $payload['event']);
        $this->assertSame($order->order_number, $payload['ecommerce']['transaction_id']);
        $this->assertEqualsWithDelta((float) $order->grand_total, $payload['ecommerce']['value'], 0.01);
        $this->assertEqualsWithDelta((float) $order->tax_amount, $payload['ecommerce']['tax'], 0.01);
        $this->assertEqualsWithDelta((float) $order->shipping_charge, $payload['ecommerce']['shipping'], 0.01);
        $this->assertSame('INR', $payload['ecommerce']['currency']);
        $this->assertArrayNotHasKey('coupon', $payload['ecommerce']);

        $this->assertCount(1, $payload['ecommerce']['items']);
        $item = $payload['ecommerce']['items'][0];
        $this->assertSame('VNRT005', $item['item_id']);
        $this->assertSame('VANRITI Green Tea', $item['item_name']);
        $this->assertSame('VANRITI', $item['item_brand']);
        $this->assertEqualsWithDelta(300.00, $item['price'], 0.01);
        $this->assertSame(2, $item['quantity']);
    }

    public function test_purchase_payload_includes_coupon_and_shipping(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT006',
            'selling_price' => 200.00,
            'mrp' => 200.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $coupon = Coupon::factory()->create([
            'code' => 'FLAT50',
            'discount_type' => 'fixed',
            'discount_value' => 50,
            'min_cart_value' => 0,
            'first_order_only' => false,
            'per_customer_limit' => 5,
        ]);

        $cart = $this->makeCart($user, $product, 1);
        $data = $this->orderData();
        $data['coupon_code'] = 'FLAT50';

        $order = app(OrderService::class)->placeOrder($user, $cart, $data);

        $payload = app(EcommerceDataService::class)->purchase($order);

        $this->assertSame('FLAT50', $payload['ecommerce']['coupon']);
        // 200 + 49 shipping - 50 coupon = 199 payable
        $this->assertEqualsWithDelta(199.00, $payload['ecommerce']['value'], 0.01);
        $this->assertEqualsWithDelta(49.00, $payload['ecommerce']['shipping'], 0.01);
    }

    public function test_cod_order_is_purchase_eligible(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 500.00,
            'mrp' => 500.00,
            'gst_rate' => 5,
            'stock' => 10,
        ]);

        $order = app(OrderService::class)->placeOrder($user, $this->makeCart($user, $product, 1), $this->orderData());

        $this->assertSame('cod', $order->payment_method);
        $this->assertTrue(app(EcommerceDataService::class)->purchaseEligible($order));
    }

    public function test_razorpay_order_only_eligible_once_paid(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 500.00,
            'mrp' => 500.00,
            'gst_rate' => 5,
            'stock' => 10,
        ]);

        $data = $this->orderData();
        $data['payment_method'] = 'razorpay';

        $order = app(OrderService::class)->placeOrder($user, $this->makeCart($user, $product, 1), $data);

        $service = app(EcommerceDataService::class);

        // Razorpay order/session created but payment not yet verified -> not a purchase.
        $this->assertSame('pending', $order->payment_status);
        $this->assertFalse($service->purchaseEligible($order));

        $order->update(['payment_status' => 'paid']);

        $this->assertTrue($service->purchaseEligible($order->fresh()));
    }

    public function test_purchase_payload_uses_historical_snapshot_not_current_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT0LD01',
            'name' => 'VANRITI Legacy Oil',
            'selling_price' => 100.00,
            'mrp' => 100.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $order = app(OrderService::class)->placeOrder($user, $this->makeCart($user, $product, 1), $this->orderData());

        // Product changes after purchase must never mutate the purchase payload.
        $product->update(['sku' => 'VNRTNEW01', 'name' => 'VANRITI Renamed Oil', 'selling_price' => 999.00]);

        $payload = app(EcommerceDataService::class)->purchase($order->fresh());

        $item = $payload['ecommerce']['items'][0];
        $this->assertSame('VNRT0LD01', $item['item_id']);
        $this->assertSame('VANRITI Legacy Oil', $item['item_name']);
        $this->assertEqualsWithDelta(100.00, $item['price'], 0.01);
        $this->assertSame(1, $item['quantity']);
        $this->assertArrayNotHasKey('mrp', $item);
    }

    public function test_purchase_payload_contains_no_pii(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT0021',
            'name' => 'VANRITI Rose Water',
            'selling_price' => 250.00,
            'mrp' => 250.00,
            'gst_rate' => 5,
            'stock' => 10,
        ]);

        $order = app(OrderService::class)->placeOrder($user, $this->makeCart($user, $product, 2), $this->orderData());

        $payload = app(EcommerceDataService::class)->purchase($order->fresh());

        $keys = [];
        array_walk_recursive($payload, function ($value, $key) use (&$keys) {
            if (is_string($key)) {
                $keys[] = $key;
            }
        });

        foreach (['email', 'phone', 'mobile', 'address', 'city', 'state', 'pincode', 'user_id', 'product_id'] as $forbidden) {
            $this->assertNotContains($forbidden, $keys);
        }
    }

    public function test_purchase_ineligible_for_razorpay_while_processing(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 500.00,
            'mrp' => 500.00,
            'gst_rate' => 5,
            'stock' => 10,
        ]);

        $data = $this->orderData();
        $data['payment_method'] = 'razorpay';

        $order = app(OrderService::class)->placeOrder($user, $this->makeCart($user, $product, 1), $data);
        $order->update(['payment_status' => 'processing']);

        $this->assertFalse(app(EcommerceDataService::class)->purchaseEligible($order->fresh()));
    }

    public function test_purchase_ineligible_for_failed_and_cancelled_orders(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 500.00,
            'mrp' => 500.00,
            'gst_rate' => 5,
            'stock' => 10,
        ]);

        $order = app(OrderService::class)->placeOrder($user, $this->makeCart($user, $product, 1), $this->orderData());
        $service = app(EcommerceDataService::class);

        $order->update(['payment_method' => 'razorpay', 'payment_status' => 'failed']);
        $this->assertFalse($service->purchaseEligible($order->fresh()));

        $order->update(['payment_status' => 'paid']);
        $order->update(['order_status' => 'cancelled']);
        $this->assertFalse($service->purchaseEligible($order->fresh()));

        $order->update(['order_status' => 'failed']);
        $this->assertFalse($service->purchaseEligible($order->fresh()));
    }

    public function test_view_item_payload_shape(): void
    {
        $category = Category::factory()->create(['name' => 'Haircare']);
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT010',
            'name' => 'VANRITI Hair Oil',
            'selling_price' => 180.00,
            'mrp' => 220.00,
        ]);
        $product->categories()->syncWithoutDetaching([$category->id => ['is_primary' => true]]);

        $payload = app(EcommerceDataService::class)->viewItem($product, null, 1);

        $this->assertSame('view_item', $payload['event']);
        $this->assertSame('INR', $payload['ecommerce']['currency']);
        $this->assertEqualsWithDelta(180.00, $payload['ecommerce']['value'], 0.01);
        $this->assertCount(1, $payload['ecommerce']['items']);

        $item = $payload['ecommerce']['items'][0];
        $this->assertSame('VNRT010', $item['item_id']);
        $this->assertSame('VANRITI Hair Oil', $item['item_name']);
        $this->assertSame('VANRITI', $item['item_brand']);
        $this->assertSame('Haircare', $item['item_category']);
        $this->assertEqualsWithDelta(180.00, $item['price'], 0.01);
        $this->assertSame(1, $item['quantity']);
    }

    public function test_view_item_uses_selected_variant(): void
    {
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT011',
            'selling_price' => 100.00,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Charcoal 50 g',
            'sku' => 'VNRT011-CH50',
            'selling_price' => 110.00,
            'status' => 'active',
        ]);

        $payload = app(EcommerceDataService::class)->viewItem($product, $variant, 1);

        $this->assertSame('VNRT011-CH50', $payload['ecommerce']['items'][0]['item_id']);
        $this->assertSame('Charcoal 50 g', $payload['ecommerce']['items'][0]['item_variant']);
        $this->assertEqualsWithDelta(110.00, $payload['ecommerce']['value'], 0.01);
    }

    public function test_add_to_cart_payload_shape(): void
    {
        $parent = Category::factory()->create(['name' => 'Personal Care']);
        $category = Category::factory()->create(['name' => 'Skincare', 'parent_id' => $parent->id]);

        $product = Product::factory()->active()->create([
            'sku' => 'VNRT012',
            'name' => 'VANRITI Glow Serum',
            'selling_price' => 499.00,
            'mrp' => 699.00,
        ]);
        $product->categories()->syncWithoutDetaching([$category->id => ['is_primary' => true]]);

        $payload = app(EcommerceDataService::class)->addToCart($product, null, 2);

        $this->assertSame('add_to_cart', $payload['event']);
        $this->assertSame('INR', $payload['ecommerce']['currency']);
        $this->assertEqualsWithDelta(998.00, $payload['ecommerce']['value'], 0.01);
        $this->assertCount(1, $payload['ecommerce']['items']);

        $item = $payload['ecommerce']['items'][0];
        $this->assertSame('VNRT012', $item['item_id']);
        $this->assertSame('VANRITI Glow Serum', $item['item_name']);
        $this->assertSame('VANRITI', $item['item_brand']);
        $this->assertSame('Skincare', $item['item_category']);
        $this->assertSame('Personal Care', $item['item_category2']);
        $this->assertEqualsWithDelta(499.00, $item['price'], 0.01);
        $this->assertSame(2, $item['quantity']);
        $this->assertArrayNotHasKey('item_variant', $item);
        $this->assertArrayNotHasKey('mrp', $item);
        $this->assertArrayNotHasKey('id', $item);
        $this->assertArrayNotHasKey('product_id', $item);
    }

    public function test_add_to_cart_with_variant_uses_variant_sku_and_price(): void
    {
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT013',
            'selling_price' => 300.00,
            'mrp' => 300.00,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Tub 250 g',
            'sku' => 'VNRT013-TUB250',
            'selling_price' => 350.00,
            'status' => 'active',
        ]);

        $payload = app(EcommerceDataService::class)->addToCart($product, $variant, 3);

        $this->assertSame('add_to_cart', $payload['event']);
        $this->assertEqualsWithDelta(1050.00, $payload['ecommerce']['value'], 0.01);

        $item = $payload['ecommerce']['items'][0];
        $this->assertSame('VNRT013-TUB250', $item['item_id']);
        $this->assertSame('Tub 250 g', $item['item_variant']);
        $this->assertEqualsWithDelta(350.00, $item['price'], 0.01);
        $this->assertSame(3, $item['quantity']);
    }

    public function test_view_cart_payload_maps_all_items_and_subtotal(): void
    {
        $user = User::factory()->create();

        $plain = Product::factory()->active()->create([
            'sku' => 'VNRT014',
            'name' => 'VANRITI Aloe Gel',
            'selling_price' => 100.00,
        ]);
        $variantProduct = Product::factory()->active()->create([
            'sku' => 'VNRT015',
            'name' => 'VANRITI Face Wash',
            'selling_price' => 150.00,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $variantProduct->id,
            'name' => 'Tea Tree 150 ml',
            'sku' => 'VNRT015-TT150',
            'selling_price' => 180.00,
            'status' => 'active',
        ]);

        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $plain->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'mrp' => 150.00,
            'gst_rate' => 5,
        ]);
        $cart->items()->create([
            'product_id' => $variantProduct->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'unit_price' => 180.00,
            'mrp' => 250.00,
            'gst_rate' => 5,
        ]);

        $payload = app(EcommerceDataService::class)->viewCart($cart->fresh());

        $this->assertSame('view_cart', $payload['event']);
        $this->assertSame('INR', $payload['ecommerce']['currency']);
        $this->assertEqualsWithDelta(740.00, $payload['ecommerce']['value'], 0.01);
        $this->assertCount(2, $payload['ecommerce']['items']);

        $bySku = collect($payload['ecommerce']['items'])->keyBy('item_id');
        $this->assertEqualsWithDelta(100.00, $bySku['VNRT014']['price'], 0.01);
        $this->assertSame(2, $bySku['VNRT014']['quantity']);
        $this->assertEqualsWithDelta(180.00, $bySku['VNRT015-TT150']['price'], 0.01);
        $this->assertSame('Tea Tree 150 ml', $bySku['VNRT015-TT150']['item_variant']);
        $this->assertSame(3, $bySku['VNRT015-TT150']['quantity']);
    }

    public function test_begin_checkout_payload_maps_cart_and_has_no_pii(): void
    {
        $user = User::factory()->create();
        $parent = Category::factory()->create(['name' => 'Personal Care']);
        $category = Category::factory()->create(['name' => 'Skincare', 'parent_id' => $parent->id]);

        $product = Product::factory()->active()->create([
            'sku' => 'VNRT016',
            'name' => 'VANRITI Turmeric Soap',
            'selling_price' => 120.00,
            'mrp' => 160.00,
        ]);
        $product->categories()->syncWithoutDetaching([$category->id => ['is_primary' => true]]);

        $variantProduct = Product::factory()->active()->create([
            'sku' => 'VNRT017',
            'name' => 'VANRITI Shampoo',
            'selling_price' => 200.00,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $variantProduct->id,
            'name' => 'Onion 150 ml',
            'sku' => 'VNRT017-ON150',
            'selling_price' => 230.00,
            'status' => 'active',
        ]);

        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 120.00,
            'mrp' => 160.00,
            'gst_rate' => 5,
        ]);
        $cart->items()->create([
            'product_id' => $variantProduct->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 230.00,
            'mrp' => 280.00,
            'gst_rate' => 5,
        ]);

        $payload = app(EcommerceDataService::class)->beginCheckout($cart->fresh());

        $this->assertSame('begin_checkout', $payload['event']);
        $this->assertSame('INR', $payload['ecommerce']['currency']);
        $this->assertEqualsWithDelta(470.00, $payload['ecommerce']['value'], 0.01);
        $this->assertCount(2, $payload['ecommerce']['items']);
        $this->assertArrayNotHasKey('shipping_tier', $payload['ecommerce']);
        $this->assertArrayNotHasKey('payment_type', $payload['ecommerce']);

        $bySku = collect($payload['ecommerce']['items'])->keyBy('item_id');
        $this->assertSame('VNRT016', $bySku['VNRT016']['item_id']);
        $this->assertSame('VANRITI Turmeric Soap', $bySku['VNRT016']['item_name']);
        $this->assertSame('Skincare', $bySku['VNRT016']['item_category']);
        $this->assertSame('Personal Care', $bySku['VNRT016']['item_category2']);
        $this->assertEqualsWithDelta(120.00, $bySku['VNRT016']['price'], 0.01);
        $this->assertSame(2, $bySku['VNRT016']['quantity']);

        $this->assertSame('VNRT017-ON150', $bySku['VNRT017-ON150']['item_id']);
        $this->assertSame('Onion 150 ml', $bySku['VNRT017-ON150']['item_variant']);
        $this->assertEqualsWithDelta(230.00, $bySku['VNRT017-ON150']['price'], 0.01);
        $this->assertSame(1, $bySku['VNRT017-ON150']['quantity']);

        foreach ($payload['ecommerce']['items'] as $item) {
            foreach (['id', 'product_id', 'variant_id', 'mrp', 'user_id', 'email', 'phone', 'address'] as $forbidden) {
                $this->assertArrayNotHasKey($forbidden, $item);
            }
        }
    }

    public function test_add_shipping_info_payload_includes_shipping_tier_when_provided(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT018',
            'name' => 'VANRITI Cleanser',
            'selling_price' => 150.00,
            'mrp' => 150.00,
        ]);

        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 150.00,
            'mrp' => 150.00,
            'gst_rate' => 5,
        ]);

        $service = app(EcommerceDataService::class);

        $withTier = $service->addShippingInfo($cart->fresh(), 'express');
        $this->assertSame('add_shipping_info', $withTier['event']);
        $this->assertSame('INR', $withTier['ecommerce']['currency']);
        $this->assertEqualsWithDelta(450.00, $withTier['ecommerce']['value'], 0.01);
        $this->assertSame('express', $withTier['ecommerce']['shipping_tier']);
        $this->assertSame(3, $withTier['ecommerce']['items'][0]['quantity']);
        $this->assertArrayNotHasKey('payment_type', $withTier['ecommerce']);
        $this->assertArrayNotHasKey('name', $withTier['ecommerce']);
        $this->assertArrayNotHasKey('shipping_address', $withTier['ecommerce']);

        $withoutTier = $service->addShippingInfo($cart->fresh());
        $this->assertArrayNotHasKey('shipping_tier', $withoutTier['ecommerce']);
    }

    public function test_add_payment_info_payload_for_razorpay_uses_canonical_type(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT019',
            'name' => 'VANRITI Serum',
            'selling_price' => 500.00,
            'mrp' => 500.00,
            'gst_rate' => 5,
            'stock' => 10,
        ]);

        $order = app(OrderService::class)->placeOrder($user, $this->makeCart($user, $product, 2), $this->orderData());

        $payload = app(EcommerceDataService::class)->addPaymentInfo($order, 'razorpay');

        $this->assertSame('add_payment_info', $payload['event']);
        $this->assertSame('razorpay', $payload['ecommerce']['payment_type']);
        $this->assertSame('INR', $payload['ecommerce']['currency']);
        $this->assertEqualsWithDelta(1000.00, $payload['ecommerce']['value'], 0.01);
        $this->assertCount(1, $payload['ecommerce']['items']);
        $this->assertSame('VNRT019', $payload['ecommerce']['items'][0]['item_id']);
        $this->assertEqualsWithDelta(500.00, $payload['ecommerce']['items'][0]['price'], 0.01);
        $this->assertSame(2, $payload['ecommerce']['items'][0]['quantity']);
    }

    public function test_add_payment_info_payload_for_cod_uses_canonical_type(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT020',
            'name' => 'VANRITI Hair Oil',
            'selling_price' => 250.00,
            'mrp' => 250.00,
            'gst_rate' => 5,
            'stock' => 10,
        ]);

        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 250.00,
            'mrp' => 250.00,
            'gst_rate' => 5,
        ]);

        $payload = app(EcommerceDataService::class)->addPaymentInfo($cart->fresh(), 'cod');

        $this->assertSame('add_payment_info', $payload['event']);
        $this->assertSame('cod', $payload['ecommerce']['payment_type']);
        $this->assertEqualsWithDelta(250.00, $payload['ecommerce']['value'], 0.01);
        $this->assertSame('VNRT020', $payload['ecommerce']['items'][0]['item_id']);
        foreach (['id', 'product_id', 'user_id', 'email', 'phone', 'address', 'pincode', 'city', 'state'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $payload['ecommerce']);
        }
    }

    public function test_refund_payload_maps_amount_and_returned_items(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT007',
            'name' => 'VANRITI Cleanse Bar',
            'selling_price' => 250.00,
            'mrp' => 250.00,
            'gst_rate' => 5,
            'stock' => 10,
        ]);

        $order = app(OrderService::class)->placeOrder($user, $this->makeCart($user, $product, 2), $this->orderData());
        $orderItem = $order->items()->first();

        $returnRequest = ReturnRequest::create([
            'return_number' => 'RET-2026-000001',
            'order_id' => $order->id,
            'user_id' => $user->id,
            'reason' => 'Not as described',
            'status' => 'approved',
            'requested_at' => now(),
        ]);

        ReturnItem::create([
            'return_request_id' => $returnRequest->id,
            'order_item_id' => $orderItem->id,
            'quantity' => 1,
            'refund_amount' => 250.00,
        ]);

        $refund = Refund::create([
            'refund_number' => 'REF-2026-000001',
            'return_request_id' => $returnRequest->id,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'amount' => 250.00,
            'type' => 'partial',
            'status' => 'completed',
            'requested_at' => now(),
            'processed_at' => now(),
        ]);

        $payload = app(EcommerceDataService::class)->refund($refund);

        $this->assertSame('refund', $payload['event']);
        $this->assertSame($order->order_number, $payload['ecommerce']['transaction_id']);
        $this->assertEqualsWithDelta(250.00, $payload['ecommerce']['value'], 0.01);
        $this->assertSame('INR', $payload['ecommerce']['currency']);

        $this->assertCount(1, $payload['ecommerce']['items']);
        $this->assertSame('VNRT007', $payload['ecommerce']['items'][0]['item_id']);
        $this->assertSame(-1, $payload['ecommerce']['items'][0]['quantity']);
        $this->assertEqualsWithDelta(250.00, $payload['ecommerce']['items'][0]['price'], 0.01);
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
}