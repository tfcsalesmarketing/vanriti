<?php

namespace Tests\Feature;

use App\Models\AnalyticsConversion;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Jobs\SendMetaCapiPurchase;
use App\Services\Analytics\ConversionService;
use App\Services\Analytics\EcommerceDataService;
use App\Services\Analytics\MetaCapiService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Step 7C: Meta Conversions API server-side Purchase. All external Meta
 * requests are faked; nothing here calls the real Graph API.
 */
class MetaCapiTest extends TestCase
{
    use RefreshDatabase;

    protected MetaCapiService $meta;

    protected const GRAPH_ENDPOINT = 'https://graph.facebook.com/v26.0/TEST1234/events';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->meta = app(MetaCapiService::class);
    }

    protected function enableMeta(): void
    {
        config(['meta.enabled' => true]);
        config(['meta.api_version' => '26.0']);
        config(['meta.max_attempts' => 3]);
        config(['meta.delivery_category' => 'home_delivery']);
        \App\Models\Setting::updateOrCreate(['key' => 'meta_pixel_id'], [
            'value' => 'TEST1234',
            'group' => 'seo',
            'label' => 'Meta Pixel ID',
            'type' => 'text',
        ]);
        \App\Models\Setting::updateOrCreate(['key' => 'meta_capi_access_token'], [
            'value' => 'secret_token',
            'group' => 'seo',
            'label' => 'Meta CAPI Access Token',
            'type' => 'password',
        ]);
    }

    protected function fakeGraphSuccess(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);
    }

    public function test_A_eligible_cod_purchase_delivers_exactly_one_server_conversion(): void
    {
        $this->enableMeta();
        $this->fakeGraphSuccess();

        $user = User::factory()->create();
        $product = $this->makeProduct('VNRT201', 800.00);
        $this->seedCart($user, $product, 2);

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('cod'))->assertRedirect();

        $order = Order::where('user_id', $user->id)->firstOrFail();

        $this->assertSame(1, AnalyticsConversion::where('event_type', 'purchase')->where('order_number', $order->order_number)->count());

        $this->assertCount(1, $this->graphRecorded());
        Http::assertSent(fn ($request) => $request->method() === 'POST' && str_starts_with($request->url(), self::GRAPH_ENDPOINT));

        $payload = $this->graphRecorded()[0][0]->data()['data'][0];
        $this->assertSame('purchase', strtolower($payload['event_name']));
        $this->assertEqualsWithDelta((float) $order->grand_total, $payload['custom_data']['value'], 0.01);
    }

    public function test_B_eligible_razorpay_purchase_delivers_exactly_one_server_conversion(): void
    {
        $this->enableMeta();
        $this->enableRazorpay();
        Http::fake([
            'api.razorpay.com/*' => Http::response(['id' => 'order_EZCAPI1', 'amount' => 160000, 'currency' => 'INR', 'receipt' => 'RCP'], 200),
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        $user = User::factory()->create();
        $product = $this->makeProduct('VNRT202', 800.00);
        $this->seedCart($user, $product, 2);

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('razorpay'))->assertOk()->assertJson(['success' => true]);

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $signature = hash_hmac('sha256', 'order_EZCAPI1'.'|'.'pay_CAPI1', 'rzp_test_secret');

        $this->actingAs($user, 'web')->post(route('checkout.verify'), [
            'razorpay_order_id' => 'order_EZCAPI1',
            'razorpay_payment_id' => 'pay_CAPI1',
            'razorpay_signature' => $signature,
            'order_id' => $order->id,
        ])->assertRedirect(route('checkout.success', $order));

        $this->assertSame('paid', $order->fresh()->payment_status);

        $this->assertSame(1, AnalyticsConversion::where('event_type', 'purchase')->where('order_number', $order->order_number)->count());
        $this->assertCount(1, $this->graphRecorded());
    }

    public function test_C_unverified_razorpay_payment_generates_no_capi_purchase(): void
    {
        $this->enableMeta();
        $this->enableRazorpay();
        Http::fake([
            'api.razorpay.com/*' => Http::response(['id' => 'order_EZCAPI2', 'amount' => 160000, 'currency' => 'INR', 'receipt' => 'RCP'], 200),
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        $user = User::factory()->create();
        $product = $this->makeProduct('VNRT203', 800.00);
        $this->seedCart($user, $product, 2);

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('razorpay'))->assertOk()->assertJson(['success' => true]);

        // Never verified server-side.
        $this->assertSame(0, AnalyticsConversion::count());
        $this->assertCount(0, $this->graphRecorded());
    }

    public function test_D_cancelled_order_generates_no_capi_purchase(): void
    {
        $this->enableMeta();
        $this->fakeGraphSuccess();

        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'pending');
        $order->update(['order_status' => 'cancelled']);

        $this->assertNull(app(ConversionService::class)->recordPurchase($order->fresh()));

        $this->meta->deliver($order->fresh(), ['event_source_url' => 'https://vanriti.test/checkout/success/1']);

        $this->assertCount(0, $this->graphRecorded());
    }

    public function test_E_failed_order_generates_no_capi_purchase(): void
    {
        $this->enableMeta();
        $this->fakeGraphSuccess();

        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'razorpay', 'failed');

        $this->assertNull(app(ConversionService::class)->recordPurchase($order->fresh()));

        $this->meta->deliver($order->fresh(), ['event_source_url' => 'https://vanriti.test/checkout/success/1']);

        $this->assertCount(0, $this->graphRecorded());
    }

    public function test_F_refunded_order_generates_no_capi_purchase(): void
    {
        $this->enableMeta();
        $this->fakeGraphSuccess();

        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'paid');
        $order->update(['payment_status' => 'refunded']);

        $this->meta->deliver($order->fresh(), ['event_source_url' => 'https://vanriti.test/checkout/success/1']);

        $this->assertCount(0, $this->graphRecorded());

        // Success page renders no browser Pixel Purchase either.
        $content = $this->actingAs($user, 'web')->get(route('checkout.success', $order->fresh()))->getContent();
        $this->assertStringNotContainsString("fbq('track', 'Purchase'", $content);
    }

    public function test_G_partially_refunded_order_generates_no_capi_purchase(): void
    {
        $this->enableMeta();
        $this->fakeGraphSuccess();

        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'paid');
        $order->update(['payment_status' => 'partially_refunded']);

        $this->meta->deliver($order->fresh(), ['event_source_url' => 'https://vanriti.test/checkout/success/1']);

        $this->assertCount(0, $this->graphRecorded());
    }

    public function test_H_repeated_conversion_recording_and_delivery_are_idempotent(): void
    {
        $this->enableMeta();
        $this->fakeGraphSuccess();

        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'pending');

        app(ConversionService::class)->recordPurchase($order);
        app(ConversionService::class)->recordPurchase($order);
        app(ConversionService::class)->recordPurchase($order);

        $this->assertSame(1, AnalyticsConversion::where('event_type', 'purchase')->where('order_number', $order->order_number)->count());

        $this->meta->deliver($order, ['event_source_url' => 'https://vanriti.test/checkout/success/1']);
        $this->meta->deliver($order, ['event_source_url' => 'https://vanriti.test/checkout/success/1']);
        $this->meta->deliver($order, ['event_source_url' => 'https://vanriti.test/checkout/success/1']);

        $this->assertCount(1, $this->graphRecorded());
        $this->assertSame('sent', AnalyticsConversion::first()->meta_state);
    }

    public function test_I_browser_pixel_and_capi_use_the_same_event_id(): void
    {
        $this->enableMeta();
        $this->fakeGraphSuccess();

        $user = User::factory()->create();
        $product = $this->makeProduct('VNRT204', 800.00);
        $this->seedCart($user, $product, 2);

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('cod'))->assertRedirect();

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $expected = $this->meta->eventId($order);

        // Browser Pixel Purchase carries the identical event_id.
        $content = $this->actingAs($user, 'web')->get(route('checkout.success', $order))->getContent();
        $this->assertStringContainsString('{eventID: "'.$expected.'"}', $content);

        // Server CAPI carries the identical event_id.
        $payload = $this->graphRecorded()[0][0]->data()['data'][0];
        $this->assertSame($expected, $payload['event_id']);

        // Standardized product identifier: browser Pixel and server CAPI both
        // use the SKU, matching the GA4 item_id on the same page.
        $this->assertStringContainsString('"id":"'.$order->items()->first()->sku.'"', $content);
        $this->assertSame((string) $order->items()->first()->sku, $payload['custom_data']['contents'][0]['id']);
    }

    public function test_J_capi_value_equals_canonical_grand_total(): void
    {
        $this->enableMeta();
        $this->fakeGraphSuccess();

        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'pending');
        app(ConversionService::class)->recordPurchase($order);

        $this->meta->deliver($order, ['event_source_url' => 'https://vanriti.test/checkout/success/1']);

        $payload = $this->graphRecorded()[0][0]->data()['data'][0];
        $this->assertEqualsWithDelta((float) $order->grand_total, $payload['custom_data']['value'], 0.01);
        $this->assertEqualsWithDelta((float) $order->grand_total, $payload['custom_data']['value'], 0.01);
    }

    public function test_K_capi_currency_is_inr(): void
    {
        $this->enableMeta();
        $this->fakeGraphSuccess();

        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'pending');
        app(ConversionService::class)->recordPurchase($order);

        $this->meta->deliver($order, ['event_source_url' => 'https://vanriti.test/checkout/success/1']);

        $payload = $this->graphRecorded()[0][0]->data()['data'][0];
        $this->assertSame('INR', $payload['custom_data']['currency']);
    }

    public function test_L_capi_contents_match_canonical_order_items(): void
    {
        $this->enableMeta();
        $this->fakeGraphSuccess();

        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'pending');
        app(ConversionService::class)->recordPurchase($order);

        $this->meta->deliver($order, ['event_source_url' => 'https://vanriti.test/checkout/success/1']);

        $contents = $this->graphRecorded()[0][0]->data()['data'][0]['custom_data']['contents'];

        $this->assertCount(1, $contents);
        $this->assertSame($order->items()->first()->sku, $contents[0]['id']);
        $this->assertSame(2, $contents[0]['quantity']);
        $this->assertEqualsWithDelta((float) $order->items()->first()->unit_price, $contents[0]['item_price'], 0.01);
        $this->assertSame('home_delivery', $contents[0]['delivery_category']);
    }

    public function test_M_no_access_token_appears_in_rendered_html(): void
    {
        $this->enableMeta();
        $this->fakeGraphSuccess();

        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'pending');

        $content = $this->actingAs($user, 'web')->get(route('checkout.success', $order))->getContent();

        $this->assertStringNotContainsString('secret_token', $content);
        $this->assertStringNotContainsString('access_token', $content);
    }

    public function test_N_no_access_token_appears_in_javascript_or_datalayer(): void
    {
        $this->enableMeta();
        $this->fakeGraphSuccess();

        $user = User::factory()->create();
        $product = $this->makeProduct('VNRT205', 300.00);
        $this->seedCart($user, $product, 1);

        foreach ([route('checkout.index'), route('cart.index'), route('shop.index')] as $page) {
            $content = $this->actingAs($user, 'web')->get($page)->getContent();
            $this->assertStringNotContainsString('secret_token', $content);
            $this->assertStringNotContainsString('access_token', $content);
        }

        $order = $this->makeOrder($user, 'cod', 'pending');
        $success = $this->actingAs($user, 'web')->get(route('checkout.success', $order))->getContent();
        $this->assertStringNotContainsString('secret_token', $success);
        $this->assertStringNotContainsString('access_token', $success);
    }

    public function test_O_no_plaintext_sensitive_user_data_is_transmitted_or_logged(): void
    {
        $this->enableMeta();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'boom', 'code' => 1]], 500),
        ]);

        $user = User::factory()->create(['email' => 'pii@example.com', 'phone' => '9812345670']);
        $order = $this->makeOrder($user, 'cod', 'pending');
        app(ConversionService::class)->recordPurchase($order);

        Log::spy();
        $this->meta->deliver($order, ['event_source_url' => 'https://vanriti.test/checkout/success/1']);

        // The transmitted user_data is SHA-256 hashed, never plaintext.
        $built = $this->meta->buildPayload($order, ['event_source_url' => 'https://vanriti.test/checkout/success/1']);
        $this->assertSame([hash('sha256', 'pii@example.com')], $built['user_data']['em']);
        $this->assertSame([hash('sha256', '919876543210')], $built['user_data']['ph']);
        $this->assertNotContains('pii@example.com', $built['user_data']['em']);
        $this->assertNotContains('9876543210', $built['user_data']['ph']);

        // Logs never contain the raw identifiers or the token.
        Log::shouldNotHaveReceived('error', [
            Mockery::on(fn ($message) => is_string($message)
                && (str_contains($message, 'pii@example.com')
                    || str_contains($message, '9876543210')
                    || str_contains($message, '9812345670')
                    || str_contains($message, 'secret_token'))),
            Mockery::any(),
        ]);
    }

    public function test_P_capi_failure_does_not_fail_checkout(): void
    {
        $this->enableMeta();
        $this->enableRazorpay();
        Http::fake([
            'graph.facebook.com/*' => Http::response([], 503),
        ]);

        // COD: Meta outage must still complete checkout and persist the order.
        $user = User::factory()->create();
        $product = $this->makeProduct('VNRT206', 800.00);
        $this->seedCart($user, $product, 2);

        $response = $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('cod'));
        $response->assertRedirect(route('checkout.success', Order::where('user_id', $user->id)->where('payment_method', 'cod')->firstOrFail()));

        $order = Order::where('user_id', $user->id)->where('payment_method', 'cod')->firstOrFail();
        $this->assertSame('pending', $order->fresh()->payment_status);
        $ledger = AnalyticsConversion::where('event_type', 'purchase')->where('order_number', $order->order_number)->firstOrFail();
        $this->assertSame('failed', $ledger->meta_state);

        // Razorpay: a verified payment still converts and completes checkout.
        $user2 = User::factory()->create();
        Http::fake([
            'api.razorpay.com/*' => Http::response(['id' => 'order_EZCAPI3', 'amount' => 160000, 'currency' => 'INR', 'receipt' => 'RCP'], 200),
            'graph.facebook.com/*' => Http::response([], 503),
        ]);
        $this->seedCart($user2, $product, 2);

        $this->actingAs($user2, 'web')->post(route('checkout.store'), $this->addressData('razorpay'))->assertOk()->assertJson(['success' => true]);

        $order2 = Order::where('user_id', $user2->id)->firstOrFail();
        $signature = hash_hmac('sha256', 'order_EZCAPI3'.'|'.'pay_CAPI3', 'rzp_test_secret');

        $this->actingAs($user2, 'web')->post(route('checkout.verify'), [
            'razorpay_order_id' => 'order_EZCAPI3',
            'razorpay_payment_id' => 'pay_CAPI3',
            'razorpay_signature' => $signature,
            'order_id' => $order2->id,
        ])->assertRedirect(route('checkout.success', $order2));

        $this->assertSame('paid', $order2->fresh()->payment_status);
        $ledger2 = AnalyticsConversion::where('event_type', 'purchase')->where('order_number', $order2->order_number)->firstOrFail();
        $this->assertSame('failed', $ledger2->meta_state);
    }

    public function test_Q_retry_behavior_is_bounded_and_idempotent(): void
    {
        $this->enableMeta();

        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'pending');
        app(ConversionService::class)->recordPurchase($order);

        // Deterministic: fail once, then succeed, for the same URL.
        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                ->push([], 500)
                ->push(['events_received' => 1], 200),
        ]);

        // First delivery fails -> state failed, attempts incremented.
        $this->meta->deliver($order, ['event_source_url' => 'https://vanriti.test/checkout/success/1']);
        $ledger = AnalyticsConversion::first();
        $this->assertSame('failed', $ledger->meta_state);
        $this->assertSame(1, $ledger->meta_attempts);

        // Retry succeeds -> sent.
        $this->meta->deliver($order, ['event_source_url' => 'https://vanriti.test/checkout/success/1']);
        $ledger->refresh();
        $this->assertSame('sent', $ledger->meta_state);
        $this->assertSame(2, $ledger->meta_attempts);

        // Already-sent: no further HTTP.
        $recordedAfterRetry = count($this->graphRecorded());
        $this->assertSame(2, $recordedAfterRetry);
        $this->meta->deliver($order, ['event_source_url' => 'https://vanriti.test/checkout/success/1']);
        $this->assertSame($recordedAfterRetry, count($this->graphRecorded()));

        // Attempt cap stops further attempts deterministically.
        $order2 = $this->makeOrder($user, 'cod', 'pending');
        app(ConversionService::class)->recordPurchase($order2);
        AnalyticsConversion::where('order_number', $order2->order_number)->update(['meta_state' => 'failed', 'meta_attempts' => 3]);
        $this->meta->deliver($order2, ['event_source_url' => 'https://vanriti.test/checkout/success/1']);
        $this->assertSame($recordedAfterRetry, count($this->graphRecorded()));
    }

    public function test_R_v26_payload_adheres_to_current_capi_schema(): void
    {
        $this->enableMeta();
        $this->fakeGraphSuccess();

        $user = User::factory()->create(['email' => 'schema@example.com']);
        $order = $this->makeOrder($user, 'cod', 'pending');
        app(ConversionService::class)->recordPurchase($order);

        // Every website-event field v26 expects, including the required
        // client_user_agent, the browser client_ip_address, and a Test Events
        // code routed through the job's setting lookup.
        \App\Models\Setting::updateOrCreate(['key' => 'meta_test_event_code'], ['value' => 'TEST-V26']);
        (new SendMetaCapiPurchase(
            $order,
            'https://vanriti.test/checkout/success/1',
            'fb.1.1558571054389.1098115397',
            'fb.1.1554763741205.AbCdEfGhIjKlMnOpQrStUvWxYz1234567890',
            '203.0.113.7',
            'Mozilla/5.0 (ops-validate)',
        ))->handle($this->meta);

        $request = $this->graphRecorded()[0][0];
        $body = $request->data();

        // Envelope: test_event_code is part of the top-level body.
        $this->assertSame('TEST-V26', $body['test_event_code']);

        $event = $body['data'][0];
        $this->assertSame('Purchase', $event['event_name']);
        $this->assertSame('website', $event['action_source']);
        $this->assertSame('https://vanriti.test/checkout/success/1', $event['event_source_url']);
        $this->assertSame(hash('sha256', 'schema@example.com'), $event['user_data']['em'][0]);
        $this->assertSame(hash('sha256', '919876543210'), $event['user_data']['ph'][0]);
        $this->assertSame(hash('sha256', (string) $order->user_id), $event['user_data']['external_id'][0]);

        // Browser-derived identifiers forwarded raw (never hashed).
        $this->assertSame('203.0.113.7', $event['user_data']['client_ip_address']);
        $this->assertSame('Mozilla/5.0 (ops-validate)', $event['user_data']['client_user_agent']);
        $this->assertSame('fb.1.1558571054389.1098115397', $event['user_data']['fbp']);
        $this->assertSame('fb.1.1554763741205.AbCdEfGhIjKlMnOpQrStUvWxYz1234567890', $event['user_data']['fbc']);

        $this->assertSame('INR', $event['custom_data']['currency']);
        $this->assertEqualsWithDelta((float) $order->grand_total, $event['custom_data']['value'], 0.01);
        $this->assertSame((string) $order->order_number, $event['custom_data']['transaction_id']);

        $item = $event['custom_data']['contents'][0];
        $this->assertSame((string) $order->items()->first()->sku, $item['id']);
        $this->assertSame('home_delivery', $item['delivery_category']);
        $this->assertArrayNotHasKey('product_id', $item);
    }

    public function test_R2_phone_is_normalized_to_e164_with_leading_zero_stripped(): void
    {
        $this->enableMeta();

        $user = User::factory()->create(['email' => 'pii@example.com']);
        $order = $this->makeOrder($user, 'cod', 'pending');
        $order->update(['billing_mobile' => '09876543210']);

        $payload = $this->meta->buildPayload($order, ['event_source_url' => 'https://vanriti.test/checkout/success/1']);

        // 09876543210 -> 9876543210 -> 919876543210, then SHA-256.
        $this->assertSame([hash('sha256', '919876543210')], $payload['user_data']['ph']);
        $this->assertNotContains('9876543210', $payload['user_data']['ph']);

        // Unresolvable numbers are dropped entirely, not invented.
        $order->update(['billing_mobile' => 'not-a-number']);
        $payload = $this->meta->buildPayload($order, ['event_source_url' => 'https://vanriti.test/checkout/success/1']);
        $this->assertArrayNotHasKey('ph', $payload['user_data']);
    }

    protected function graphRecorded(): array
    {
        return Http::recorded()
            ->filter(fn ($pair) => str_contains((string) $pair[0]->url(), 'graph.facebook.com'))
            ->values()
            ->all();
    }

    protected function makeProduct(string $sku, float $price): Product
    {
        return Product::factory()->active()->create([
            'sku' => $sku,
            'name' => 'VANRITI CAPI Balm',
            'selling_price' => $price,
            'mrp' => $price,
            'gst_rate' => 0,
            'stock' => 20,
        ]);
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
            'notes' => null,
        ];
    }

    protected function enableRazorpay(): void
    {
        \App\Models\Setting::updateOrCreate(['key' => 'online_payment_enabled'], ['value' => '1']);
        \App\Models\Setting::updateOrCreate(['key' => 'razorpay_enabled'], ['value' => '1']);
        \App\Models\Setting::updateOrCreate(['key' => 'razorpay_key_id'], ['value' => 'rzp_test_key']);
        \App\Models\Setting::updateOrCreate(['key' => 'razorpay_key_secret'], ['value' => 'rzp_test_secret']);
    }

    protected function makeOrder(User $user, string $paymentMethod, string $paymentStatus): Order
    {
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT2'.str_pad((string) mt_rand(10, 99), 2, '0', STR_PAD_LEFT),
            'name' => 'VANRITI Body Lotion',
            'selling_price' => 800.00,
            'mrp' => 800.00,
            'gst_rate' => 0,
        ]);

        $order = Order::create([
            'order_number' => 'VAN-7C-'.str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT),
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