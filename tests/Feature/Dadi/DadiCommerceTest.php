<?php

namespace Tests\Feature\Dadi;

use App\Dadi\Contracts\AiProvider;
use App\Dadi\Persistence\DadiConversationStore;
use App\Dadi\Persistence\DadiProductProfileStore;
use App\Dadi\ValueObjects\AiRequest;
use App\Dadi\ValueObjects\AiResponse;
use App\Models\Admin;
use App\Models\Cart;
use App\Models\DadiMessage;
use App\Models\DadiRecommendationEvent;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Services\CartService;
use App\Services\Dadi\DadiAttributionService;
use App\Services\OrderService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class CommerceFakeProvider implements AiProvider
{
    public function __construct(public AiResponse $response) {}

    public function generate(AiRequest $request): AiResponse
    {
        return $this->response;
    }
}

/**
 * Stage 9: Dadi's product cards connect to the existing, Laravel-authoritative
 * commerce flows (cart.add / Buy Now / checkout / orders) with a thin,
 * privacy-conscious attribution layer. No Dadi-specific cart, order or
 * inventory ever exists; the browser can never supply price, stock or product
 * identity, and attribution references are validated against conversation
 * ownership before any event is recorded.
 */
class DadiCommerceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingsSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function store(): DadiConversationStore
    {
        return $this->app->make(DadiConversationStore::class);
    }

    private function attribution(): DadiAttributionService
    {
        return $this->app->make(DadiAttributionService::class);
    }

    private function products(): Product
    {
        return Product::factory()->active()->create([
            'selling_price' => 499.00,
            'mrp' => 699.00,
            'gst_rate' => 18,
            'stock' => 20,
        ]);
    }

    private function openGuestSession(): string
    {
        $this->get(route('dadi.index'));

        return $this->app['session.store']->getId();
    }

    private function guestCommerce(
        string $sessionId,
        array $payload,
    ): TestResponse {
        return $this
            ->withCredentials()
            ->withCookie(config('session.cookie'), $sessionId)
            ->postJson(route('cart.add', $payload['product']), $payload['body']);
    }

    /* ---------------- product intelligence helpers (message-turn tests) ---------------- */

    private function reviewer(): Admin
    {
        $permission = Permission::firstOrCreate(
            ['slug' => DadiProductProfileStore::REVIEW_PERMISSION],
            ['name' => 'Review dadi product profiles'],
        );

        $role = Role::create(['slug' => 'dadi-commerce-reviewer-'.Str::random(6), 'name' => 'Dadi Reviewer']);
        $role->permissions()->attach($permission);

        $admin = Admin::factory()->create();
        $admin->roles()->attach($role);

        return $admin;
    }

    private function profileStore(): DadiProductProfileStore
    {
        return $this->app->make(DadiProductProfileStore::class);
    }

    private function approveProduct(Product $product): void
    {
        $reviewer = $this->reviewer();

        $profile = $this->profileStore()->create($product, [
            'sections' => ['hair'],
            'concerns' => ['hair_dryness'],
            'positioning' => 'Gentle daily care.',
            'approved_benefits' => ['Nourishes dry lengths'],
            'approved_usage_context' => ['Apply to damp lengths'],
            'approved_precautions' => ['Avoid contact with eyes'],
            'suitability_notes' => ['Consider for dry hair'],
        ], $reviewer);

        $this->profileStore()->submitForReview($profile, $reviewer);
        $this->profileStore()->approve($profile, $reviewer);
    }

    private function provider(array $data): void
    {
        $fake = new CommerceFakeProvider(
            new AiResponse(json_encode($data, JSON_THROW_ON_ERROR), $data),
        );

        $this->app->instance(AiProvider::class, $fake);
    }

    /**
     * @return array<string,mixed>
     */
    private function recommendationReply(): array
    {
        return [
            'reply' => 'Beta, aapke baal ke liye yeh serum kaam aayega.',
            'intent' => 'concern',
            'safety_review' => false,
            'understanding' => [
                'section' => 'hair',
                'concerns' => ['hair_dryness'],
                'attributes' => [],
                'preferences' => [],
                'memory' => [],
            ],
        ];
    }

    /* ---------------- cart.add (reused for Dadi cards) ---------------- */

    public function test_recommendation_add_records_attribution_after_a_successful_cart_add(): void
    {
        $sessionId = $this->openGuestSession();
        $product = $this->products();
        $conversation = $this->store()->start(sessionId: $sessionId);
        $impression = $this->attribution()->impressionFor($conversation, $product);

        $response = $this
            ->withCredentials()
            ->withCookie(config('session.cookie'), $sessionId)
            ->postJson(route('cart.add', $product), [
                'quantity' => 1,
                'dadi_reference' => $impression->reference,
                'conversation_id' => $conversation->getKey(),
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('cartCount', 1);
        $response->assertJsonPath('analytics.event', 'add_to_cart');

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'product_variant_id' => null,
        ]);

        $this->assertDatabaseHas('dadi_recommendation_events', [
            'conversation_id' => $conversation->getKey(),
            'product_id' => $product->id,
            'action' => 'add_to_cart',
            'session_id' => $sessionId,
            'user_id' => null,
        ]);

        $addEvent = DadiRecommendationEvent::query()
            ->where('conversation_id', $conversation->getKey())
            ->where('action', 'add_to_cart')
            ->first();

        $this->assertNotNull($addEvent);
        $this->assertNotSame($impression->reference, $addEvent->reference);
        $this->assertNotNull($addEvent->cart_id);
        $this->assertNotNull($addEvent->cart_item_id);
    }

    public function test_repeating_a_card_add_merges_into_the_same_cart_line(): void
    {
        $sessionId = $this->openGuestSession();
        $product = $this->products();
        $conversation = $this->store()->start(sessionId: $sessionId);
        $impression = $this->attribution()->impressionFor($conversation, $product);

        $payload = [
            'quantity' => 1,
            'dadi_reference' => $impression->reference,
            'conversation_id' => $conversation->getKey(),
        ];

        $this->guestCommerce($sessionId, ['product' => $product, 'body' => $payload])->assertJsonPath('cartCount', 1);
        $second = $this->guestCommerce($sessionId, ['product' => $product, 'body' => $payload]);

        $second->assertOk();
        $second->assertJsonPath('success', true);
        $second->assertJsonPath('cartCount', 2);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_client_supplied_price_and_internal_fields_are_never_trusted(): void
    {
        $sessionId = $this->openGuestSession();
        $guestCartKey = 'cart_'.Str::random(20);
        $product = $this->products();
        $conversation = $this->store()->start(sessionId: $sessionId);
        $impression = $this->attribution()->impressionFor($conversation, $product);

        $response = $this
            ->withCookie('vanriti_cart', $guestCartKey)
            ->withCredentials()
            ->withCookie(config('session.cookie'), $sessionId)
            ->postJson(route('cart.add', $product), [
                'quantity' => 1,
                'dadi_reference' => $impression->reference,
                'conversation_id' => $conversation->getKey(),
                'price' => 1.00,
                'selling_price' => 1.00,
                'stock' => 9999,
                'sku' => 'FORGED-SKU',
                'product_reference' => 424242,
            ]);

        $response->assertJsonPath('success', true);

        $cart = Cart::where('owner_type', 'guest')->where('session_id', $guestCartKey)->firstOrFail();
        $item = $cart->items()->first();

        $this->assertSame($product->id, (int) $item->product_id);
        $this->assertEqualsWithDelta(499.00, (float) $item->unit_price, 0.001);
        $this->assertSame(1, (int) $item->quantity);
    }

    public function test_an_unknown_reference_is_silently_ignored_but_commerce_still_succeeds(): void
    {
        $sessionId = $this->openGuestSession();
        $product = $this->products();

        $response = $this->guestCommerce($sessionId, [
            'product' => $product,
            'body' => [
                'quantity' => 1,
                'dadi_reference' => 'not-a-real-reference-1234567890',
                'conversation_id' => 999999,
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('cartCount', 1);

        $this->assertDatabaseCount('dadi_recommendation_events', 0);
    }

    public function test_a_reference_belonging_to_someone_else_is_ignored(): void
    {
        $owner = User::factory()->create();
        $ownerConversation = $this->store()->start(user: $owner);
        $product = $this->products();
        $ownerImpression = $this->attribution()->impressionFor($ownerConversation, $product);

        $attacker = User::factory()->create();
        $response = $this->actingAs($attacker, 'web')->postJson(route('cart.add', $product), [
            'quantity' => 1,
            'dadi_reference' => $ownerImpression->reference,
            'conversation_id' => $ownerConversation->getKey(),
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('carts', [
            'owner_type' => User::class,
            'owner_id' => $attacker->id,
        ]);

        $this->assertDatabaseMissing('dadi_recommendation_events', [
            'conversation_id' => $ownerConversation->getKey(),
            'action' => 'add_to_cart',
        ]);
    }

    public function test_an_authenticated_add_records_attribution_with_the_user(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user);
        $product = $this->products();
        $impression = $this->attribution()->impressionFor($conversation, $product);

        $response = $this->actingAs($user, 'web')->postJson(route('cart.add', $product), [
            'quantity' => 1,
            'dadi_reference' => $impression->reference,
            'conversation_id' => $conversation->getKey(),
        ]);

        $response->assertJsonPath('cartCount', 1);

        $this->assertDatabaseHas('dadi_recommendation_events', [
            'conversation_id' => $conversation->getKey(),
            'product_id' => $product->id,
            'action' => 'add_to_cart',
            'user_id' => $user->id,
        ]);
    }

    public function test_buy_now_reuses_the_existing_checkout_redirect_and_records_buy_now(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user);
        $product = $this->products();
        $impression = $this->attribution()->impressionFor($conversation, $product);

        $response = $this->actingAs($user, 'web')->postJson(route('cart.add', $product), [
            'quantity' => 1,
            'buy_now' => 1,
            'dadi_reference' => $impression->reference,
            'conversation_id' => $conversation->getKey(),
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('redirect', route('checkout.index'));
        $response->assertJsonPath('redirect_only', true);

        $this->assertDatabaseHas('dadi_recommendation_events', [
            'conversation_id' => $conversation->getKey(),
            'product_id' => $product->id,
            'action' => 'buy_now',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('dadi_recommendation_events', [
            'conversation_id' => $conversation->getKey(),
            'product_id' => $product->id,
            'action' => 'add_to_cart',
        ]);
    }

    public function test_out_of_stock_recommendation_rejects_the_add_without_attribution(): void
    {
        $sessionId = $this->openGuestSession();
        $product = Product::factory()->active()->create(['stock' => 0]);
        $conversation = $this->store()->start(sessionId: $sessionId);
        $impression = $this->attribution()->impressionFor($conversation, $product);

        $response = $this->guestCommerce($sessionId, [
            'product' => $product,
            'body' => [
                'quantity' => 1,
                'dadi_reference' => $impression->reference,
                'conversation_id' => $conversation->getKey(),
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseMissing('dadi_recommendation_events', [
            'conversation_id' => $conversation->getKey(),
            'action' => 'add_to_cart',
        ]);
    }

    public function test_an_inactive_recommendation_cannot_be_added(): void
    {
        $sessionId = $this->openGuestSession();
        $product = Product::factory()->create(['status' => 'inactive', 'stock' => 20]);
        $conversation = $this->store()->start(sessionId: $sessionId);
        $impression = $this->attribution()->impressionFor($conversation, $product);

        $response = $this->guestCommerce($sessionId, [
            'product' => $product,
            'body' => [
                'quantity' => 1,
                'dadi_reference' => $impression->reference,
                'conversation_id' => $conversation->getKey(),
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseMissing('dadi_recommendation_events', [
            'conversation_id' => $conversation->getKey(),
            'action' => 'add_to_cart',
        ]);
    }

    public function test_quantity_bounds_are_enforced_by_the_existing_add_endpoint(): void
    {
        $sessionId = $this->openGuestSession();
        $product = $this->products();

        $response = $this->guestCommerce($sessionId, [
            'product' => $product,
            'body' => ['quantity' => 100],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('cart_items', 0);
    }

    /* ---------------- recommendation card surface (variant handling) ---------------- */

    public function test_recommendation_card_exposes_only_the_first_in_stock_variant_for_add(): void
    {
        $product = Product::factory()->active()->create(['name' => 'Varietal Serum', 'stock' => 30]);
        $this->approveProduct($product);

        $outOfStock = ProductVariant::factory()->create(['product_id' => $product->id, 'stock' => 0, 'sort_order' => 1, 'status' => 'active']);
        $inStock = ProductVariant::factory()->create(['product_id' => $product->id, 'stock' => 12, 'sort_order' => 2, 'status' => 'active']);

        $this->provider($this->recommendationReply());

        $response = $this->postJson(route('dadi.message'), ['message' => 'mere baal dryness se toot rahe hain']);

        $response->assertOk();
        $response->assertJsonCount(1, 'recommendations');
        $response->assertJsonPath('recommendations.0.product_reference', (int) $product->id);
        $response->assertJsonPath('recommendations.0.variant_id', (int) $inStock->id);
        $response->assertJsonPath('recommendations.0.addable', true);
        $response->assertJsonPath('recommendations.0.add_url', route('cart.add', $product));
        $response->assertJsonPath('recommendations.0.product_url', route('product.show', $product));

        $this->assertIsString($response->json('recommendations.0.reference'));
        $this->assertNotSame('', $response->json('recommendations.0.reference'));
    }

    public function test_a_recommendation_that_goes_out_of_stock_becomes_view_only(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->active()->create(['name' => 'Varietal Serum', 'stock' => 30]);
        $this->approveProduct($product);

        $inStock = ProductVariant::factory()->create(['product_id' => $product->id, 'stock' => 12, 'sort_order' => 2, 'status' => 'active']);

        $this->provider($this->recommendationReply());

        // While in stock the card is addable.
        $turn = $this->actingAs($user, 'web')->postJson(route('dadi.message'), ['message' => 'mere baal dryness se toot rahe hain']);

        $turn->assertOk();
        $turn->assertJsonPath('recommendations.0.addable', true);
        $turn->assertJsonPath('recommendations.0.variant_id', (int) $inStock->id);
        $turn->assertJsonPath('recommendations.0.add_url', route('cart.add', $product));

        // Stock disappears between the turn and the next page load.
        $inStock->update(['stock' => 0]);

        // The rehydrated card is now view-only: no commerce buttons, no add URL.
        $page = $this->actingAs($user, 'web')->get(route('dadi.index'));

        $page->assertOk();
        $page->assertSee('dadi-recs', false);
        $page->assertSee('data-dadi-ref=', false);
        $page->assertSee(route('product.show', $product), false);
        $page->assertDontSee('js-dadi-add', false);
        $page->assertDontSee('js-dadi-buy-now', false);
        $page->assertDontSee('Cart mein rakho', false);
    }

    /* ---------------- impressions & clicks ---------------- */

    public function test_impressions_are_deduplicated_per_conversation_and_product(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user);
        $product = $this->products();

        $first = $this->attribution()->impressionFor($conversation, $product);
        $second = $this->attribution()->impressionFor($conversation, $product);

        $this->assertSame((int) $first->getKey(), (int) $second->getKey());
        $this->assertSame($first->reference, $second->reference);
        $this->assertDatabaseCount('dadi_recommendation_events', 1);
    }

    public function test_click_beacon_records_owned_clicks_and_ignores_forged_or_unknown_references(): void
    {
        $user = User::factory()->create();
        $conversation = $this->store()->start(user: $user);
        $product = $this->products();
        $impression = $this->attribution()->impressionFor($conversation, $product);

        $owned = $this->actingAs($user, 'web')->postJson(route('dadi.recommendation.click'), [
            'reference' => $impression->reference,
        ]);
        $owned->assertOk();
        $owned->assertJsonPath('tracked', true);

        $this->assertDatabaseHas('dadi_recommendation_events', [
            'conversation_id' => $conversation->getKey(),
            'product_id' => $product->id,
            'action' => 'click',
            'user_id' => $user->id,
        ]);

        // Another user's reference must be ignored.
        $other = User::factory()->create();
        $this->actingAs($other, 'web')->postJson(route('dadi.recommendation.click'), [
            'reference' => $impression->reference,
        ])->assertOk();

        // Unknown reference must be ignored.
        $this->actingAs($user, 'web')->postJson(route('dadi.recommendation.click'), [
            'reference' => 'totally-made-up',
        ])->assertOk();

        $this->assertSame(
            1,
            DadiRecommendationEvent::query()->where('action', 'click')->count(),
        );
    }

    public function test_guest_clicks_require_the_same_session(): void
    {
        $sessionId = $this->openGuestSession();
        $conversation = $this->store()->start(sessionId: $sessionId);
        $product = $this->products();
        $impression = $this->attribution()->impressionFor($conversation, $product);

        // A different session cannot record the click.
        $this->postJson(route('dadi.recommendation.click'), [
            'reference' => $impression->reference,
        ])->assertOk();

        $this->assertSame(
            0,
            DadiRecommendationEvent::query()->where('action', 'click')->count(),
        );
    }

    /* ---------------- the page renders the commerce surface ---------------- */

    public function test_recommendation_page_renders_the_commerce_card_surface(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create(['name' => 'Baal Tonic', 'stock' => 20]);
        $conversation = $this->store()->start(user: $user);
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_USER, 'product batao');
        $this->store()->appendMessage($conversation, DadiMessage::ROLE_ASSISTANT, 'yeh lo beta', [
            'recommended_product_references' => [$product->id],
        ]);

        $response = $this->actingAs($user, 'web')->get(route('dadi.index'));

        $response->assertOk();
        $response->assertSee('dadi-recs', false);
        $response->assertSee('js-dadi-add', false);
        $response->assertSee('js-dadi-buy-now', false);
        $response->assertSee('Cart mein rakho', false);
        $response->assertSee('data-dadi-ref=', false);
        $response->assertSee(route('product.show', $product), false);

        // The rendered page persists exactly one deduplicated impression.
        $this->assertDatabaseCount('dadi_recommendation_events', 1);
        $this->assertDatabaseHas('dadi_recommendation_events', [
            'conversation_id' => $conversation->getKey(),
            'product_id' => $product->id,
            'action' => 'impression',
        ]);
    }

    /* ---------------- guest → user reconcile + purchase attribution ---------------- */

    public function test_guest_events_reconcile_on_login_and_purchase_is_attributed(): void
    {
        $sessionId = $this->openGuestSession();
        $guestCartKey = 'cart_'.Str::random(20);
        $product = $this->products();
        $conversation = $this->store()->start(sessionId: $sessionId);
        $impression = $this->attribution()->impressionFor($conversation, $product);

        // Guest adds the recommended product to the (guest) cart.
        $added = $this
            ->withCookie('vanriti_cart', $guestCartKey)
            ->withCredentials()
            ->withCookie(config('session.cookie'), $sessionId)
            ->postJson(route('cart.add', $product), [
                'quantity' => 1,
                'dadi_reference' => $impression->reference,
                'conversation_id' => $conversation->getKey(),
            ]);
        $added->assertJsonPath('success', true);

        $user = User::factory()->create();

        // Rebind an authenticated request carrying the guest cart cookie so the
        // merge sees it and routes the items into the user's cart.
        $this
            ->actingAs($user, 'web')
            ->withCookie('vanriti_cart', $guestCartKey)
            ->withCredentials()
            ->withCookie(config('session.cookie'), $sessionId)
            ->get(route('dadi.index'))
            ->assertOk();

        // The real login seam: CartService::mergeGuestCartIntoUser().
        app(CartService::class)->mergeGuestCartIntoUser($user);

        // The add_to_cart/impression events now belong to the user.
        $this->assertDatabaseHas('dadi_recommendation_events', [
            'conversation_id' => $conversation->getKey(),
            'product_id' => $product->id,
            'action' => 'add_to_cart',
            'user_id' => $user->id,
        ]);

        // The guest cart was merged into a user-owned cart.
        $userCart = Cart::where('owner_type', User::class)->where('owner_id', $user->id)->firstOrFail();
        $this->assertSame(1, (int) $userCart->items()->first()->quantity);

        // Placing the order records purchase attribution linked to the conversation.
        $order = app(OrderService::class)->placeOrder(
            $user,
            $userCart,
            $this->orderData(),
        );

        $this->assertDatabaseHas('dadi_recommendation_events', [
            'conversation_id' => $conversation->getKey(),
            'product_id' => $product->id,
            'action' => 'purchase',
            'user_id' => $user->id,
            'order_number' => $order->order_number,
        ]);
    }

    public function test_an_order_without_dadi_events_records_no_purchase_attribution(): void
    {
        $user = User::factory()->create();
        $product = $this->products();

        $cart = Cart::create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
        ]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->selling_price,
            'mrp' => $product->mrp,
            'gst_rate' => $product->gst_rate,
        ]);

        $order = app(OrderService::class)->placeOrder($user, $cart->fresh(), $this->orderData());

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('dadi_recommendation_events', [
            'order_number' => $order->order_number,
            'action' => 'purchase',
        ]);
    }

    /* ---------------- helpers ---------------- */

    /**
     * @return array<string,mixed>
     */
    private function orderData(): array
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
