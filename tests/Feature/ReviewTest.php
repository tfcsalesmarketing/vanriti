<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    protected function deliveredOrder(User $user, Product $product): Order
    {
        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->selling_price,
            'mrp' => $product->mrp,
            'gst_rate' => 0,
        ]);

        $order = app(OrderService::class)->placeOrder($user, $cart->fresh(), [
            'billing' => ['full_name' => 'A', 'mobile' => '9876543210', 'address_line1' => '1', 'city' => 'C', 'state' => 'S', 'pincode' => '560038'],
            'shipping' => ['full_name' => 'A', 'mobile' => '9876543210', 'address_line1' => '1', 'city' => 'C', 'state' => 'S', 'pincode' => '560038'],
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
            'coupon_code' => null,
            'notes' => null,
        ]);

        $order->update(['order_status' => 'delivered']);

        return $order->fresh();
    }

    public function test_customer_can_review_own_delivered_order(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 150.00, 'mrp' => 150.00, 'gst_rate' => 0, 'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);
        $orderItem = $order->items()->firstOrFail();

        $response = $this->actingAs($user, 'web')
            ->post(route('account.order.review', $order), [
                'order_item_id' => $orderItem->id,
                'rating' => 5,
                'title' => 'Excellent',
                'comment' => 'Great product, highly recommend!',
            ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'order_item_id' => $orderItem->id,
            'rating' => 5,
            'status' => 'pending',
            'is_verified_purchase' => true,
        ]);
    }

    public function test_duplicate_review_is_rejected(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 150.00, 'mrp' => 150.00, 'gst_rate' => 0, 'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);
        $orderItem = $order->items()->firstOrFail();

        // First review
        $this->actingAs($user, 'web')
            ->post(route('account.order.review', $order), [
                'order_item_id' => $orderItem->id,
                'rating' => 5,
                'comment' => 'First review',
            ])->assertSessionHas('success');

        // Duplicate review should be rejected gracefully
        $response = $this->actingAs($user, 'web')
            ->post(route('account.order.review', $order), [
                'order_item_id' => $orderItem->id,
                'rating' => 1,
                'comment' => 'Second attempt',
            ]);

        $response->assertSessionHas('error');
        $this->assertSame(1, Review::where('order_item_id', $orderItem->id)->count());
    }

    public function test_user_cannot_review_another_users_order(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 150.00, 'mrp' => 150.00, 'gst_rate' => 0, 'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);

        $response = $this->actingAs($other, 'web')
            ->post(route('account.order.review', $order), [
                'order_item_id' => $order->items()->firstOrFail()->id,
                'rating' => 5,
                'comment' => 'not mine',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_requires_valid_rating(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'selling_price' => 150.00, 'mrp' => 150.00, 'gst_rate' => 0, 'stock' => 10,
        ]);

        $order = $this->deliveredOrder($user, $product);

        $response = $this->actingAs($user, 'web')
            ->post(route('account.order.review', $order), [
                'order_item_id' => $order->items()->firstOrFail()->id,
                'rating' => 99,
                'comment' => 'Bad rating',
            ]);

        $response->assertSessionHasErrors('rating');
        $this->assertDatabaseCount('reviews', 0);
    }
}
