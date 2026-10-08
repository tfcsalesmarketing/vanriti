<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\OrderService;
use App\Services\RefundService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Guest checkout: cart -> checkout -> COD/Razorpay -> secure guest order
 * status pages -> optional post-purchase account creation -> guest refund
 * analytics handoff. Complements CheckoutTest (authenticated checkout).
 */
class GuestCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected const GUEST_SESSION = 'guest_checkout_test_sess';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    protected function addressData(string $paymentMethod = 'cod'): array
    {
        return [
            'shipping_name' => 'Aarav Mehta',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => '42 MG Road',
            'shipping_address_line2' => 'Indiranagar',
            'shipping_landmark' => 'Near Metro',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'shipping_country' => 'India',
            'billing_same' => '1',
            'shipping_method' => 'standard',
            'payment_method' => $paymentMethod,
            'notes' => 'Please deliver after 6pm',
            'email' => 'guest@vanriti.test',
        ];
    }

    protected function makeProduct(array $overrides = []): Product
    {
        $product = Product::factory()->active()->create(array_merge([
            'selling_price' => 800.00,
            'mrp' => 1000.00,
            'gst_rate' => 18,
            'stock' => 25,
        ], $overrides));

        Inventory::create([
            'stockable_type' => Product::class,
            'stockable_id' => $product->id,
            'stock_on_hand' => $product->stock,
            'low_stock_threshold' => $product->low_stock_threshold ?? 5,
        ]);

        return $product;
    }

    /**
     * Add a product to this test browser's guest cart (vanriti_cart cookie).
     */
    protected function seedGuestCart(int $qty = 2, array $productOverrides = []): Product
    {
        $product = $this->makeProduct($productOverrides);

        $this->withCookie('vanriti_cart', self::GUEST_SESSION)
            ->post(route('cart.add', $product), ['quantity' => $qty]);

        return $product;
    }

    /**
     * Place a guest COD order over HTTP. Returns [order, redirect location].
     */
    protected function placeGuestCod(array $extra = []): array
    {
        $this->seedGuestCart();

        $response = $this->post(
            route('checkout.store'),
            array_merge($this->addressData('cod'), $extra)
        );

        $response->assertRedirect();

        $order = Order::whereNull('user_id')->latest('id')->firstOrFail();

        return [$order, (string) $response->headers->get('Location')];
    }

    protected function enableRazorpay(): void
    {
        Setting::updateOrCreate(['key' => 'online_payment_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_key_id'], ['value' => 'rzp_test_key']);
        Setting::updateOrCreate(['key' => 'razorpay_key_secret'], ['value' => Crypt::encryptString('rzp_test_secret')]);
    }

    protected function fakeRazorpay(): void
    {
        Http::fake([
            'api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_GUESTTEST01',
                'amount' => 160000,
                'currency' => 'INR',
                'receipt' => 'VAN-GUEST-0001',
            ], 200),
            // Keep the defence-in-depth fetchPayment call on a stub so the
            // test never touches the network; a failed fetch falls back to
            // the signature check (documented gateway behaviour).
            'api.razorpay.com/v1/payments/*' => Http::response([
                'error' => ['description' => 'payment fetch intentionally not faked'],
            ], 400),
        ]);
    }

    // ────────────────────────────────────────────────────────────── pages ──

    public function test_guest_checkout_page_renders_without_login(): void
    {
        $this->seedGuestCart();

        $response = $this->get(route('checkout.index'));

        $response->assertOk();
        $response->assertViewIs('storefront.checkout.guest');
        $response->assertSee('Checking out as a <strong>guest</strong>', false);
        $response->assertSee('name="email"', false);
        // The address-book launcher button is auth-only; the modal script may
        // still mention it, so match the button markup itself.
        $response->assertDontSee('onclick="openAddressModal()"');
    }

    public function test_guest_with_empty_cart_is_redirected_to_cart_page(): void
    {
        $this->withCookie('vanriti_cart', self::GUEST_SESSION);

        $this->get(route('checkout.index'))->assertRedirect(route('cart.index'));
    }

    public function test_authenticated_checkout_page_still_uses_account_view(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user, 'web')->post(route('cart.add', $product), ['quantity' => 1]);

        $this->actingAs($user, 'web')
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertViewIs('storefront.checkout.index')
            ->assertSee('Add New Address');
    }

    // ───────────────────────────────────────────────── COD guest checkout ──

    public function test_guest_can_place_cod_order_with_secure_token(): void
    {
        [$order, $location] = $this->placeGuestCod();

        $this->assertNull($order->user_id);
        $this->assertSame('Aarav Mehta', $order->guest_name);
        $this->assertSame('9876543210', $order->guest_mobile);
        $this->assertSame('guest@vanriti.test', $order->guest_email);
        $this->assertSame(self::GUEST_SESSION, $order->guest_session_id);
        $this->assertMatchesRegularExpression('/^VAN-\d{4}-\d{6}$/', $order->order_number);
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('pending', $order->order_status);

        // Only the SHA-256 hash is persisted; the raw token lives in the
        // placing browser's session alone.
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $order->access_token);
        $raw = session('guest_order_tokens.'.$order->id);
        $this->assertIsString($raw);
        $this->assertNotSame('', $raw);
        $this->assertSame(hash('sha256', $raw), $order->access_token);

        // The success redirect carries the raw token so the URL keeps working
        // after the session is lost.
        $this->assertStringContainsString('/checkout/success/'.$order->id, $location);
        $this->assertStringContainsString('token=', $location);
        $this->assertSame('Order placed successfully!', session('success'));

        // Cart is released and no address book row is created for a guest.
        $cart = Cart::where('owner_type', 'guest')->where('session_id', self::GUEST_SESSION)->first();
        $this->assertSame(0, $cart?->items()->count() ?? 1);
        $this->assertDatabaseCount('addresses', 0);
    }

    // ──────────────────────────────────────────────── status page security ──

    public function test_guest_success_page_renders_for_owner_browser_with_create_account_cta(): void
    {
        [, $location] = $this->placeGuestCod();

        $this->get($location)
            ->assertOk()
            ->assertSee('Create your account', false)
            ->assertSee('View Order Details', false);
    }

    public function test_guest_success_page_renders_with_token_only_after_session_loss(): void
    {
        [, $location] = $this->placeGuestCod();

        // Simulate a lost session and a changed guest cookie: only the raw
        // token in the URL can authorise the page now.
        $this->flushSession();
        $this->withCookie('vanriti_cart', 'some_other_browser');

        $this->get($location)->assertOk()->assertSee('Create your account', false);
    }

    public function test_guest_status_pages_denied_without_token_session_or_matching_cookie(): void
    {
        [$order] = $this->placeGuestCod();

        $this->flushSession();
        $this->withCookie('vanriti_cart', 'some_other_browser');

        $this->get(route('checkout.success', $order))->assertForbidden();
        $this->get(route('checkout.failed', $order))->assertForbidden();
        $this->get(route('checkout.pending', $order))->assertForbidden();
    }

    public function test_account_order_is_denied_to_guest_visitors(): void
    {
        $user = User::factory()->create();
        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $product = $this->makeProduct();
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->selling_price,
            'mrp' => $product->mrp,
            'gst_rate' => $product->gst_rate,
        ]);

        $order = app(OrderService::class)->placeOrder($user, $cart->fresh(), [
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
            'coupon_code' => null,
            'notes' => null,
        ]);

        $this->withCookie('vanriti_cart', self::GUEST_SESSION);

        // Guest cookie + even a guessed guest-style token never opens an
        // account order: owner check wins first.
        $this->get(route('checkout.success', $order))->assertForbidden();
        $this->get(route('checkout.success', ['order' => $order, 'token' => 'not-the-owner-token']))
            ->assertForbidden();
    }

    // ──────────────────────────────────────────────────── Razorpay guest ──

    public function test_guest_can_complete_razorpay_checkout(): void
    {
        $this->enableRazorpay();
        $this->fakeRazorpay();
        $this->seedGuestCart();

        $create = $this->post(route('checkout.store'), $this->addressData('razorpay'));
        $create->assertOk()->assertJson([
            'success' => true,
            'razorpay_order_id' => 'order_GUESTTEST01',
            'currency' => 'INR',
        ]);

        $order = Order::whereNull('user_id')->firstOrFail();
        $this->assertSame('processing', $order->payment_status);
        $this->assertSame(self::GUEST_SESSION, $order->guest_session_id);

        $signature = hash_hmac('sha256', 'order_GUESTTEST01'.'|'.'pay_GUESTPAY1', 'rzp_test_secret');

        $verify = $this->post(route('checkout.verify'), [
            'razorpay_order_id' => 'order_GUESTTEST01',
            'razorpay_payment_id' => 'pay_GUESTPAY1',
            'razorpay_signature' => $signature,
            'order_id' => $order->id,
        ]);

        $verify->assertRedirect();
        $location = (string) $verify->headers->get('Location');
        $this->assertStringContainsString('/checkout/success/'.$order->id, $location);
        $this->assertStringContainsString('token=', $location);

        $this->assertSame('paid', $order->fresh()->payment_status);

        $cart = Cart::where('owner_type', 'guest')->where('session_id', self::GUEST_SESSION)->first();
        $this->assertSame(0, $cart?->items()->count() ?? 1);
    }

    public function test_guest_razorpay_resubmission_resumes_same_pending_order(): void
    {
        $this->enableRazorpay();
        $this->fakeRazorpay();
        $this->seedGuestCart();

        $first = $this->post(route('checkout.store'), $this->addressData('razorpay'));
        $first->assertOk()->assertJson(['success' => true]);

        $order = Order::whereNull('user_id')->firstOrFail();

        $second = $this->post(route('checkout.store'), $this->addressData('razorpay'));
        $second->assertOk()->assertJson([
            'success' => true,
            'order_id' => $order->id,
        ]);

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_guest_cannot_verify_payment_of_an_unrelated_visitor(): void
    {
        $this->enableRazorpay();
        $this->fakeRazorpay();
        $this->seedGuestCart();

        $this->post(route('checkout.store'), $this->addressData('razorpay'))->assertOk();

        $order = Order::whereNull('user_id')->firstOrFail();

        // A different browser (no session, wrong cookie) with a perfectly
        // valid Razorpay signature still cannot flip this order's payment.
        $this->flushSession();
        $this->withCookie('vanriti_cart', 'some_other_browser');

        $signature = hash_hmac('sha256', 'order_GUESTTEST01'.'|'.'pay_GUESTPAY1', 'rzp_test_secret');

        $this->post(route('checkout.verify'), [
            'razorpay_order_id' => 'order_GUESTTEST01',
            'razorpay_payment_id' => 'pay_GUESTPAY1',
            'razorpay_signature' => $signature,
            'order_id' => $order->id,
        ])->assertForbidden();

        $this->assertSame('processing', $order->fresh()->payment_status);
    }

    // ────────────────────────────────────────────────────────── validation ──

    public function test_guest_store_rejects_invalid_payload_without_creating_order(): void
    {
        $this->seedGuestCart();

        $this->post(route('checkout.store'), [
            'payment_method' => 'cod',
            'shipping_method' => 'standard',
            'billing_same' => '1',
            'email' => 'not-an-email',
        ])->assertSessionHasErrors([
            'shipping_name', 'shipping_mobile', 'shipping_address_line1',
            'shipping_city', 'shipping_state', 'shipping_pincode', 'email',
        ]);

        $this->assertDatabaseCount('orders', 0);
    }

    // ───────────────────────────────────────────────────────────── coupons ──

    public function test_first_order_only_coupon_is_rejected_for_guest_at_apply_time(): void
    {
        $this->seedGuestCart();

        Coupon::factory()->create([
            'code' => 'FIRST10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_cart_value' => 0,
            'max_discount' => null,
            'first_order_only' => true,
            'per_customer_limit' => 1,
            'usage_limit' => 0,
        ]);

        $this->post(route('cart.coupon'), ['code' => 'FIRST10']);

        $this->assertNotNull(session('error'));
        $this->assertNull(session('cart_coupon'));
    }

    public function test_global_limit_coupon_counts_guest_redemptions(): void
    {
        Coupon::factory()->create([
            'code' => 'ONCE50',
            'discount_type' => 'fixed',
            'discount_value' => 50,
            'min_cart_value' => 0,
            'max_discount' => null,
            'first_order_only' => false,
            'per_customer_limit' => 0,
            'usage_limit' => 1,
        ]);

        // Guest A applies and checks out with the last remaining redemption.
        $this->seedGuestCart();
        $this->post(route('cart.coupon'), ['code' => 'ONCE50']);
        $this->assertNull(session('error'));

        $this->post(route('checkout.store'), $this->addressData('cod'))->assertRedirect();

        $order = Order::whereNull('user_id')->latest('id')->firstOrFail();
        $this->assertDatabaseHas('coupon_usages', [
            'coupon_id' => Coupon::where('code', 'ONCE50')->firstOrFail()->id,
            'order_id' => $order->id,
            'user_id' => null,
        ]);

        // Guest B (fresh session/browser) must now be refused.
        $this->flushSession();
        $this->withCookie('vanriti_cart', 'guest_b_session');
        $this->seedGuestCart();

        $this->post(route('cart.coupon'), ['code' => 'ONCE50']);
        // isUsable() refuses because the guest's null-user redemption already
        // consumed the single allowed use.
        $this->assertSame('This coupon has expired or reached its usage limit.', session('error'));
    }

    // ───────────────────────────────────────── post-purchase registration ──

    public function test_create_account_stages_link_and_prefill_then_redirects_to_register(): void
    {
        [$order] = $this->placeGuestCod();
        $raw = session('guest_order_tokens.'.$order->id);

        $this->get(route('checkout.create-account', ['order' => $order, 'token' => $raw]))
            ->assertRedirect(route('register'));

        $this->assertSame([(string) $order->id => $raw], session('post_purchase_link'));
        $this->assertSame('Aarav Mehta', session('post_purchase_prefill.name'));
        $this->assertSame('guest@vanriti.test', session('post_purchase_prefill.email'));
        $this->assertSame('9876543210', session('post_purchase_prefill.phone'));
    }

    public function test_registering_after_create_account_links_the_guest_order(): void
    {
        [$order] = $this->placeGuestCod();
        $raw = session('guest_order_tokens.'.$order->id);

        $this->get(route('checkout.create-account', ['order' => $order, 'token' => $raw]))
            ->assertRedirect(route('register'));

        $register = $this->post(route('register'), [
            'name' => 'Aarav Mehta',
            'email' => 'guest@vanriti.test',
            'phone' => '9876543210',
            'password' => 'GuestPass123!',
            'password_confirmation' => 'GuestPass123!',
        ]);

        $register->assertRedirect();
        $this->assertAuthenticated();
        $this->assertNull(session('post_purchase_link'));

        $fresh = $order->fresh();
        $this->assertNotNull($fresh->user_id);
        $this->assertSame(auth('web')->id(), $fresh->user_id);
        $this->assertFalse($fresh->isGuest());
    }

    public function test_register_without_staged_flag_leaves_guest_order_unlinked(): void
    {
        [$order] = $this->placeGuestCod();

        $this->post(route('register'), [
            'name' => 'Unrelated Person',
            'phone' => '9123456780',
            'password' => 'UnrelatedPass123!',
            'password_confirmation' => 'UnrelatedPass123!',
        ])->assertSessionMissing('errors');

        $this->assertAuthenticated();
        $this->assertNull($order->fresh()->user_id);
        $this->assertTrue($order->fresh()->isGuest());
    }

    public function test_create_account_route_enforces_ownership(): void
    {
        [$order] = $this->placeGuestCod();
        $raw = session('guest_order_tokens.'.$order->id);

        // No proof at all: different browser, lost session.
        $this->flushSession();
        $this->withCookie('vanriti_cart', 'some_other_browser');

        $this->get(route('checkout.create-account', ['order' => $order]))->assertForbidden();
        $this->get(route('checkout.create-account', ['order' => $order, 'token' => 'bogus-token']))
            ->assertForbidden();

        // An already-authenticated visitor is simply sent to their dashboard.
        $this->actingAs(User::factory()->create(), 'web');
        $this->get(route('checkout.create-account', ['order' => $order, 'token' => $raw]))
            ->assertRedirect(route('account.dashboard'));
    }

    // ──────────────────────────────────────────────────────────── tracking ──

    public function test_guest_can_track_order_without_login(): void
    {
        [$order] = $this->placeGuestCod();

        $this->post(route('track.lookup'), [
            'order_number' => $order->order_number,
            'mobile' => '9876543210',
        ])->assertOk()->assertSee($order->order_number, false);

        // The signed guest tracking link works without a session or account…
        $url = $order->guestTrackingUrl();
        $parts = parse_url($url);
        $this->get($parts['path'].'?'.$parts['query'])->assertOk();

        // …while the unsigned variant is refused.
        $this->get(route('track.order', ['order' => $order->order_number]))->assertForbidden();
    }

    // ───────────────────────────────────────────── guest refund analytics ──

    protected function deliveredGuestOrder(): Order
    {
        $product = $this->makeProduct([
            'sku' => 'GREF-001',
            'name' => 'VANRITI Guest Refund Serum',
            'selling_price' => 500.00,
            'mrp' => 500.00,
            'gst_rate' => 0,
        ]);

        $cart = Cart::create([
            'owner_type' => 'guest',
            'owner_id' => 0,
            'session_id' => self::GUEST_SESSION,
        ]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => $product->selling_price,
            'mrp' => $product->mrp,
            'gst_rate' => $product->gst_rate,
        ]);

        $order = app(OrderService::class)->placeOrder(null, $cart->fresh(), [
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
            'coupon_code' => null,
            'notes' => null,
            'guest' => [
                'name' => 'Aarav Mehta',
                'mobile' => '9876543210',
                'email' => 'guest@vanriti.test',
                'session_id' => self::GUEST_SESSION,
            ],
        ]);

        $order->update(['order_status' => 'delivered']);

        Payment::create([
            'order_id' => $order->id,
            'method' => 'cod',
            'amount' => $order->amount_due,
            'status' => 'paid',
        ]);

        return $order->fresh();
    }

    public function test_guest_refund_analytics_staged_under_guest_session_key(): void
    {
        $order = $this->deliveredGuestOrder();

        $service = app(RefundService::class);
        $refund = $service->createForOrder($order, null, 500.00, 'partial', 'Damaged on arrival');
        $refund = $service->approve($refund);
        $completed = $service->complete($refund);

        $this->assertSame('completed', $completed->status);

        $key = RefundService::PENDING_REFUND_CACHE_KEY.'guest_'.self::GUEST_SESSION;
        $pending = cache()->get($key);

        $this->assertIsArray($pending);
        $this->assertArrayHasKey($refund->id, $pending);
        $this->assertSame('refund', $pending[$refund->id]['event']);
        $this->assertSame($order->order_number, $pending[$refund->id]['ecommerce']['transaction_id']);

        // No account key was written for a guest refund.
        $this->assertNull(cache()->get(RefundService::PENDING_REFUND_CACHE_KEY.$order->user_id));
    }

    public function test_guest_refund_analytics_pulled_only_by_the_placing_browser(): void
    {
        $order = $this->deliveredGuestOrder();

        $service = app(RefundService::class);
        $refund = $service->complete($service->approve(
            $service->createForOrder($order, null, 500.00, 'partial', 'Damaged on arrival')
        ));

        $key = RefundService::PENDING_REFUND_CACHE_KEY.'guest_'.self::GUEST_SESSION;
        $this->assertNotNull(cache()->get($key));

        // An unrelated browser renders the storefront: the staged payload
        // must stay queued.
        $this->withCookie('vanriti_cart', 'some_other_browser')->get(route('home'));
        $this->assertNotNull(cache()->get($key));

        // The placing browser renders the storefront: pulled exactly once.
        $this->withCookie('vanriti_cart', self::GUEST_SESSION)->get(route('home'));
        $this->assertNull(cache()->get($key));

        $this->withCookie('vanriti_cart', self::GUEST_SESSION)->get(route('home'));
        $this->assertNull(cache()->get($key));
    }

    public function test_guest_magic_checkout_script_is_rendered(): void
    {
        $this->seedGuestCart();

        $response = $this->get(route('checkout.index'));

        $response->assertOk();
        $response->assertSee('https://checkout.razorpay.com/v1/magic-checkout.js', false);
        $response->assertDontSee('https://checkout.razorpay.com/v1/checkout.js', false);
    }

    public function test_guest_magic_checkout_one_click_checkout_flag_is_enabled(): void
    {
        $this->seedGuestCart();

        $response = $this->get(route('checkout.index'));

        $response->assertOk();
        $response->assertSee('one_click_checkout: true', false);
    }

    public function test_guest_magic_checkout_prefill_contact_is_present(): void
    {
        $this->seedGuestCart();

        $response = $this->get(route('checkout.index'));

        $response->assertOk();
        $response->assertSee('contact:', false);
    }

    public function test_guest_razorpay_order_creation_includes_line_items(): void
    {
        $this->enableRazorpay();
        $this->fakeRazorpay();
        $this->seedGuestCart();

        $this->post(route('checkout.store'), $this->addressData('razorpay'))->assertOk();

        Http::assertSent(function ($request) {
            $data = $request->data();

            return isset($data['line_items_total'])
                && $data['line_items_total'] === 160000
                && isset($data['line_items'])
                && is_array($data['line_items'])
                && count($data['line_items']) === 1
                && $data['line_items'][0]['sku'] !== ''
                && $data['line_items'][0]['quantity'] === 2
                && $data['line_items'][0]['price'] === 80000
                && $data['line_items'][0]['name'] !== '';
        });
    }

    public function test_guest_cod_does_not_invoke_magic_checkout(): void
    {
        $this->seedGuestCart();

        $response = $this->post(route('checkout.store'), $this->addressData('cod'));

        $response->assertRedirect();
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('cod', Payment::firstOrFail()->method);
    }

    // ─────────────────────────────── guest add to cart (no login gate) ──

    public function test_guest_can_add_to_cart_and_view_it_in_cart(): void
    {
        $product = $this->makeProduct();

        $add = $this->withCookie('vanriti_cart', self::GUEST_SESSION)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('cart.add', $product), ['quantity' => 1]);

        $add->assertOk();
        $json = $add->json();
        $this->assertTrue($json['success']);
        $this->assertSame(1, $json['cartCount']);

        $cart = $this->withCookie('vanriti_cart', self::GUEST_SESSION)->get(route('cart.index'));

        $cart->assertOk();
        $cart->assertSee($product->name);
    }

    public function test_guest_cart_quantity_update_and_remove(): void
    {
        $this->seedGuestCart(1);

        $item = Cart::where('owner_type', 'guest')
            ->where('session_id', self::GUEST_SESSION)
            ->firstOrFail()
            ->items()
            ->firstOrFail();

        $update = $this->withCookie('vanriti_cart', self::GUEST_SESSION)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('cart.update', $item->id), ['quantity' => 3]);

        $update->assertOk();
        $this->assertSame(3, $item->fresh()->quantity);

        $remove = $this->withCookie('vanriti_cart', self::GUEST_SESSION)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('cart.remove', $item->id));

        $remove->assertOk();
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_guest_cannot_update_another_visitors_cart_item(): void
    {
        $this->seedGuestCart(1);

        $item = Cart::where('owner_type', 'guest')
            ->where('session_id', self::GUEST_SESSION)
            ->firstOrFail()
            ->items()
            ->firstOrFail();

        // A different visitor (different vanriti_cart cookie) must not be able
        // to touch this item, even though route-model binding resolves it.
        $foreignUpdate = $this->withCookie('vanriti_cart', 'another_visitor_session')
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('cart.update', $item->id), ['quantity' => 5]);

        $foreignUpdate->assertStatus(422);
        $this->assertSame(1, $item->fresh()->quantity);

        $foreignRemove = $this->withCookie('vanriti_cart', 'another_visitor_session')
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('cart.remove', $item->id));

        $foreignRemove->assertStatus(422);
        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
    }

    public function test_product_page_renders_without_login_gate(): void
    {
        $product = $this->makeProduct();

        $response = $this->get(route('product.show', $product));

        $response->assertOk();
        // The client-side guest gates were removed; the wishlist meta flag stays.
        $response->assertDontSee('if (isGuest())', false);
        $response->assertSee('vr-is-guest', false);
    }

    public function test_guest_buy_now_does_not_require_login(): void
    {
        $product = $this->makeProduct();

        $response = $this->withCookie('vanriti_cart', self::GUEST_SESSION)
            ->post(route('cart.add', $product), ['quantity' => 1, 'buy_now' => 1]);

        $response->assertRedirect(route('checkout.index'));

        $this->withCookie('vanriti_cart', self::GUEST_SESSION)
            ->get(route('checkout.index'))
            ->assertOk();
    }
}
