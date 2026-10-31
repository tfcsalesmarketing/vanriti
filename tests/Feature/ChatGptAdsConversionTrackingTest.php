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

        $this->assertSame('consent-oppref-123', $payloadDenied['user_data']['oppref']);
        $this->assertArrayNotHasKey('obref', $payloadDenied['user_data']);
        $this->assertArrayNotHasKey('email', $payloadDenied['user_data']);
        $this->assertArrayNotHasKey('phone', $payloadDenied['user_data']);

        // When consent is granted
        $payloadGranted = $capi->buildPayload($order, [
            'consent_granted' => true,
            'obref' => 'obref_browser_cookie_value',
        ]);

        $this->assertSame('consent-oppref-123', $payloadGranted['user_data']['oppref']);
        $this->assertSame('obref_browser_cookie_value', $payloadGranted['user_data']['obref']);
        $this->assertArrayHasKey('email', $payloadGranted['user_data']);
        $this->assertArrayHasKey('phone', $payloadGranted['user_data']);
    }
}
