<?php

namespace Tests\Feature;

use App\Consent\ConsentService;
use App\Consent\Enums\ConsentCategory;
use App\Jobs\SendOpenAiCapiOrder;
use App\Models\Admin;
use App\Models\AnalyticsConversion;
use App\Models\AnalyticsEvent;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\Analytics\AnalyticsCommandCenterService;
use App\Services\Analytics\ChatGptAdsAttributionService;
use App\Services\Analytics\ChatGptAdsReportingService;
use App\Services\Analytics\ConversionService;
use App\Services\Analytics\Exceptions\OpenAiTimestampException;
use App\Services\Analytics\OpenAiCapiService;
use App\Services\Analytics\OpenAiEcommercePayloadService;
use App\Services\Payments\PaymentService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ChatGptAdsConversionTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(SettingsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function manager(): Admin
    {
        $admin = Admin::factory()->create(['is_super_admin' => true]);
        $admin->roles()->attach(Role::where('slug', 'manager')->firstOrFail());

        return $admin;
    }

    protected function makeProduct(string $sku = 'TEST-SKU-1', float $price = 999.00): Product
    {
        return Product::factory()->active()->create([
            'sku' => $sku,
            'name' => 'Signature Cotton Kurti',
            'selling_price' => $price,
            'mrp' => $price,
            'stock' => 50,
            'gst_rate' => 0,
        ]);
    }

    protected function addressData(string $paymentMethod = 'cod'): array
    {
        return [
            'shipping_name' => 'Priya Sharma',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Flat 101, Palm Grove',
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

    protected function seedCart(User $user, Product $product, int $quantity = 1): Cart
    {
        $cart = Cart::create([
            'owner_type' => User::class,
            'owner_id' => $user->id,
        ]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $product->selling_price,
            'mrp' => $product->mrp,
            'gst_rate' => $product->gst_rate,
            'total_price' => $product->selling_price * $quantity,
        ]);

        return $cart;
    }

    /**
     * 1 & 2: Landing page captures oppref and UTMs into session and cookie.
     */
    public function test_captures_oppref_and_utm_on_landing_page(): void
    {
        $attributionService = app(ChatGptAdsAttributionService::class);

        $request = Request::create('/?oppref=test-click-ref-12345&utm_source=chatgpt&utm_medium=cpc&utm_campaign=summer_sale', 'GET');
        $captured = $attributionService->capture($request);

        $this->assertNotNull($captured);
        $this->assertSame('test-click-ref-12345', $captured['oppref']);
        $this->assertSame('chatgpt', $captured['source']);
        $this->assertSame('cpc', $captured['medium']);
        $this->assertSame('summer_sale', $captured['campaign']);

        $this->assertSame('test-click-ref-12345', session('chatgpt_ads.active_oppref'));
        $this->assertSame('summer_sale', session('chatgpt_ads.active_campaign'));
    }

    /**
     * 3: Attribution persistence across pages without oppref in subsequent URL.
     */
    public function test_attribution_persistence_across_subsequent_pages(): void
    {
        $attributionService = app(ChatGptAdsAttributionService::class);

        // First request with oppref
        $landingRequest = Request::create('/?oppref=initial-click-999&utm_campaign=festive', 'GET');
        $attributionService->capture($landingRequest);

        // Subsequent page view without any query parameters
        $nextRequest = Request::create('/products/some-item', 'GET');
        $resolved = $attributionService->resolve($nextRequest);

        $this->assertNotNull($resolved);
        $this->assertSame('initial-click-999', $resolved['oppref']);
        $this->assertSame('festive', $resolved['campaign']);
    }

    /**
     * 4: Attribution persists through login.
     */
    public function test_attribution_persists_through_customer_login(): void
    {
        $attributionService = app(ChatGptAdsAttributionService::class);

        $request = Request::create('/?oppref=login-flow-ref', 'GET');
        $attributionService->capture($request);

        $user = User::factory()->create();

        // Simulate login
        $this->actingAs($user, 'web');

        $resolved = $attributionService->resolve();
        $this->assertNotNull($resolved);
        $this->assertSame('login-flow-ref', $resolved['oppref']);
    }

    /**
     * 5: Attribution persists through checkout and attaches to Order.
     */
    public function test_attribution_attaches_to_order_upon_placement(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct('ATTR-PROD', 1200.0);
        $this->seedCart($user, $product, 1);

        session([
            'chatgpt_ads.active_oppref' => 'order-attribution-ref-xyz',
            'chatgpt_ads.active_source' => 'chatgpt',
            'chatgpt_ads.active_campaign' => 'diwali_special',
            'chatgpt_ads.active_medium' => 'cpc',
        ]);

        $this->actingAs($user, 'web')
            ->post(route('checkout.store'), $this->addressData('cod'))
            ->assertRedirect();

        $order = Order::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('order-attribution-ref-xyz', $order->oppref);
        $this->assertSame('chatgpt', $order->attribution_source);
        $this->assertSame('diwali_special', $order->attribution_campaign);
        $this->assertSame('cpc', $order->attribution_medium);
    }

    /**
     * 6: Product-view tracking payload format (minor units / paise).
     */
    public function test_product_view_payload_uses_official_minor_units(): void
    {
        $payloadService = app(OpenAiEcommercePayloadService::class);
        $product = $this->makeProduct('KURTI-01', 1499.50);

        $payload = $payloadService->contentsViewed($product);

        $this->assertSame('contents', $payload['type']);
        $this->assertSame(149950, $payload['amount']); // 1499.50 * 100 paise
        $this->assertSame('INR', $payload['currency']);
        $this->assertCount(1, $payload['contents']);
        $this->assertSame('KURTI-01', $payload['contents'][0]['id']);
        $this->assertSame(1, $payload['contents'][0]['quantity']);
        $this->assertSame(149950, $payload['contents'][0]['amount']);
    }

    /**
     * 7: Add-to-cart tracking payload format.
     */
    public function test_add_to_cart_payload_uses_unit_amount_and_total_amount(): void
    {
        $payloadService = app(OpenAiEcommercePayloadService::class);
        $product = $this->makeProduct('KURTI-02', 800.00);

        $payload = $payloadService->itemsAdded($product, null, 3);

        $this->assertSame('contents', $payload['type']);
        $this->assertSame(240000, $payload['amount']); // 3 * 800 * 100
        $this->assertSame('INR', $payload['currency']);
        $this->assertSame(3, $payload['contents'][0]['quantity']);
        $this->assertSame(80000, $payload['contents'][0]['amount']); // Unit price in paise
    }

    /**
     * 8: Checkout started payload format.
     */
    public function test_checkout_started_payload_format(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct('CHECKOUT-01', 500.00);
        $cart = $this->seedCart($user, $product, 2);

        $payloadService = app(OpenAiEcommercePayloadService::class);
        $payload = $payloadService->checkoutStarted($cart);

        $this->assertSame('contents', $payload['type']);
        $this->assertSame(100000, $payload['amount']);
        $this->assertSame(2, $payload['contents'][0]['quantity']);
        $this->assertSame(50000, $payload['contents'][0]['amount']);
    }

    /**
     * 9: Prepaid purchase not sent before payment; sent after verified payment.
     */
    public function test_prepaid_purchase_sent_only_after_verified_payment(): void
    {
        Queue::fake();

        Setting::updateOrCreate(['key' => 'razorpay_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'online_payment_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_key_id'], ['value' => 'rzp_test_key']);
        Setting::updateOrCreate(['key' => 'razorpay_key_secret'], ['value' => \Illuminate\Support\Facades\Crypt::encryptString('rzp_test_secret')]);

        Http::fake([
            'api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_RZP_TEST_01',
                'amount' => 150000,
                'currency' => 'INR',
                'receipt' => 'REC-001',
            ], 200),
        ]);

        $user = User::factory()->create();
        $product = $this->makeProduct('PREPAID-01', 1500.00);
        $this->seedCart($user, $product, 1);

        // Configure OpenAI
        config(['openai_ads.enabled' => true, 'openai_ads.pixel_id' => 'px_test_123', 'openai_ads.capi_key' => 'key_test_123']);

        // Submit checkout with razorpay
        $this->actingAs($user, 'web')
            ->post(route('checkout.store'), $this->addressData('razorpay'))
            ->assertOk();

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertContains($order->payment_status, ['pending', 'processing']);
        $this->assertNotSame('paid', $order->payment_status);

        // Must NOT have created conversion ledger row yet
        $this->assertNull(AnalyticsConversion::where('event_type', 'order_created')->where('order_number', $order->order_number)->first());
        Queue::assertNotPushed(SendOpenAiCapiOrder::class);

        // Now simulate verified payment
        $payment = $order->payments()->first();
        $paymentService = app(PaymentService::class);
        $paymentService->markPaid($payment);

        $conversionService = app(ConversionService::class);
        $conversionService->recordOpenAiOrderCreated($order->refresh());

        $ledger = AnalyticsConversion::where('event_type', 'order_created')->where('order_number', $order->order_number)->first();
        $this->assertNotNull($ledger);
        $this->assertSame(150000, $ledger->payload['amount']);
    }

    /**
     * 10: COD order creation triggers order_created conversion once.
     */
    public function test_cod_order_creation_triggers_order_created_conversion(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $product = $this->makeProduct('COD-01', 1200.00);
        $this->seedCart($user, $product, 1);

        config(['openai_ads.enabled' => true, 'openai_ads.pixel_id' => 'px_cod_123', 'openai_ads.capi_key' => 'key_cod_123']);

        $this->actingAs($user, 'web')
            ->post(route('checkout.store'), $this->addressData('cod'))
            ->assertRedirect();

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(0.0, (float) $order->amount_paid);

        $ledger = AnalyticsConversion::where('event_type', 'order_created')->where('order_number', $order->order_number)->first();
        $this->assertNotNull($ledger);
        $this->assertSame(120000, $ledger->payload['amount']);

        Queue::assertPushed(SendOpenAiCapiOrder::class, function ($job) use ($order) {
            return $job->order->id === $order->id;
        });
    }

    /**
     * 11: COD payment collection updates revenue without sending second conversion.
     */
    public function test_cod_payment_collection_does_not_send_duplicate_conversion(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $product = $this->makeProduct('COD-COLLECT-01', 1000.00);
        $this->seedCart($user, $product, 1);

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('cod'));

        $order = Order::where('user_id', $user->id)->firstOrFail();

        $initialLedgerCount = AnalyticsConversion::where('event_type', 'order_created')->where('order_number', $order->order_number)->count();
        $this->assertSame(1, $initialLedgerCount);

        // Admin marks payment status as paid
        $admin = $this->manager();
        $this->actingAs($admin, 'admin')->post(route('admin.orders.payment-status', $order), [
            'payment_status' => 'paid',
            'reason' => 'Cash collected upon delivery by courier partner.',
        ])->assertRedirect();

        $order = $order->fresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertEquals(1000.00, (float) $order->amount_paid);

        // Ledger row count must still be exactly 1
        $subsequentLedgerCount = AnalyticsConversion::where('event_type', 'order_created')->where('order_number', $order->order_number)->count();
        $this->assertSame(1, $subsequentLedgerCount);
    }

    /**
     * 12: Duplicate webhook protection and idempotency.
     */
    public function test_duplicate_webhook_does_not_duplicate_conversion(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'VR-TEST-WEBHOOK-DUP',
            'user_id' => $user->id,
            'billing_name' => 'Test User',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Street 1',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => 'Test User',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Street 1',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'grand_total' => 2000.00,
            'amount_due' => 2000.00,
            'amount_paid' => 2000.00,
            'payment_method' => 'razorpay',
            'payment_status' => 'paid',
            'order_status' => 'processing',
        ]);

        $order->items()->create([
            'product_name' => 'Sample Item',
            'sku' => 'SKU-SMP',
            'quantity' => 1,
            'unit_price' => 2000.00,
            'mrp' => 2000.00,
            'total_price' => 2000.00,
        ]);

        $conversionService = app(ConversionService::class);

        $first = $conversionService->recordOpenAiOrderCreated($order);
        $second = $conversionService->recordOpenAiOrderCreated($order);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, AnalyticsConversion::where('event_type', 'order_created')->where('order_number', $order->order_number)->count());
    }

    /**
     * 13: Duplicate browser conversion prevented on success page refresh.
     */
    public function test_duplicate_browser_conversion_prevented_on_page_refresh(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'VR-TEST-SUCCESS-REFRESH',
            'user_id' => $user->id,
            'billing_name' => 'Test User',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Street 1',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => 'Test User',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Street 1',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'grand_total' => 1500.00,
            'amount_due' => 0.00,
            'amount_paid' => 1500.00,
            'payment_method' => 'razorpay',
            'payment_status' => 'paid',
            'order_status' => 'processing',
        ]);

        $order->items()->create([
            'product_name' => 'Sample Kurti',
            'sku' => 'KURTI-REFRESH',
            'quantity' => 1,
            'unit_price' => 1500.00,
            'mrp' => 1500.00,
            'total_price' => 1500.00,
        ]);

        // Pre-create conversion ledger
        app(ConversionService::class)->recordOpenAiOrderCreated($order);

        config(['openai_ads.pixel_id' => 'px_live_test']);

        // First visit to success page
        $firstResponse = $this->actingAs($user, 'web')->get(route('checkout.success', $order));
        $firstResponse->assertOk();
        $this->assertNotNull($firstResponse->viewData('openAiEventPayload'));

        // Ledger row now has openai_browser_rendered_at set
        $ledger = AnalyticsConversion::where('event_type', 'order_created')->where('order_number', $order->order_number)->first();
        $this->assertNotNull($ledger->openai_browser_rendered_at);

        // Second visit (page refresh)
        $secondResponse = $this->actingAs($user, 'web')->get(route('checkout.success', $order));
        $secondResponse->assertOk();

        // Must be null on second visit!
        $this->assertNull($secondResponse->viewData('openAiEventPayload'));
    }

    /**
     * 14: Cancelled orders preserve historical attribution and are excluded from paid revenue.
     */
    public function test_cancelled_orders_preserve_attribution_and_exclude_from_revenue(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'VR-CANCEL-TEST',
            'user_id' => $user->id,
            'billing_name' => 'User',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Street',
            'billing_city' => 'City',
            'billing_state' => 'State',
            'billing_pincode' => '560001',
            'shipping_name' => 'User',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Street',
            'shipping_city' => 'City',
            'shipping_state' => 'State',
            'shipping_pincode' => '560001',
            'grand_total' => 2500.00,
            'amount_due' => 2500.00,
            'amount_paid' => 0.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'oppref' => 'cancel-oppref-123',
            'attribution_source' => 'chatgpt_ads',
        ]);

        $order->items()->create([
            'product_name' => 'Item',
            'sku' => 'SKU-CANC',
            'quantity' => 1,
            'unit_price' => 2500.00,
            'mrp' => 2500.00,
            'total_price' => 2500.00,
        ]);

        // Cancel order
        $order->update(['order_status' => 'cancelled', 'cancelled_at' => now()]);

        // Historical attribution still present
        $this->assertSame('cancel-oppref-123', $order->fresh()->oppref);

        // Check reporting
        $reportingService = app(ChatGptAdsReportingService::class);
        $bounds = [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
        ];
        $report = $reportingService->report($bounds);

        $this->assertSame(1, $report['orders']['cancelled_orders']);
        $this->assertEquals(0.0, (float) $report['summary']['paid_revenue']);
    }

    /**
     * 15: Refunds are tracked and excluded from paid revenue.
     */
    public function test_refunded_orders_are_excluded_from_paid_revenue(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'VR-REFUND-TEST',
            'user_id' => $user->id,
            'billing_name' => 'User',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Street',
            'billing_city' => 'City',
            'billing_state' => 'State',
            'billing_pincode' => '560001',
            'shipping_name' => 'User',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Street',
            'shipping_city' => 'City',
            'shipping_state' => 'State',
            'shipping_pincode' => '560001',
            'grand_total' => 3000.00,
            'amount_due' => 0.00,
            'amount_paid' => 3000.00,
            'payment_method' => 'razorpay',
            'payment_status' => 'refunded',
            'order_status' => 'delivered',
            'oppref' => 'refund-oppref-456',
            'attribution_source' => 'chatgpt_ads',
        ]);

        $reportingService = app(ChatGptAdsReportingService::class);
        $bounds = [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
        ];
        $report = $reportingService->report($bounds);

        $this->assertSame(1, $report['orders']['refunded_orders']);
        $this->assertEquals(0.0, (float) $report['summary']['paid_revenue']);
    }

    /**
     * 16 & 17: Admin reporting accuracy and date filters.
     */
    public function test_admin_reporting_and_date_filtering(): void
    {
        $admin = $this->manager();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.analytics', [
            'section' => 'chatgpt',
            'preset' => 'last_7',
        ]));

        $response->assertOk();
        $response->assertSee('ChatGPT Ads Campaign Performance');
        $response->assertSee('Customer Journey Funnel');
        $response->assertSee('Revenue &amp; Payment Distinction', false);
    }

    /**
     * 18: Missing API credentials fails safely and reports unavailable.
     */
    public function test_missing_api_credentials_reported_as_unavailable(): void
    {
        config(['openai_ads.enabled' => false, 'openai_ads.capi_key' => '']);

        $service = app(OpenAiCapiService::class);
        $this->assertFalse($service->isConfigured());

        $reporting = app(ChatGptAdsReportingService::class);
        $metrics = $reporting->platformMetrics([
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
        ]);

        $this->assertFalse($metrics['available']);
        $this->assertStringContainsString('OpenAI Advertiser API not configured', $metrics['reason']);
    }

    /**
     * 19: OpenAI API retry handling and failure recording.
     */
    public function test_openai_api_failure_updates_ledger_with_failed_state(): void
    {
        Http::fake([
            'https://bzr.openai.com/*' => Http::response(['error' => ['message' => 'Rate limit exceeded']], 429),
        ]);

        config([
            'openai_ads.enabled' => true,
            'openai_ads.pixel_id' => 'px_retry_test',
            'openai_ads.capi_key' => 'key_retry_test',
        ]);

        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'VR-CAPI-RETRY',
            'user_id' => $user->id,
            'billing_name' => 'User',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Street',
            'billing_city' => 'City',
            'billing_state' => 'State',
            'billing_pincode' => '560001',
            'shipping_name' => 'User',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Street',
            'shipping_city' => 'City',
            'shipping_state' => 'State',
            'shipping_pincode' => '560001',
            'grand_total' => 1000.00,
            'amount_due' => 0.00,
            'amount_paid' => 1000.00,
            'payment_method' => 'razorpay',
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
        ]);

        $order->items()->create([
            'product_name' => 'Item',
            'sku' => 'SKU-1',
            'quantity' => 1,
            'unit_price' => 1000.00,
            'mrp' => 1000.00,
            'total_price' => 1000.00,
        ]);

        $capi = app(OpenAiCapiService::class);
        $capi->deliver($order);

        $ledger = AnalyticsConversion::where('event_type', 'order_created')->where('order_number', $order->order_number)->firstOrFail();
        $this->assertSame('failed', $ledger->openai_state);
        $this->assertSame(1, $ledger->openai_attempts);
    }

    /**
     * 20: Measurement consent gates obref and user data.
     */
    public function test_measurement_consent_gates_obref_and_pii(): void
    {
        $capi = app(OpenAiCapiService::class);

        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'phone' => '9876543210',
        ]);

        $order = Order::create([
            'order_number' => 'VR-CONSENT-TEST',
            'user_id' => $user->id,
            'billing_name' => 'Customer',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Street',
            'billing_city' => 'City',
            'billing_state' => 'State',
            'billing_pincode' => '560001',
            'shipping_name' => 'Customer',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Street',
            'shipping_city' => 'City',
            'shipping_state' => 'State',
            'shipping_pincode' => '560001',
            'grand_total' => 500.00,
            'amount_due' => 0.00,
            'amount_paid' => 500.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'oppref' => 'consent-oppref-123',
        ]);

        // When consent is denied
        $payloadDenied = $capi->buildPayload($order, [
            'consent_granted' => false,
            'obref' => 'obref_browser_cookie_value',
        ]);

        // oppref remains at root level unchanged
        $this->assertSame('consent-oppref-123', $payloadDenied['oppref']);
        // Check 5: user property must be completely omitted when no permitted identifiers are available
        $this->assertArrayNotHasKey('user', $payloadDenied, 'User property must be omitted entirely when no permitted identifiers are present');

        // When consent is granted
        $payloadGranted = $capi->buildPayload($order, [
            'consent_granted' => true,
            'obref' => 'obref_browser_cookie_value',
        ]);

        $this->assertSame('consent-oppref-123', $payloadGranted['oppref']);
        $this->assertArrayHasKey('user', $payloadGranted);
        $grantedUser = $payloadGranted['user'];
        $this->assertSame('obref_browser_cookie_value', $grantedUser['obref']);
        $this->assertSame([hash('sha256', 'customer@example.com')], $grantedUser['emails_sha256']);
        $this->assertSame([hash('sha256', '919876543210')], $grantedUser['phone_numbers_sha256']);
    }

    /**
     * 21: Payload schema strictly matches official OpenAI CAPI specification.
     */
    public function test_openai_capi_payload_schema_and_forbidden_fields(): void
    {
        $capi = app(OpenAiCapiService::class);

        $user = User::factory()->create([
            'email' => 'sharma.priya@example.com',
            'phone' => '+919876543210',
        ]);

        $order = Order::create([
            'order_number' => 'VR-SCHEMA-CHECK',
            'user_id' => $user->id,
            'billing_name' => 'Priya Sharma',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Flat 101',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => 'Priya Sharma',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Flat 101',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'grand_total' => 1499.00,
            'amount_due' => 0.00,
            'amount_paid' => 1499.00,
            'payment_method' => 'razorpay',
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
            'oppref' => 'official-oppref-token-xyz',
        ]);

        $order->items()->create([
            'product_name' => 'Signature Anarkali Suit',
            'sku' => 'SUIT-ANAR-01',
            'quantity' => 1,
            'unit_price' => 1499.00,
            'mrp' => 1499.00,
            'total_price' => 1499.00,
        ]);

        $fixedTime = (int) round(microtime(true) * 1000);
        $payload = $capi->buildPayload($order, [
            'consent_granted' => true,
            'obref' => 'unchanged_browser_reference_123',
            'timestamp_ms' => $fixedTime,
            'source_url' => 'https://vanriti.com/checkout/success',
        ]);

        // 1. Required Envelope Fields
        $this->assertSame($capi->eventId($order), $payload['id']);
        $this->assertSame('order_created', $payload['type']);
        $this->assertSame($fixedTime, $payload['timestamp_ms']);
        $this->assertSame('official-oppref-token-xyz', $payload['oppref']);
        $this->assertSame('https://vanriti.com/checkout/success', $payload['source_url']);
        $this->assertSame('web', $payload['action_source']);

        // 2. User fields
        $userData = (array) $payload['user'];
        $this->assertSame('unchanged_browser_reference_123', $userData['obref']);
        $this->assertSame([hash('sha256', 'sharma.priya@example.com')], $userData['emails_sha256']);
        $this->assertSame([hash('sha256', '919876543210')], $userData['phone_numbers_sha256']);

        // 3. Data fields
        $this->assertSame('contents', $payload['data']['type']);
        $this->assertSame(149900, $payload['data']['amount']);
        $this->assertSame('INR', $payload['data']['currency']);
        $this->assertCount(1, $payload['data']['contents']);

        $item = $payload['data']['contents'][0];
        $this->assertSame('SUIT-ANAR-01', $item['id']);
        $this->assertSame('Signature Anarkali Suit', $item['name']);
        $this->assertSame(1, $item['quantity']);
        $this->assertSame(149900, $item['amount']);

        // 4. Strictly FORBIDDEN fields (assertions against legacy Meta or undocumented structures)
        $this->assertArrayNotHasKey('event_name', $payload, 'OpenAI uses "type", not "event_name"');
        $this->assertArrayNotHasKey('event_id', $payload, 'OpenAI uses "id", not "event_id"');
        $this->assertArrayNotHasKey('event_time', $payload, 'OpenAI uses "timestamp_ms", not "event_time"');
        $this->assertArrayNotHasKey('user_data', $payload, 'OpenAI uses "user", not "user_data"');
        $this->assertArrayNotHasKey('custom_data', $payload, 'OpenAI uses "data", not "custom_data"');
        $this->assertArrayNotHasKey('email_sha256', $userData, 'OpenAI uses array "emails_sha256"');
        $this->assertArrayNotHasKey('phone', $userData, 'Plaintext phone is forbidden');
        $this->assertArrayNotHasKey('phone_sha256', $userData, 'OpenAI uses array "phone_numbers_sha256"');
        $this->assertArrayNotHasKey('order_id', $payload['data'], 'order_id must not be present in data');
        $this->assertArrayNotHasKey('order_id', $item, 'order_id must not be present in contents item');
    }

    /**
     * 22: INR amounts in paise and per-unit item amounts (not line total).
     */
    public function test_inr_amounts_in_paise_and_per_unit_item_pricing(): void
    {
        $payloadService = app(OpenAiEcommercePayloadService::class);

        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'VR-PAISE-CHECK',
            'user_id' => $user->id,
            'billing_name' => 'Priya',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Street',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => 'Priya',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Street',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'grand_total' => 2998.50,
            'amount_due' => 0.00,
            'amount_paid' => 2998.50,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        // 3 units at ₹999.50 each (unit price = 99950 paise, line total = 299850 paise)
        $order->items()->create([
            'product_name' => 'Embroidered Dupatta',
            'sku' => 'DUPATTA-EMB-01',
            'quantity' => 3,
            'unit_price' => 999.50,
            'mrp' => 999.50,
            'total_price' => 2998.50,
        ]);

        $data = $payloadService->orderCreated($order);

        // Event-level amount is the entire event value in minor units (paise)
        $this->assertSame(299850, $data['amount']);
        $this->assertSame('INR', $data['currency']);
        $this->assertCount(1, $data['contents']);

        $item = $data['contents'][0];
        $this->assertSame('DUPATTA-EMB-01', $item['id']);
        $this->assertSame('Embroidered Dupatta', $item['name']);
        $this->assertSame(3, $item['quantity']);
        // Item-level amount is the SINGLE-UNIT price in paise, NOT the line total
        $this->assertSame(99950, $item['amount']);
    }

    /**
     * 23: Validate-only mode executes against OpenAI CAPI with validate_only flag.
     */
    public function test_openai_validate_only_mode(): void
    {
        Http::fake([
            'https://bzr.openai.com/v1/events*' => function (\Illuminate\Http\Client\Request $request) {
                $body = $request->data();

                // Validate request envelope
                if (! isset($body['validate_only']) || $body['validate_only'] !== true) {
                    return Http::response(['error' => ['message' => 'validate_only must be true']], 400);
                }

                if (! isset($body['events']) || ! is_array($body['events']) || count($body['events']) !== 1) {
                    return Http::response(['error' => ['message' => 'events array required']], 400);
                }

                $event = $body['events'][0];
                if (! isset($event['id'], $event['type'], $event['timestamp_ms'], $event['data'])) {
                    return Http::response(['error' => ['message' => 'Missing required event fields']], 400);
                }

                return Http::response([
                    'success' => true,
                    'events_received' => 1,
                    'validation_status' => 'valid',
                    'warnings' => [],
                ], 200);
            },
        ]);

        config([
            'openai_ads.enabled' => true,
            'openai_ads.pixel_id' => 'px_test_validation_789',
            'openai_ads.capi_key' => 'key_sec_validation_token_xyz',
            'openai_ads.validate_only' => true,
        ]);

        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'VR-VALIDATE-ONLY-01',
            'user_id' => $user->id,
            'billing_name' => 'Tester',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Street',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => 'Tester',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Street',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'grand_total' => 1500.00,
            'amount_due' => 0.00,
            'amount_paid' => 1500.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'oppref' => 'val-oppref-123',
        ]);

        $order->items()->create([
            'product_name' => 'Test Item',
            'sku' => 'SKU-VAL',
            'quantity' => 1,
            'unit_price' => 1500.00,
            'mrp' => 1500.00,
            'total_price' => 1500.00,
        ]);

        $capi = app(OpenAiCapiService::class);
        $response = $capi->sendOrder($order, ['validate_only' => true]);

        $this->assertSame(200, $response->status());
        $this->assertTrue($response->json('success'));
        $this->assertSame(1, $response->json('events_received'));
        $this->assertSame('valid', $response->json('validation_status'));

        // Verify outgoing request headers and token
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($capi) {
            return $request->url() === 'https://bzr.openai.com/v1/events?pid=px_test_validation_789'
                && $request->hasHeader('Authorization', 'Bearer ' . $capi->capiKey())
                && $request['validate_only'] === true
                && $request['events'][0]['type'] === 'order_created'
                && $request['events'][0]['data']['amount'] === 150000;
        });
    }

    /**
     * 24: Check 5 — user property is omitted entirely when no permitted identifiers are available.
     */
    public function test_user_property_omitted_entirely_when_no_identifiers(): void
    {
        $capi = app(OpenAiCapiService::class);

        // Order where customer has neither email nor phone number attached
        $userWithoutIdentifiers = User::factory()->create(['email' => '', 'phone' => '']);

        $order = Order::create([
            'order_number' => 'VR-NO-USER-01',
            'user_id' => $userWithoutIdentifiers->id,
            'billing_name' => 'Guest Visitor',
            'billing_mobile' => '',
            'billing_address_line1' => 'Street',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => 'Guest Visitor',
            'shipping_mobile' => '',
            'shipping_address_line1' => 'Street',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'grand_total' => 1000.00,
            'amount_due' => 0.00,
            'amount_paid' => 1000.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $payload = $capi->buildPayload($order, [
            'consent_granted' => true,
            'obref' => null,
        ]);

        // When no email and no obref, 'user' must be absent, not an empty object {}
        $this->assertArrayNotHasKey('user', $payload, 'Event payload must omit "user" key entirely when no identifiers exist');
    }

    /**
     * 25: Valid recent timestamp is preserved from the original order event.
     */
    public function test_timestamp_valid_recent_is_preserved(): void
    {
        $capi = app(OpenAiCapiService::class);

        $orderTime = now()->subHours(2);
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'VR-TIME-VALID',
            'user_id' => $user->id,
            'billing_name' => 'Tester',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Street',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => 'Tester',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Street',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'grand_total' => 500.00,
            'amount_due' => 0.00,
            'amount_paid' => 500.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);
        $order->created_at = $orderTime;
        $order->save();

        $payload = $capi->buildPayload($order);

        $expectedMs = (int) ($orderTime->getTimestamp() * 1000);
        $this->assertSame($expectedMs, $payload['timestamp_ms']);
    }

    /**
     * 26: Timestamp exactly at the seven-day boundary is accepted.
     */
    public function test_timestamp_exactly_at_seven_day_boundary_is_accepted(): void
    {
        $capi = app(OpenAiCapiService::class);

        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'VR-TIME-BOUNDARY',
            'user_id' => $user->id,
            'billing_name' => 'Tester',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Street',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => 'Tester',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Street',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'grand_total' => 500.00,
            'amount_due' => 0.00,
            'amount_paid' => 500.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $referenceNow = 1793505600000;
        $boundaryMs = $referenceNow - OpenAiCapiService::MAX_TIMESTAMP_AGE_MS; // Exactly 7 days prior

        $payload = $capi->buildPayload($order, [
            'reference_now_ms' => $referenceNow,
            'timestamp_ms' => $boundaryMs,
        ]);

        $this->assertSame($boundaryMs, $payload['timestamp_ms']);
    }

    /**
     * 27: Timestamp older than seven days is rejected, marked expired, and never retried.
     */
    public function test_timestamp_older_than_seven_days_is_rejected_and_marked_expired(): void
    {
        config([
            'openai_ads.enabled' => true,
            'openai_ads.pixel_id' => 'px_time_test',
            'openai_ads.capi_key' => 'key_time_test',
        ]);

        $capi = app(OpenAiCapiService::class);

        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'VR-TIME-EXPIRED',
            'user_id' => $user->id,
            'billing_name' => 'Tester',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Street',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => 'Tester',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Street',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'grand_total' => 500.00,
            'amount_due' => 0.00,
            'amount_paid' => 500.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $referenceNow = 1793505600000;
        $expiredMs = $referenceNow - OpenAiCapiService::MAX_TIMESTAMP_AGE_MS - 1000; // 7 days + 1 second ago

        // 1. Calling buildPayload throws OpenAiTimestampException (isExpired)
        try {
            $capi->buildPayload($order, [
                'reference_now_ms' => $referenceNow,
                'timestamp_ms' => $expiredMs,
            ]);
            $this->fail('Expected OpenAiTimestampException was not thrown');
        } catch (OpenAiTimestampException $e) {
            $this->assertTrue($e->isExpired());
            $this->assertStringContainsString('Conversion expired', $e->getMessage());
        }

        // 2. deliver() marks ledger as expired and records terminal state
        $capi->deliver($order, [
            'reference_now_ms' => $referenceNow,
            'timestamp_ms' => $expiredMs,
        ]);

        $ledger = AnalyticsConversion::where('event_type', 'order_created')->where('order_number', $order->order_number)->firstOrFail();
        $this->assertSame('expired', $ledger->openai_state);
        $this->assertNull($ledger->openai_sent_at);
        $this->assertSame(1, $ledger->openai_attempts);

        // 3. Ensure rejected events cannot be retried indefinitely
        // Calling deliver again must immediately return without incrementing attempts or attempting delivery
        $capi->deliver($order, [
            'reference_now_ms' => $referenceNow,
            'timestamp_ms' => $expiredMs,
        ]);

        $freshLedger = $ledger->fresh();
        $this->assertSame('expired', $freshLedger->openai_state);
        $this->assertSame(1, $freshLedger->openai_attempts, 'Attempts must not increment once in terminal expired state');
    }

    /**
     * 28: Timestamp more than ten minutes in the future is rejected, marked rejected, and never retried.
     */
    public function test_timestamp_more_than_ten_minutes_in_future_is_rejected(): void
    {
        config([
            'openai_ads.enabled' => true,
            'openai_ads.pixel_id' => 'px_time_test',
            'openai_ads.capi_key' => 'key_time_test',
        ]);

        $capi = app(OpenAiCapiService::class);

        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'VR-TIME-FUTURE',
            'user_id' => $user->id,
            'billing_name' => 'Tester',
            'billing_mobile' => '9876543210',
            'billing_address_line1' => 'Street',
            'billing_city' => 'Bengaluru',
            'billing_state' => 'Karnataka',
            'billing_pincode' => '560038',
            'shipping_name' => 'Tester',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Street',
            'shipping_city' => 'Bengaluru',
            'shipping_state' => 'Karnataka',
            'shipping_pincode' => '560038',
            'grand_total' => 500.00,
            'amount_due' => 0.00,
            'amount_paid' => 500.00,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ]);

        $referenceNow = 1793505600000;
        $futureMs = $referenceNow + OpenAiCapiService::MAX_FUTURE_SKEW_MS + 5000; // 10 minutes + 5 seconds future

        // 1. Calling buildPayload throws OpenAiTimestampException (isFuture)
        try {
            $capi->buildPayload($order, [
                'reference_now_ms' => $referenceNow,
                'timestamp_ms' => $futureMs,
            ]);
            $this->fail('Expected OpenAiTimestampException was not thrown');
        } catch (OpenAiTimestampException $e) {
            $this->assertTrue($e->isFuture());
            $this->assertStringContainsString('Conversion rejected', $e->getMessage());
        }

        // 2. deliver() marks ledger as rejected and records terminal state
        $capi->deliver($order, [
            'reference_now_ms' => $referenceNow,
            'timestamp_ms' => $futureMs,
        ]);

        $ledger = AnalyticsConversion::where('event_type', 'order_created')->where('order_number', $order->order_number)->firstOrFail();
        $this->assertSame('rejected', $ledger->openai_state);
        $this->assertNull($ledger->openai_sent_at);
        $this->assertSame(1, $ledger->openai_attempts);

        // 3. Ensure rejected events cannot be retried indefinitely
        $capi->deliver($order, [
            'reference_now_ms' => $referenceNow,
            'timestamp_ms' => $futureMs,
        ]);

        $freshLedger = $ledger->fresh();
        $this->assertSame('rejected', $freshLedger->openai_state);
        $this->assertSame(1, $freshLedger->openai_attempts, 'Attempts must not increment once in terminal rejected state');
    }

    /**
     * Test OpenAI Ads and CAPI diagnostics are displayed in the Admin Analytics Center.
     */
    public function test_openai_ads_analytics_and_diagnostics_in_command_center(): void
    {
        config([
            'openai_ads.enabled' => true,
            'openai_ads.pixel_id' => 'BBWekc1tuk8CcLkcNrLBzk',
            'openai_ads.capi_key' => 'test-capi-key-12345',
            'openai_ads.validate_only' => false,
        ]);

        AnalyticsConversion::create([
            'event_type' => 'order_created',
            'order_number' => 'VAN-TEST-DIAG-01',
            'channel' => 'chatgpt_ads',
            'payload' => [],
            'openai_state' => 'sent',
            'openai_sent_at' => now(),
            'openai_attempts' => 1,
            'openai_event_id' => 'test_event_id_01',
        ]);

        $service = app(\App\Services\Analytics\AnalyticsCommandCenterService::class);
        $report = $service->report(now()->subDays(7)->toDateString(), now()->toDateString());

        $this->assertTrue($report['tracking']['openai_pixel_configured']);
        $this->assertSame('active', $report['tracking']['openai_capi']['status']);
        $this->assertSame(1, $report['tracking']['openai_capi']['delivered_7d']);
        $this->assertSame(1, $report['tracking']['openai_ledger_total']);
        $this->assertFalse($report['tracking']['openai_capi']['validate_only']);

        $admin = $this->manager();

        // 1. Diagnostics View
        $diagResponse = $this->actingAs($admin, 'admin')->get(route('admin.analytics', ['section' => 'diagnostics']));
        $diagResponse->assertOk();
        $diagResponse->assertSee('OpenAI Measurement Pixel (oaiq)');
        $diagResponse->assertSee('OpenAI Conversions API (CAPI)');
        $diagResponse->assertSee('OpenAI Conversion ledger (order_created)');
        $diagResponse->assertSee('Live Production Mode');

        // 2. OpenAI Ads Dedicated View (using section=openai alias and section=chatgpt)
        $openAiResponse = $this->actingAs($admin, 'admin')->get(route('admin.analytics', ['section' => 'openai']));
        $openAiResponse->assertOk();
        $openAiResponse->assertSee('OpenAI Ads');
        $openAiResponse->assertSee('Measurement Pixel:');
        $openAiResponse->assertSee('Conversions API:');

        $chatGptResponse = $this->actingAs($admin, 'admin')->get(route('admin.analytics', ['section' => 'chatgpt']));
        $chatGptResponse->assertOk();
        $chatGptResponse->assertSee('OpenAI Ads');
    }
}
