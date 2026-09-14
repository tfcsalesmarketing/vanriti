<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Analytics\MetaCapiService;
use App\Services\OrderService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * M4.4 — Meta Advanced Matching readiness.
 *
 * Locks the semantics of the EXISTING (still-disabled) CAPI user_data path and
 * the existing browser Pixel bootstrap. Uses synthetic values only. No
 * production code is exercised beyond what already exists.
 */
class MetaMatchingReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    protected function setPixelId(string $id = 'M44PIXEL'): void
    {
        Setting::updateOrCreate(['key' => 'meta_pixel_id'], [
            'value' => $id,
            'group' => 'seo',
            'label' => 'Meta Pixel ID',
            'type' => 'text',
        ]);
    }

    protected function address(string $mobile = '9876543210'): array
    {
        return [
            'full_name' => 'Test Buyer',
            'mobile' => $mobile,
            'address_line1' => '42 MG Road',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560038',
            'country' => 'India',
        ];
    }

    protected function placeOrderFor(User $user, string $mobile): Order
    {
        $product = Product::factory()->active()->create([
            'selling_price' => 200.00,
            'mrp' => 200.00,
            'gst_rate' => 5,
            'stock' => 10,
        ]);

        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 200.00,
            'mrp' => 200.00,
            'gst_rate' => 5,
        ]);

        return app(OrderService::class)->placeOrder($user, $cart, [
            'billing' => $this->address($mobile),
            'shipping' => $this->address($mobile),
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 1. Browser Pixel bootstrap carries ZERO Advanced Matching data
    // ──────────────────────────────────────────────────────────────────────
    public function test_browser_pixel_init_has_no_advanced_matching_data(): void
    {
        $this->setPixelId();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString("fbq('init', 'M44PIXEL');", $html);
        $this->assertSame(1, substr_count($html, "fbq('init',"));

        // ANY second argument to init (e.g. the Advanced Matching object) is prohibited.
        $this->assertStringNotContainsString("fbq('init', 'M44PIXEL', {", $html);
        $this->assertStringNotContainsString("'em'", $html);
        $this->assertStringNotContainsString("'ph'", $html);
        $this->assertStringNotContainsString('external_id', $html);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 2. Email is SHA-256 hashed AFTER Meta-compatible normalization
    //    Mixed case and +tag preserved verbatim apart from lowercasing.
    // ──────────────────────────────────────────────────────────────────────
    public function test_capi_email_is_normalized_then_hashed(): void
    {
        $user = User::factory()->create(['email' => 'Test.User+Example@Example.COM']);
        $order = $this->placeOrderFor($user, '9876543210');

        $payload = app(MetaCapiService::class)->buildPayload($order);

        $expected = [hash('sha256', 'test.user+example@example.com')];
        $this->assertSame($expected, $payload['user_data']['em']);

        // Local-part dots and the +tag survive; only case and whitespace are adjusted.
        $this->assertNotSame([hash('sha256', 'testuserexampleexamplecom')], $payload['user_data']['em']);
        $this->assertNotSame([hash('sha256', 'test.userexampleexample.com')], $payload['user_data']['em']);

        // No plaintext email in the payload.
        $this->assertStringNotContainsString('Test.User+Example@Example.COM', json_encode($payload));
    }

    // ──────────────────────────────────────────────────────────────────────
    // 3. Phone normalisation maps every common Indian format to one E.164 hash
    // ──────────────────────────────────────────────────────────────────────
    public function test_capi_phone_formats_converge_on_single_e164_hash(): void
    {
        $phone = '9876543210';
        $expected = [hash('sha256', '91'.$phone)];

        $variants = [
            '9876543210',           // bare 10-digit
            '+91 98765 43210',      // +91 with spaces
            '919876543210',         // bare country code
            '09876543210',          // leading domestic trunk zero
            '98765-43210',          // hyphenated
            '(91) 98765 43210',     // parenthesised country code
        ];

        $service = app(MetaCapiService::class);

        foreach ($variants as $variant) {
            $user = User::factory()->create(['email' => null, 'phone' => $variant]);
            $order = $this->placeOrderFor($user, $variant);

            $payload = $service->buildPayload($order);

            $this->assertSame(
                $expected,
                $payload['user_data']['ph'] ?? null,
                "Phone variant [{$variant}] did not converge on E.164 {$phone}."
            );
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // 4. No double hashing: value equals a single SHA-256 of the E.164 value
    // ──────────────────────────────────────────────────────────────────────
    public function test_capi_user_data_is_single_hashed_never_double(): void
    {
        $user = User::factory()->create(['email' => 'shopper@example.com', 'phone' => '9876543210']);
        $order = $this->placeOrderFor($user, '9876543210');

        $payload = app(MetaCapiService::class)->buildPayload($order);

        $canonicalPhone = '919876543210';
        $this->assertSame([hash('sha256', $canonicalPhone)], $payload['user_data']['ph']);
        $this->assertNotSame([hash('sha256', hash('sha256', $canonicalPhone))], $payload['user_data']['ph']);

        $this->assertSame([hash('sha256', 'shopper@example.com')], $payload['user_data']['em']);
        $this->assertNotSame([hash('sha256', hash('sha256', 'shopper@example.com'))], $payload['user_data']['em']);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 5. external_id is a stable SHA-256 of the internal user ID
    // ──────────────────────────────────────────────────────────────────────
    public function test_capi_external_id_is_stable_hash_of_user_id(): void
    {
        $user = User::factory()->create(['email' => null, 'phone' => '9876543210']);
        $order = $this->placeOrderFor($user, '9876543210');

        $service = app(MetaCapiService::class);

        $this->assertSame([hash('sha256', (string) $order->user_id)], $service->buildPayload($order)['user_data']['external_id']);
        $this->assertSame(
            $service->buildPayload($order)['user_data']['external_id'],
            $service->buildPayload($order->fresh())['user_data']['external_id'],
            'external_id must be deterministic for the same order.'
        );
    }

    // ──────────────────────────────────────────────────────────────────────
    // 6. fbp / fbc / IP / User-Agent are forwarded RAW, conditionally omitted
    // ──────────────────────────────────────────────────────────────────────
    public function test_capi_forwards_fbp_fbc_ip_and_user_agent_raw_not_hashed(): void
    {
        $user = User::factory()->create(['email' => null, 'phone' => '9876543210']);
        $order = $this->placeOrderFor($user, '9876543210');

        $service = app(MetaCapiService::class);

        $context = [
            'fbp' => '_fbp.1.1750000000000.1750000000000',
            'fbc' => 'fb.1.1750000000000.GMlXRQpyDveAAAaAAAAAAAAAA',
            'client_ip_address' => '203.0.113.10',
            'client_user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) TestAgent',
        ];

        $userData = $service->buildPayload($order, $context)['user_data'];

        $this->assertSame($context['fbp'], $userData['fbp']);
        $this->assertSame($context['fbc'], $userData['fbc']);
        $this->assertSame($context['client_ip_address'], $userData['client_ip_address']);
        $this->assertSame($context['client_user_agent'], $userData['client_user_agent']);

        // Raw identifiers (Meta requires raw _fbp/_fbc); never hashed.
        $this->assertStringContainsString('_fbp.1.1750000000000.1750000000000', json_encode($userData));

        // Absent when not provided.
        $empty = $service->buildPayload($order)['user_data'];
        $this->assertArrayNotHasKey('fbp', $empty);
        $this->assertArrayNotHasKey('fbc', $empty);
        $this->assertArrayNotHasKey('client_ip_address', $empty);
        $this->assertArrayNotHasKey('client_user_agent', $empty);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 7. event_source_url is the success URL with no query string
    // ──────────────────────────────────────────────────────────────────────
    public function test_capi_event_source_url_is_clean_success_url(): void
    {
        $user = User::factory()->create(['email' => null, 'phone' => '9876543210']);
        $order = $this->placeOrderFor($user, '9876543210');

        $service = app(MetaCapiService::class);
        $payload = $service->buildPayload($order);

        $this->assertStringContainsString('/checkout/success/'.$order->id, $payload['event_source_url']);
        $this->assertStringNotContainsString('?', $payload['event_source_url']);
        $this->assertStringNotContainsString('email', $payload['event_source_url']);
        $this->assertStringNotContainsString('token', $payload['event_source_url']);
        $this->assertStringNotContainsString('otp', $payload['event_source_url']);

        // Overridable via context (used by the queued job).
        $withContext = $service->buildPayload($order, ['event_source_url' => 'https://example.test/from-context']);
        $this->assertSame('https://example.test/from-context', $withContext['event_source_url']);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 8. transaction_id IS order_number; event_id is DIFFERENT and deterministic
    // ──────────────────────────────────────────────────────────────────────
    public function test_capi_transaction_id_differs_from_deterministic_event_id(): void
    {
        $user = User::factory()->create(['email' => null, 'phone' => '9876543210']);
        $order = $this->placeOrderFor($user, '9876543210');

        $service = app(MetaCapiService::class);

        $payload = $service->buildPayload($order);

        $this->assertSame((string) $order->order_number, $payload['custom_data']['transaction_id']);

        $this->assertNotSame($payload['custom_data']['transaction_id'], $payload['event_id']);
        $this->assertSame(64, strlen($payload['event_id']));

        $expectedEventId = hash_hmac('sha256', 'meta-capi-purchase:'.$order->order_number, (string) config('app.key'));
        $this->assertSame($expectedEventId, $service->eventId($order));
        $this->assertSame($service->eventId($order), $service->eventId($order->fresh()));
    }

    // ──────────────────────────────────────────────────────────────────────
    // 9. CAPI stays disabled
    // ──────────────────────────────────────────────────────────────────────
    public function test_capi_remains_disabled(): void
    {
        $this->assertFalse(Config::get('meta.enabled'));

        $user = User::factory()->create(['email' => null, 'phone' => '9876543210']);
        $order = $this->placeOrderFor($user, '9876543210');

        $service = app(MetaCapiService::class);
        $this->assertFalse($service->isConfigured());

        $service->deliver($order); // must be a silent no-op
        $this->assertTrue(true);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 10. Browser Purchase payload carries NO PII and NO Advanced Matching keys
    // ──────────────────────────────────────────────────────────────────────
    public function test_browser_purchase_payload_contains_no_customer_pii(): void
    {
        $this->setPixelId();

        $user = User::factory()->create(['email' => 'buyer@example.com', 'phone' => '9876543210']);
        $order = $this->placeOrderFor($user, '9876543210');

        $html = $this->actingAs($user, 'web')
            ->get(route('checkout.success', $order))
            ->assertOk()
            ->getContent();

        if (preg_match("/fbq\('track', 'Purchase', (\{.*\}), \{eventID/", $html, $m) !== 1) {
            $this->fail('Purchase fbq block not rendered.');
        }

        $script = $m[1];

        // No Advanced Matching keys on the browser payload.
        foreach (['em', 'ph', 'external_id', 'fn', 'ln', 'ct', 'st', 'zp', 'country'] as $key) {
            $this->assertStringNotContainsString("\"$key\"", $script, "Browser Purchase must not carry [{$key}].");
        }

        // No plaintext customer identity.
        $this->assertStringNotContainsString('buyer@example.com', $script);
        $this->assertStringNotContainsString('9876543210', $script);
    }
}
