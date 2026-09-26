<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\User;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\OrderService;
use App\Services\RefundService;
use App\Services\ShipMojoService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhaseDBusinessLogicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    // ── order number ──────────────────────────────────────────────────────────

    public function test_placed_order_never_retains_the_placeholder_number(): void
    {
        $order = $this->placeOrder();

        $this->assertStringStartsWith('VAN-', $order->order_number);
        $this->assertStringNotContainsString('TMP-', $order->order_number);
    }

    public function test_order_numbers_are_unique_across_orders(): void
    {
        $first = $this->placeOrder();
        $second = $this->placeOrder();

        $this->assertNotSame($first->order_number, $second->order_number);
        $this->assertSame(2, Order::whereIn('order_number', [$first->order_number, $second->order_number])->count());
    }

    // ── coupons ───────────────────────────────────────────────────────────────

    public function test_redemption_limit_is_enforced_and_never_exceeded(): void
    {
        $user = $this->user();
        $product = $this->product('LIMIT-1', 500.00);

        $coupon = $this->coupon(['usage_limit' => 1, 'discount_value' => 10, 'discount_type' => 'percentage']);

        $this->placeOrder($user, $product, $coupon->code);

        $result = app(CouponService::class)->validate($coupon->code, $this->cart($user, $product), $user);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('usage limit', (string) $result['message']);
        $this->assertSame(1, $coupon->usages()->count());
    }

    public function test_per_customer_limit_blocks_a_second_redemption_by_the_same_user(): void
    {
        $user = $this->user();
        $product = $this->product('PCL-1', 500.00);

        $coupon = $this->coupon(['per_customer_limit' => 1, 'discount_value' => 10, 'discount_type' => 'percentage']);

        $this->placeOrder($user, $product, $coupon->code);

        $result = app(CouponService::class)->validate($coupon->code, $this->cart($user, $product), $user);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('already used', (string) $result['message']);
    }

    public function test_first_order_only_coupon_is_not_offered_to_guests(): void
    {
        $coupon = $this->coupon(['first_order_only' => true, 'discount_value' => 10, 'discount_type' => 'percentage']);

        $result = app(CouponService::class)->validate($coupon->code, $this->cart(), null);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('first-time buyers', (string) $result['message']);
    }

    public function test_product_restricted_coupon_only_discounts_the_eligible_line(): void
    {
        $eligible = $this->product('ELIG-1', 400.00);
        $other = $this->product('OTHER-1', 600.00);

        $coupon = $this->coupon(['discount_type' => 'percentage', 'discount_value' => 50]);
        $coupon->products()->attach($eligible->id);

        $cart = $this->cart();
        $cart->items()->create([
            'product_id' => $eligible->id, 'quantity' => 1,
            'unit_price' => 400.00, 'mrp' => 400.00, 'gst_rate' => 0,
        ]);
        $cart->items()->create([
            'product_id' => $other->id, 'quantity' => 1,
            'unit_price' => 600.00, 'mrp' => 600.00, 'gst_rate' => 0,
        ]);

        $discount = app(CouponService::class)->calculateDiscount($coupon, $cart->fresh());

        // 50% of the eligible 400 line only, never of the 1000 cart total.
        $this->assertEqualsWithDelta(200.00, $discount, 0.01);
    }

    public function test_coupon_restricted_to_other_products_is_rejected(): void
    {
        $eligible = $this->product('ELIG-2', 400.00);
        $inCart = $this->product('IN-CART', 300.00);

        $coupon = $this->coupon(['discount_type' => 'percentage', 'discount_value' => 10]);
        $coupon->products()->attach($eligible->id);

        $cart = $this->cart();
        $cart->items()->create([
            'product_id' => $inCart->id, 'quantity' => 1,
            'unit_price' => 300.00, 'mrp' => 300.00, 'gst_rate' => 0,
        ]);

        $result = app(CouponService::class)->validate($coupon->code, $cart->fresh(), $this->user());

        $this->assertFalse($result['valid']);
    }

    // ── refunds ───────────────────────────────────────────────────────────────

    public function test_a_requested_refund_cannot_be_completed(): void
    {
        $refund = $this->refund(status: 'requested');

        $this->expectException(\RuntimeException::class);

        app(RefundService::class)->complete($refund);
    }

    public function test_a_rejected_refund_cannot_be_completed(): void
    {
        $refund = $this->refund(status: 'rejected');

        $this->expectException(\RuntimeException::class);

        app(RefundService::class)->complete($refund);
    }

    public function test_an_approved_refund_can_be_completed(): void
    {
        $refund = $this->refund(status: 'approved');

        $this->assertSame('completed', app(RefundService::class)->complete($refund)->status);
    }

    // ── ShipMojo ──────────────────────────────────────────────────────────────

    public function test_repeated_pushes_reuse_one_shipment_and_do_not_repost_to_the_carrier(): void
    {
        $this->enableShipMojo();

        $order = $this->placeOrder();
        $order->update(['order_status' => 'processing']);

        Http::fake(['shipping-api.com/*' => Http::response(['result' => '1', 'data' => ['order_id' => 'SM-1', 'reference_id' => 'REF-1']], 200)]);

        $service = app(ShipMojoService::class);
        $service->pushOrder($order->fresh());
        $service->pushOrder($order->fresh());

        $this->assertSame(1, Shipment::where('order_id', $order->id)->count());
        $this->assertSame('SM-1', Shipment::where('order_id', $order->id)->first()->shipmojo_order_id);

        Http::assertSentCount(1);
    }

    public function test_a_late_rto_webhook_cannot_cancel_a_delivered_order(): void
    {
        $this->enableShipMojo();

        $order = $this->deliveredOrder();
        $shipment = Shipment::create([
            'order_id' => $order->id,
            'status' => 'delivered',
            'shipmojo_order_id' => 'SM-DELIVERED',
            'delivered_at' => now(),
        ]);

        $result = app(ShipMojoService::class)->applyWebhook([
            'order_id' => 'SM-DELIVERED',
            'status' => 'rto initiated',
        ]);

        $this->assertSame('delivered', $shipment->fresh()->status);
        $this->assertSame('delivered', $order->fresh()->order_status);
        $this->assertFalse((bool) $result['order_status_changed']);
    }

    public function test_a_carrier_cancel_is_stored_with_a_valid_shipment_status(): void
    {
        $this->enableShipMojo();

        $order = $this->placeOrder();
        $order->update(['order_status' => 'shipped']);

        Shipment::create([
            'order_id' => $order->id,
            'status' => 'shipped',
            'shipmojo_order_id' => 'SM-CANCEL',
        ]);

        app(ShipMojoService::class)->applyWebhook([
            'order_id' => 'SM-CANCEL',
            'status' => 'cancelled',
        ]);

        // shipments.status has no 'cancelled' member, so it must be recorded as
        // 'failed' rather than blowing up on an invalid enum write.
        $this->assertSame('failed', Shipment::where('order_id', $order->id)->first()->status);
        $this->assertSame('cancelled', $order->fresh()->order_status);
    }

    public function test_a_delivered_shipment_is_not_downgraded_by_a_tracking_sync(): void
    {
        $this->enableShipMojo();

        $order = $this->deliveredOrder();
        Shipment::create([
            'order_id' => $order->id,
            'status' => 'delivered',
            'shipmojo_order_id' => 'SM-SYNC',
            'awb_number' => 'AWB-SYNC',
            'delivered_at' => now(),
        ]);

        Http::fake(['shipping-api.com/*' => Http::response([
            'result' => '1',
            'data' => ['current_status' => 'rto delivered', 'scan_detail' => []],
        ], 200)]);

        app(ShipMojoService::class)->syncTracking($order->fresh());

        $this->assertSame('delivered', Shipment::where('order_id', $order->id)->first()->status);
        $this->assertSame('delivered', $order->fresh()->order_status);
    }

    // ── catalogue / account gates ─────────────────────────────────────────────

    public function test_an_inactive_category_page_is_not_public(): void
    {
        $category = Category::create([
            'name' => 'Draft Range',
            'slug' => 'draft-range-'.uniqid(),
            'status' => 'inactive',
        ]);

        $this->get('/shop/category/'.$category->slug)->assertNotFound();
    }

    public function test_reviews_are_rejected_for_an_undelivered_order(): void
    {
        $user = $this->user();
        $product = $this->product('UNREV-1', 250.00);

        $order = $this->placeOrder($user, $product);
        $item = $order->items()->firstOrFail();

        $this->actingAs($user, 'web')
            ->post(route('account.order.review', $order), [
                'order_item_id' => $item->id,
                'rating' => 5,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('reviews', ['order_item_id' => $item->id]);
    }

    public function test_returns_are_blocked_outside_the_return_window(): void
    {
        $user = $this->user();
        $product = $this->product('RET-1', 250.00);

        $order = $this->deliveredOrder($user, $product);
        $item = $order->items()->firstOrFail();

        // The delivery happened well outside the advertised 7-day window.
        $this->travel(20)->days();
        $order->update(['order_status' => 'delivered']);

        $this->assertFalse($order->fresh()->isReturnable());

        $this->actingAs($user, 'web')
            ->post(route('account.order.return', $order), [
                'reason' => 'Too late',
                'items' => [$item->id],
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, ReturnRequest::where('order_id', $order->id)->count());
    }

    public function test_returns_are_allowed_inside_the_return_window(): void
    {
        $user = $this->user();
        $product = $this->product('RET-2', 250.00);

        $order = $this->deliveredOrder($user, $product);
        $item = $order->items()->firstOrFail();

        $this->assertTrue($order->fresh()->isReturnable());

        $this->actingAs($user, 'web')
            ->post(route('account.order.return', $order), [
                'reason' => 'Damaged',
                'items' => [$item->id],
            ])
            ->assertSessionHas('success');

        $this->assertSame(1, ReturnRequest::where('order_id', $order->id)->count());
    }

    // ── cart ──────────────────────────────────────────────────────────────────

    public function test_a_foreign_cart_item_cannot_be_updated_or_removed(): void
    {
        $owner = $this->user();
        $product = $this->product('CART-1', 100.00);

        $foreignCart = Cart::create(['owner_type' => User::class, 'owner_id' => $owner->id]);
        $foreignItem = $foreignCart->items()->create([
            'product_id' => $product->id, 'quantity' => 1,
            'unit_price' => 100.00, 'mrp' => 100.00, 'gst_rate' => 0,
        ]);

        $attacker = $this->user();
        $attackerCart = Cart::create(['owner_type' => User::class, 'owner_id' => $attacker->id]);
        $attackerCart->items()->create([
            'product_id' => $product->id, 'quantity' => 1,
            'unit_price' => 100.00, 'mrp' => 100.00, 'gst_rate' => 0,
        ]);

        $response = $this->actingAs($attacker, 'web')
            ->postJson(route('cart.update', $foreignItem), ['quantity' => 4]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertSame(1, $foreignItem->fresh()->quantity);

        $this->actingAs($attacker, 'web')
            ->postJson(route('cart.remove', $foreignItem))
            ->assertStatus(422);

        $this->assertDatabaseHas('cart_items', ['id' => $foreignItem->id]);
    }

    public function test_logging_out_releases_the_account_cart_to_the_session(): void
    {
        $user = $this->user();
        $product = $this->product('REL-1', 120.00);

        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id, 'quantity' => 2,
            'unit_price' => 120.00, 'mrp' => 120.00, 'gst_rate' => 0,
        ]);

        $this->actingAs($user, 'web')->post(route('logout'));

        $this->assertGuest();

        $guestCart = Cart::where('owner_type', 'guest')->where('owner_id', 0)->latest('id')->first();

        $this->assertNotNull($guestCart, 'The cart should survive logout as a guest cart.');
        $this->assertSame(1, $guestCart->items()->count());
        $this->assertSame(2, (int) $guestCart->items()->first()->quantity);
    }

    // ── newsletter ────────────────────────────────────────────────────────────

    public function test_newsletter_addresses_are_normalized(): void
    {
        $this->post(route('newsletter.subscribe'), ['email' => '  Buyer.Name+Sale@Gmail.com '])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('newsletters', ['email' => 'buyername@gmail.com']);
    }

    public function test_unsubscribing_requires_a_valid_token(): void
    {
        $this->post(route('newsletter.subscribe'), ['email' => 'buyer@example.com']);

        $this->get('/newsletter/unsubscribe/badtoken?email=buyer@example.com')->assertForbidden();

        $this->assertDatabaseHas('newsletters', ['email' => 'buyer@example.com', 'is_subscribed' => true]);
    }

    public function test_a_subscriber_can_unsubscribe_through_a_signed_link(): void
    {
        $this->post(route('newsletter.subscribe'), ['email' => ' Buyer.Name+Sale@Gmail.com ']);

        $this->assertDatabaseHas('newsletters', ['email' => 'buyername@gmail.com', 'is_subscribed' => true]);

        $this->get(newsletter_unsubscribe_url('buyer.name+sale@gmail.com'))
            ->assertOk();

        $this->assertDatabaseHas('newsletters', ['email' => 'buyername@gmail.com', 'is_subscribed' => false]);
    }

    public function test_an_unsubscribe_token_cannot_be_retargeted_at_another_address(): void
    {
        $this->post(route('newsletter.subscribe'), ['email' => 'victim@example.com']);
        $this->post(route('newsletter.subscribe'), ['email' => 'attacker@example.com']);

        // Reuse the victim's own token, but swap the address in the query string.
        $url = route('newsletter.unsubscribe', [
            'token' => newsletter_unsubscribe_token('victim@example.com'),
            'email' => 'attacker@example.com',
        ]);

        $this->get($url)->assertForbidden();

        $this->assertDatabaseHas('newsletters', ['email' => 'attacker@example.com', 'is_subscribed' => true]);
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    protected function user(): User
    {
        return User::factory()->create();
    }

    protected function product(string $sku, float $price): Product
    {
        return Product::factory()->active()->create([
            'sku' => $sku,
            'name' => 'Product '.$sku,
            'selling_price' => $price,
            'mrp' => $price,
            'gst_rate' => 0,
            'stock' => 20,
        ]);
    }

    protected function coupon(array $overrides = []): Coupon
    {
        return Coupon::create(array_merge([
            'code' => 'TEST'.strtoupper(uniqid()),
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_cart_value' => 0,
            'usage_limit' => null,
            'per_customer_limit' => 0,
            'first_order_only' => false,
            'is_active' => true,
        ], $overrides));
    }

    protected function cart(?User $user = null, ?Product $product = null): Cart
    {
        $user ??= $this->user();
        $product ??= $this->product('CART-'.uniqid(), 500.00);

        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id, 'quantity' => 1,
            'unit_price' => $product->selling_price, 'mrp' => $product->mrp,
            'gst_rate' => $product->gst_rate,
        ]);

        return $cart->fresh();
    }

    protected function placeOrder(?User $user = null, ?Product $product = null, ?string $coupon = null): Order
    {
        $user ??= $this->user();
        $product ??= $this->product('SKU-'.uniqid(), 500.00);

        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id, 'quantity' => 1,
            'unit_price' => $product->selling_price, 'mrp' => $product->mrp,
            'gst_rate' => $product->gst_rate,
        ]);

        return app(OrderService::class)->placeOrder($user, $cart->fresh(), [
            'billing' => [
                'full_name' => 'Aarav Mehta', 'mobile' => '9876543210',
                'address_line1' => '42 MG Road', 'city' => 'Bengaluru',
                'state' => 'Karnataka', 'pincode' => '560038', 'country' => 'India',
            ],
            'shipping' => [
                'full_name' => 'Aarav Mehta', 'mobile' => '9876543210',
                'address_line1' => '42 MG Road', 'city' => 'Bengaluru',
                'state' => 'Karnataka', 'pincode' => '560038', 'country' => 'India',
            ],
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
            'coupon_code' => $coupon,
            'notes' => null,
        ]);
    }

    protected function deliveredOrder(?User $user = null, ?Product $product = null): Order
    {
        $order = $this->placeOrder($user, $product);

        app(OrderService::class)->updateOrderStatus($order, 'delivered', 'Delivered.');

        return $order->fresh();
    }

    protected function refund(string $status): Refund
    {
        $order = $this->deliveredOrder();

        $refund = Refund::create([
            'refund_number' => 'REF-TEST-'.uniqid(),
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'amount' => 100.00,
            'type' => 'partial',
            'status' => $status,
            'requested_at' => now(),
        ]);

        $order->update(['amount_paid' => 500.00, 'payment_status' => 'paid']);

        return $refund;
    }

    protected function enableShipMojo(): void
    {
        Setting::updateOrCreate(['key' => 'shipmojo_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'shipmojo_public_key'], ['value' => 'pub_key']);
        Setting::updateOrCreate(['key' => 'shipmojo_private_key'], [
            'value' => Crypt::encryptString('priv_key'),
        ]);
    }
}
