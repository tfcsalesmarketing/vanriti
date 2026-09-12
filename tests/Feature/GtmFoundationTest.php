<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GtmFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        config(['analytics.gtm_container_id' => 'GTM-DEMO123']);
    }

    public function test_gtm_is_rendered_when_container_id_is_configured(): void
    {
        $product = $this->makeProduct();

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringContainsString('https://www.googletagmanager.com/gtm.js', $html);
        $this->assertStringContainsString('https://www.googletagmanager.com/ns.html?id=GTM-DEMO123', $html);
        $this->assertStringContainsString("'dataLayer','GTM-DEMO123'", $html);
    }

    public function test_gtm_is_absent_when_container_id_is_empty(): void
    {
        config(['analytics.gtm_container_id' => '']);

        $product = $this->makeProduct();

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringNotContainsString('googletagmanager.com', $html);
        $this->assertStringNotContainsString('gtm.js', $html);
        $this->assertStringNotContainsString('ns.html', $html);
    }

    public function test_configured_container_id_is_safely_escaped_when_rendered(): void
    {
        config(['analytics.gtm_container_id' => 'GTM-\'"><script>alert(1)</script>']);

        $product = $this->makeProduct();

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_exactly_one_gtm_bootstrap_is_rendered(): void
    {
        $product = $this->makeProduct();

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'googletagmanager.com/gtm.js'));
        $this->assertSame(1, substr_count($html, 'googletagmanager.com/ns.html'));
    }

    public function test_existing_data_layer_initialisation_remains_and_is_not_duplicated(): void
    {
        $product = $this->makeProduct();

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'window.dataLayer = window.dataLayer || [];'));
        $this->assertStringNotContainsString('vanritiDataLayer', $html);
    }

    public function test_view_item_is_still_fired_with_gtm_enabled(): void
    {
        $category = Category::factory()->create(['name' => 'Skincare']);
        $product = $this->makeProduct(['sku' => 'VNRT071', 'name' => 'VANRITI Calm Cream', 'selling_price' => 250.00, 'mrp' => 250.00]);
        $product->categories()->syncWithoutDetaching([$category->id => ['is_primary' => true]]);

        $html = $this->get(route('product.show', $product))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"view_item"'));
        $this->assertStringContainsString('"item_id":"VNRT071"', $html);
        $this->assertStringContainsString('"item_category":"Skincare"', $html);
        $this->assertStringContainsString('"currency":"INR"', $html);
    }

    public function test_add_to_cart_is_still_fired_exactly_once_with_gtm_enabled(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT072',
            'name' => 'VANRITI Cleanser',
            'selling_price' => 150.00,
            'mrp' => 150.00,
            'stock' => 10,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1])->assertRedirect(route('cart.index'));

        $html = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"event":"add_to_cart"'));
        $this->assertSame(1, substr_count($html, '"event":"view_cart"'));
        $this->assertStringContainsString('"item_id":"VNRT072"', $html);
    }

    public function test_view_cart_is_still_fired_with_gtm_enabled(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT073',
            'name' => 'VANRITI Bath Oil',
            'selling_price' => 200.00,
            'mrp' => 200.00,
            'stock' => 10,
        ]);

        $this->seedCart($user, $product, 2);

        $html = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"view_cart"'));
        $this->assertStringContainsString('"item_id":"VNRT073"', $html);
    }

    public function test_begin_checkout_is_still_fired_with_gtm_enabled(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT074',
            'name' => 'VANRITI Toner',
            'selling_price' => 180.00,
            'mrp' => 180.00,
            'stock' => 10,
        ]);

        $this->seedCart($user, $product, 1);

        $html = $this->actingAs($user, 'web')->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"begin_checkout"'));
        $this->assertStringContainsString('"event":"begin_checkout"', $html);
        $this->assertStringContainsString('"currency":"INR"', $html);
    }

    public function test_shipping_and_payment_info_events_are_still_fired_with_gtm_enabled(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'pending');

        $html = $this->actingAs($user, 'web')->get(route('checkout.success', $order))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"add_shipping_info"'));
        $this->assertSame(1, substr_count($html, '"add_payment_info"'));
        $this->assertStringContainsString('"payment_type":"cod"', $html);
    }

    public function test_purchase_event_and_transaction_id_are_still_present_with_gtm_enabled(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'pending');

        $html = $this->actingAs($user, 'web')->get(route('checkout.success', $order))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"event":"purchase"'));
        $this->assertStringContainsString('"transaction_id":"'.$order->order_number.'"', $html);
    }

    public function test_no_pii_is_introduced_into_analytics_payloads_with_gtm_enabled(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT076',
            'name' => 'VANRITI Mask',
            'selling_price' => 300.00,
            'mrp' => 300.00,
            'stock' => 10,
        ]);

        $this->seedCart($user, $product, 1);

        $checkoutContent = $this->actingAs($user, 'web')->get(route('checkout.index'))->getContent();
        preg_match('/window\.vrCheckoutAnalytics\s*=\s*(\{.*?\});/', $checkoutContent, $matches);
        $this->assertNotEmpty($matches);
        $base = json_decode($matches[1], true);
        $this->assertIsArray($base);

        $keys = [];
        array_walk_recursive($base, function ($value, $key) use (&$keys) {
            if (is_string($key)) {
                $keys[] = $key;
            }
        });
        $this->assertNotContains('email', $keys);
        $this->assertNotContains('mobile', $keys);
        $this->assertNotContains('address', $keys);
        $this->assertNotContains('pincode', $keys);
        $this->assertNotContains('user_id', $keys);

        $successContent = $this->actingAs($user, 'web')->get(route('checkout.success', $this->makeOrder($user, 'cod', 'pending')))->getContent();
        $this->assertStringNotContainsString('"email":', $successContent);
        $this->assertStringNotContainsString('"phone":', $successContent);
        $this->assertStringNotContainsString('"user_id":', $successContent);
        $this->assertStringNotContainsString('"pincode":', $successContent);
    }

    protected function makeProduct(array $attributes = []): Product
    {
        return Product::factory()->active()->create(array_merge([
            'sku' => 'VNRT070',
            'name' => 'VANRITI Soothing Balm',
            'selling_price' => 300.00,
            'mrp' => 300.00,
            'stock' => 10,
        ], $attributes));
    }

    protected function seedCart(User $user, Product $product, int $qty): void
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
    }

    protected function makeOrder(User $user, string $paymentMethod, string $paymentStatus): Order
    {
        $product = $this->makeProduct();

        $order = Order::create([
            'order_number' => 'VAN-7A-'.str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT),
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