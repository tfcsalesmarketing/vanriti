<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\WishlistService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NativeAddToCartHandoffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_native_pdp_add_redirects_to_cart_and_pushes_exactly_one_add_to_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => '7AHND-001',
            'name' => 'VANRITI Handoff Serum',
            'selling_price' => 499.00,
            'mrp' => 699.00,
            'stock' => 10,
        ]);

        $response = $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 2]);

        $response->assertRedirect(route('cart.index'));

        $html = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"event":"add_to_cart"'));
        $this->assertSame(1, substr_count($html, '"event":"view_cart"'));
        $this->assertStringContainsString('"item_id":"7AHND-001"', $html);
        $this->assertStringContainsString('"item_name":"VANRITI Handoff Serum"', $html);
        $this->assertStringContainsString('"item_brand":"VANRITI"', $html);
        $this->assertStringContainsString('"quantity":2', $html);
        $this->assertStringContainsString('"value":998', $html);
    }

    public function test_native_add_handoff_is_consumed_on_refresh(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => '7AHND-002',
            'selling_price' => 200.00,
            'stock' => 10,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1]);

        $first = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($first, '"event":"add_to_cart"'));

        $refresh = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();
        $this->assertSame(0, substr_count($refresh, '"event":"add_to_cart"'));
        $this->assertSame(1, substr_count($refresh, '"event":"view_cart"'));
    }

    public function test_add_to_cart_push_precedes_view_cart_push_on_cart_page(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => '7AHND-003',
            'selling_price' => 100.00,
            'stock' => 10,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1]);

        $html = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();

        $addPos = strpos($html, '"event":"add_to_cart"');
        $viewPos = strpos($html, '"event":"view_cart"');

        $this->assertNotFalse($addPos);
        $this->assertNotFalse($viewPos);
        $this->assertLessThan($viewPos, $addPos);
    }

    public function test_native_buy_now_redirects_to_checkout_and_pushes_add_to_cart_before_begin_checkout(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => '7AHND-004',
            'name' => 'VANRITI Buy Now Oil',
            'selling_price' => 350.00,
            'stock' => 10,
        ]);

        $response = $this->actingAs($user, 'web')->post(route('cart.add', $product), [
            'quantity' => 1,
            'buy_now' => 1,
        ]);

        $response->assertRedirect(route('checkout.index'));

        $html = $this->actingAs($user, 'web')->get(route('checkout.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"event":"add_to_cart"'));
        $this->assertSame(1, substr_count($html, '"event":"begin_checkout"'));
        $this->assertStringContainsString('"item_id":"7AHND-004"', $html);
        $this->assertStringContainsString('"value":350', $html);

        $addPos = strpos($html, '"event":"add_to_cart"');
        $beginPos = strpos($html, '"event":"begin_checkout"');
        $this->assertNotFalse($addPos);
        $this->assertNotFalse($beginPos);
        $this->assertLessThan($beginPos, $addPos);
    }

    public function test_ajax_add_leaves_no_pending_handoff_and_does_not_double_fire(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => '7AHND-005',
            'selling_price' => 250.00,
            'stock' => 10,
        ]);

        $json = $this->actingAs($user, 'web')->postJson(route('cart.add', $product), ['quantity' => 1]);
        $json->assertOk();
        $this->assertSame('add_to_cart', $json->json('analytics.event'));

        $html = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();

        $this->assertSame(0, substr_count($html, '"event":"add_to_cart"'));
        $this->assertSame(1, substr_count($html, '"event":"view_cart"'));
    }

    public function test_wishlist_native_add_pushes_add_to_cart_and_removes_from_wishlist(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => '7AHND-006',
            'selling_price' => 150.00,
            'stock' => 10,
        ]);

        $this->actingAs($user, 'web')->post(route('wishlist.toggle', $product));
        $this->assertSame(1, app(WishlistService::class)->count());

        $response = $this->actingAs($user, 'web')->post(route('cart.add', $product), [
            'quantity' => 1,
            'remove_from_wishlist' => 1,
        ]);

        $response->assertRedirect(route('cart.index'));
        $this->assertSame(0, app(WishlistService::class)->count());

        $html = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"event":"add_to_cart"'));
        $this->assertStringContainsString('"item_id":"7AHND-006"', $html);
    }

    public function test_out_of_stock_native_add_pushes_no_add_to_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => '7AHND-007',
            'selling_price' => 100.00,
            'stock' => 0,
        ]);

        $response = $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1]);

        $response->assertSessionHas('error');

        $html = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();
        $this->assertSame(0, substr_count($html, '"event":"add_to_cart"'));
    }

    public function test_native_add_handoff_payload_is_escaped(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => '7AHND-008',
            'name' => "Curly \"Oil\" & Co <Script> O'Neem",
            'selling_price' => 200.00,
            'stock' => 10,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1]);

        $html = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"event":"add_to_cart"'));
        $this->assertStringContainsString('\u003C', $html);
        $this->assertStringContainsString('\u003E', $html);
        $this->assertStringContainsString('\u0026', $html);
        $this->assertStringContainsString('\u0027', $html);
        $this->assertStringNotContainsString('<Script>', $html);
    }

    public function test_native_add_with_variant_uses_variant_sku_in_handoff(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => '7AHND-009',
            'name' => 'VANRITI Face Wash',
            'selling_price' => 120.00,
            'stock' => 10,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Charcoal 100 ml',
            'sku' => '7AHND-009-CH100',
            'selling_price' => 140.00,
            'status' => 'active',
            'stock' => 5,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), [
            'quantity' => 3,
            'variant_id' => $variant->id,
        ]);

        $html = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"event":"add_to_cart"'));
        $this->assertStringContainsString('"item_id":"7AHND-009-CH100"', $html);
        $this->assertStringContainsString('"item_variant":"Charcoal 100 ml"', $html);
        $this->assertStringContainsString('"value":420', $html);
    }
}