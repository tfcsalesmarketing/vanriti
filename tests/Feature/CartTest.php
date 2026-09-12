<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_to_cart_creates_cart_and_item_with_price_snapshot(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create();

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 3]);

        $this->assertDatabaseHas('carts', [
            'owner_type' => User::class,
            'owner_id' => $user->id,
        ]);

        $cart = Cart::where('owner_type', User::class)->where('owner_id', $user->id)->firstOrFail();
        $this->assertSame(1, $cart->items()->count());

        $item = $cart->items()->firstOrFail();
        $this->assertSame((int) $product->id, (int) $item->product_id);
        $this->assertSame(3, (int) $item->quantity);
        $this->assertEquals((float) $product->selling_price, (float) $item->unit_price);
        $this->assertEquals((float) $product->mrp, (float) $item->mrp);
    }

    public function test_update_quantity_changes_item(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create(['stock' => 50]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1]);
        $cart = Cart::where('owner_type', User::class)->where('owner_id', $user->id)->firstOrFail();
        $item = $cart->items()->firstOrFail();

        $this->actingAs($user, 'web')->post(route('cart.update', $item), ['quantity' => 5]);

        $this->assertSame(5, (int) $item->fresh()->quantity);
    }

    public function test_remove_deletes_item(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create();

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1]);
        $cart = Cart::where('owner_type', User::class)->where('owner_id', $user->id)->firstOrFail();
        $item = $cart->items()->firstOrFail();

        $this->actingAs($user, 'web')->post(route('cart.remove', $item));

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_clear_empties_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create();

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 2]);
        $cart = Cart::where('owner_type', User::class)->where('owner_id', $user->id)->firstOrFail();

        $this->actingAs($user, 'web')->post(route('cart.clear'));

        $this->assertSame(0, $cart->items()->count());
    }

    public function test_out_of_stock_product_cannot_be_added(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create(['stock' => 0]);

        $response = $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_quantity_greater_than_stock_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create(['stock' => 5]);

        $response = $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 10]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_variant_can_be_added_to_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 10,
            'status' => 'active',
        ]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), [
            'quantity' => 2,
            'variant_id' => $variant->id,
        ]);

        $cart = Cart::where('owner_type', User::class)->where('owner_id', $user->id)->firstOrFail();
        $item = $cart->items()->firstOrFail();
        $this->assertSame((int) $variant->id, (int) $item->product_variant_id);
        $this->assertSame(2, (int) $item->quantity);
    }

    public function test_cart_service_subtotal_is_correct(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create(['selling_price' => 199.50]);

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 2]);

        $cart = Cart::where('owner_type', User::class)->where('owner_id', $user->id)->firstOrFail();
        $this->assertEqualsWithDelta(399.00, $cart->subtotal(), 0.01);
    }

    public function test_buy_now_json_redirects_to_checkout_and_orders_item(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create(['stock' => 10]);

        $response = $this->actingAs($user, 'web')->postJson(route('cart.add', $product), [
            'quantity' => 2,
            'buy_now' => 1,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'redirect_only' => true,
                'redirect' => route('checkout.index'),
            ]);

        $cart = Cart::where('owner_type', User::class)->where('owner_id', $user->id)->firstOrFail();
        $this->assertSame(2, (int) $cart->items()->firstOrFail()->quantity);
    }

    public function test_add_to_cart_json_returns_cart_count(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create(['stock' => 10]);

        $response = $this->actingAs($user, 'web')->postJson(route('cart.add', $product), ['quantity' => 1]);

        $response->assertOk()
            ->assertJsonFragment(['cartCount' => 1, 'success' => true]);
    }

    public function test_guest_first_add_json_returns_cart_count_one(): void
    {
        $product = Product::factory()->active()->create(['stock' => 10]);

        $response = $this->postJson(route('cart.add', $product), ['quantity' => 1]);

        $response->assertOk()
            ->assertJsonFragment(['cartCount' => 1, 'success' => true]);
    }
}
