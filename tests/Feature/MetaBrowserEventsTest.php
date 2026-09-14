<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaBrowserEventsTest extends TestCase
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

    protected function seedCart(User $user, array $products): Cart
    {
        $cart = Cart::create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
        ]);

        foreach ($products as $spec) {
            $cart->items()->create([
                'product_id' => $spec['product']->id,
                'quantity' => $spec['quantity'],
                'unit_price' => $spec['product']->selling_price,
                'mrp' => $spec['product']->mrp,
                'gst_rate' => $spec['product']->gst_rate,
            ]);
        }

        return $cart;
    }

    public function test_view_content_fires_once_with_canonical_sku(): void
    {
        $this->setPixelId();

        $product = Product::factory()->active()->create([
            'sku' => 'MBC-001',
            'name' => 'VANRITI Meta Test Oil',
            'selling_price' => 499.00,
            'stock' => 8,
        ]);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString("fbq('init', 'TEST1234')", $html);
        $this->assertSame(1, substr_count($html, 'vrMeta.track(\'ViewContent\''));
        $this->assertStringContainsString('"content_ids":["MBC-001"]', $html);
        $this->assertStringContainsString('"content_name":"VANRITI Meta Test Oil"', $html);
        $this->assertStringContainsString('"contents":[{"id":"MBC-001","quantity":1,"item_price":499}]', $html);
        $this->assertStringContainsString('"value":499', $html);
        $this->assertStringContainsString('"currency":"INR"', $html);
    }

    public function test_view_content_uses_variant_sku_and_price(): void
    {
        $this->setPixelId();

        $product = Product::factory()->active()->create([
            'sku' => 'MBC-002',
            'name' => 'VANRITI Face Wash',
            'selling_price' => 120.00,
            'stock' => 10,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Charcoal 100 ml',
            'sku' => 'MBC-002-CH100',
            'selling_price' => 140.00,
            'status' => 'active',
            'is_default' => true,
            'stock' => 6,
        ]);
        $this->assertNotSame($product->sku, $variant->sku);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'vrMeta.track(\'ViewContent\''));
        $this->assertStringContainsString('"content_ids":["MBC-002-CH100"]', $html);
        $this->assertStringContainsString('"contents":[{"id":"MBC-002-CH100","quantity":1,"item_price":140}]', $html);
        $this->assertStringContainsString('"value":140', $html);
        $this->assertStringNotContainsString('"content_ids":["MBC-002"]', $html);
    }

    public function test_no_meta_scripts_when_pixel_not_configured(): void
    {
        $product = Product::factory()->active()->create([
            'sku' => 'MBC-003',
            'selling_price' => 100.00,
            'stock' => 5,
        ]);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringNotContainsString('connect.facebook.net', $html);
        $this->assertStringNotContainsString('fbq(', $html);
        $this->assertStringNotContainsString('vrMeta', $html);
    }

    public function test_ajax_add_exposes_single_authoritative_addtocart_data_for_meta(): void
    {
        $this->setPixelId();

        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'MBC-004',
            'name' => 'VANRITI Meta Serum',
            'selling_price' => 250.00,
            'stock' => 10,
        ]);

        $json = $this->actingAs($user, 'web')->postJson(route('cart.add', $product), ['quantity' => 2]);
        $json->assertOk();
        $data = $json->json();

        $this->assertSame('add_to_cart', $data['analytics']['event']);
        $this->assertSame(1, count($data['analytics']['ecommerce']['items']));
        $this->assertSame('MBC-004', $data['analytics']['ecommerce']['items'][0]['item_id']);
        $this->assertSame(2, $data['analytics']['ecommerce']['items'][0]['quantity']);
        $this->assertEqualsWithDelta(250.00, $data['analytics']['ecommerce']['items'][0]['price'], 0.01);
        $this->assertEqualsWithDelta(500.00, $data['analytics']['ecommerce']['value'], 0.01);
        $this->assertSame('INR', $data['analytics']['ecommerce']['currency']);

        // The response carries no client-side Meta calls; the browser fires
        // exactly one AddToCart through the centralised helper.
        $this->assertStringNotContainsString('fbq(', $json->content());
        $this->assertSame(1, substr_count($json->content(), 'add_to_cart'));
    }

    public function test_native_add_handoff_fires_single_meta_addtocart_on_cart_page(): void
    {
        $this->setPixelId();

        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'MBC-005',
            'name' => 'VANRITI Handoff Oil',
            'selling_price' => 350.00,
            'stock' => 10,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1]);

        $html = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'vrMeta.trackAddToCartFromGa4('));
        $this->assertStringContainsString('"item_id":"MBC-005"', $html);
        $this->assertStringContainsString('"value":350', $html);
        $this->assertSame(1, substr_count($html, '"event":"add_to_cart"'));
        $this->assertSame(1, substr_count($html, '"event":"view_cart"'));
    }

    public function test_meta_addtocart_handoff_is_consumed_on_refresh(): void
    {
        $this->setPixelId();

        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'MBC-006',
            'selling_price' => 200.00,
            'stock' => 10,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1]);

        $first = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($first, 'vrMeta.trackAddToCartFromGa4('));

        $refresh = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();
        $this->assertSame(0, substr_count($refresh, 'vrMeta.trackAddToCartFromGa4('));
    }

    public function test_checkout_page_fires_single_initiate_checkout_with_all_items(): void
    {
        $this->setPixelId();

        $user = User::factory()->create();
        $prodA = Product::factory()->active()->create([
            'sku' => 'MBC-007',
            'selling_price' => 500.00,
            'stock' => 10,
        ]);
        $prodB = Product::factory()->active()->create([
            'sku' => 'MBC-008',
            'selling_price' => 300.00,
            'stock' => 10,
        ]);
        $this->seedCart($user, [
            ['product' => $prodA, 'quantity' => 2],
            ['product' => $prodB, 'quantity' => 1],
        ]);

        $html = $this->actingAs($user, 'web')->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'vrMeta.track(\'InitiateCheckout\''));
        $this->assertStringContainsString('"content_ids":["MBC-007","MBC-008"]', $html);
        $this->assertStringContainsString('"contents":[{"id":"MBC-007","quantity":2,"item_price":500},{"id":"MBC-008","quantity":1,"item_price":300}]', $html);
        $this->assertStringContainsString('"value":1300', $html);
        $this->assertSame(1, substr_count($html, 'vrMeta.track(\'InitiateCheckout\''));
    }

    public function test_checkout_page_fires_no_meta_events_when_pixel_not_configured(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'MBC-009',
            'selling_price' => 150.00,
            'stock' => 10,
        ]);
        $this->seedCart($user, [['product' => $product, 'quantity' => 1]]);

        $html = $this->actingAs($user, 'web')->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('vrMeta', $html);
        $this->assertStringNotContainsString('fbq(', $html);
        $this->assertSame(1, substr_count($html, '"event":"begin_checkout"'));
    }

    public function test_meta_purchase_includes_content_ids(): void
    {
        $this->setPixelId();

        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'paid');

        $html = $this->actingAs($user, 'web')->get(route('checkout.success', $order))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, "fbq('track', 'Purchase'"));
        $this->assertSame(1, substr_count($html, '"content_ids":'));

        $expected = $order->items->map(fn ($item) => (string) $item->sku)->values()->all();
        $this->assertStringContainsString('"content_ids":'.json_encode($expected), $html);
    }

    protected function makeOrder(User $user, string $paymentMethod, string $paymentStatus): Order
    {
        $product = Product::factory()->active()->create([
            'sku' => 'META-'.str_pad((string) mt_rand(100, 999), 3, '0', STR_PAD_LEFT),
            'name' => 'VANRITI Order Oil',
            'selling_price' => 800.00,
            'mrp' => 800.00,
            'gst_rate' => 0,
        ]);

        $order = Order::create([
            'order_number' => 'META-0001-'.str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT),
            'user_id' => $user->id,
            'billing_name' => 'Test User',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => '42 MG Road',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => 'Test User',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '42 MG Road',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'subtotal' => 1600,
            'grand_total' => 1600,
            'amount_due' => 1600,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'order_status' => 'pending',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 2,
            'mrp' => 800.00,
            'unit_price' => 800.00,
            'gst_rate' => 0,
            'tax_amount' => 0,
            'total_price' => 1600.00,
        ]);

        return $order;
    }
}
