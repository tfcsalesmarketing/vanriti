<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartDataLayerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_successful_ajax_add_returns_add_to_cart_payload(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT060',
            'name' => 'VANRITI Revitalising Face Oil',
            'selling_price' => 499.00,
            'mrp' => 699.00,
            'stock' => 10,
        ]);

        $response = $this->actingAs($user, 'web')->postJson(route('cart.add', $product), ['quantity' => 2]);

        $response->assertOk()
            ->assertJson(['success' => true, 'cartCount' => 2]);

        $data = $response->json();
        $this->assertArrayHasKey('analytics', $data);
        $this->assertSame('add_to_cart', $data['analytics']['event']);
        $this->assertSame('INR', $data['analytics']['ecommerce']['currency']);
        $this->assertEqualsWithDelta(998.00, $data['analytics']['ecommerce']['value'], 0.01);

        $item = $data['analytics']['ecommerce']['items'][0];
        $this->assertSame('VNRT060', $item['item_id']);
        $this->assertSame('VANRITI Revitalising Face Oil', $item['item_name']);
        $this->assertSame('VANRITI', $item['item_brand']);
        $this->assertEqualsWithDelta(499.00, $item['price'], 0.01);
        $this->assertSame(2, $item['quantity']);
        $this->assertArrayNotHasKey('mrp', $item);
        $this->assertArrayNotHasKey('id', $item);
    }

    public function test_successful_ajax_add_with_variant_uses_variant_sku_and_price(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT061',
            'name' => 'VANRITI Face Wash',
            'selling_price' => 120.00,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Charcoal 100 ml',
            'sku' => 'VNRT061-CH100',
            'selling_price' => 140.00,
            'status' => 'active',
            'stock' => 5,
        ]);

        $data = $this->actingAs($user, 'web')
            ->postJson(route('cart.add', $product), ['quantity' => 1, 'variant_id' => $variant->id])
            ->assertOk()
            ->json();

        $item = $data['analytics']['ecommerce']['items'][0];
        $this->assertSame('VNRT061-CH100', $item['item_id']);
        $this->assertSame('Charcoal 100 ml', $item['item_variant']);
        $this->assertEqualsWithDelta(140.00, $item['price'], 0.01);
        $this->assertEqualsWithDelta(140.00, $data['analytics']['ecommerce']['value'], 0.01);
    }

    public function test_failed_ajax_add_returns_no_add_to_cart(): void
    {
        $user = User::factory()->create();
        $outOfStock = Product::factory()->active()->create(['sku' => 'VNRT062', 'stock' => 0]);
        $overBought = Product::factory()->active()->create(['sku' => 'VNRT063', 'stock' => 3]);

        $responseOut = $this->actingAs($user, 'web')->postJson(route('cart.add', $outOfStock), ['quantity' => 1]);
        $responseOut->assertStatus(422);
        $this->assertArrayNotHasKey('analytics', $responseOut->json());
        $this->assertStringNotContainsString('add_to_cart', $responseOut->content());

        $responseOver = $this->actingAs($user, 'web')->postJson(route('cart.add', $overBought), ['quantity' => 10]);
        $responseOver->assertStatus(422);
        $this->assertArrayNotHasKey('analytics', $responseOver->json());
    }

    public function test_validation_failure_returns_no_add_to_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create(['sku' => 'VNRT064', 'stock' => 10]);

        $response = $this->actingAs($user, 'web')->postJson(route('cart.add', $product), ['quantity' => 0]);

        $response->assertStatus(422);
        $this->assertArrayNotHasKey('analytics', $response->json());
        $this->assertStringNotContainsString('add_to_cart', $response->content());
    }

    public function test_ajax_buy_now_returns_exactly_one_add_to_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT065',
            'name' => 'VANRITI Serum',
            'selling_price' => 250.00,
            'stock' => 10,
        ]);

        $response = $this->actingAs($user, 'web')->postJson(route('cart.add', $product), [
            'quantity' => 1,
            'buy_now' => 1,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'redirect_only' => true, 'redirect' => route('checkout.index')]);

        $data = $response->json();
        $this->assertSame('add_to_cart', $data['analytics']['event']);
        $this->assertEqualsWithDelta(250.00, $data['analytics']['ecommerce']['value'], 0.01);
        $this->assertSame(1, substr_count($response->content(), 'add_to_cart'));
    }

    public function test_cart_page_with_items_fires_exactly_one_view_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT066',
            'name' => 'VANRITI Hair Oil',
            'selling_price' => 300.00,
            'stock' => 10,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 2]);

        $html = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"view_cart"'));
        $this->assertStringContainsString('"item_id":"VNRT066"', $html);
        $this->assertStringContainsString('"currency":"INR"', $html);
        $this->assertStringContainsString('"quantity":2', $html);
        $this->assertStringContainsString('"value":600', $html);
    }

    public function test_cart_page_escapes_special_characters_in_view_cart_payload(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT067',
            'name' => "Curly \"Oil\" & Co <Script> O'Neem",
            'selling_price' => 200.00,
            'stock' => 10,
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1]);

        $html = $this->actingAs($user, 'web')->get(route('cart.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '"view_cart"'));
        $this->assertStringContainsString('\u003C', $html);
        $this->assertStringContainsString('\u003E', $html);
        $this->assertStringContainsString('\u0026', $html);
        $this->assertStringContainsString('\u0027', $html);
        $this->assertStringNotContainsString('<Script>', $html);
    }

    public function test_empty_cart_page_fires_no_view_cart(): void
    {
        $html = $this->get(route('cart.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('view_cart', $html);
    }
}