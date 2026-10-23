<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsAppCheckoutSession;
use App\Models\WhatsAppWebhookLog;
use App\Services\WhatsAppCheckoutService;
use App\Services\WhatsAppShippingService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WhatsAppCommerceCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        Notification::fake();

        // Configure WhatsApp & Razorpay settings
        Setting::updateOrCreate(['key' => 'whatsapp_orders_enabled'], ['value' => '1', 'group' => 'whatsapp', 'type' => 'boolean']);
        Setting::updateOrCreate(['key' => 'whatsapp_phone_number_id'], ['value' => '10987654321', 'group' => 'whatsapp', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_webhook_verify_token'], ['value' => 'vanriti_verify_token_123', 'group' => 'whatsapp', 'type' => 'text']);
        Setting::updateOrCreate(['key' => 'whatsapp_shipping_charge'], ['value' => '79', 'group' => 'whatsapp', 'type' => 'number']);
        Setting::updateOrCreate(['key' => 'whatsapp_free_shipping_threshold'], ['value' => '499', 'group' => 'whatsapp', 'type' => 'number']);
        Setting::updateOrCreate(['key' => 'whatsapp_access_token'], [
            'value' => Crypt::encryptString('mock_wa_access_token'),
            'group' => 'whatsapp',
            'type' => 'password',
        ]);

        Setting::updateOrCreate(['key' => 'razorpay_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_key_id'], ['value' => 'rzp_test_key_123']);
        Setting::updateOrCreate(['key' => 'razorpay_key_secret'], ['value' => Crypt::encryptString('rzp_test_secret_123')]);
        Setting::updateOrCreate(['key' => 'razorpay_webhook_secret'], ['value' => Crypt::encryptString('rzp_test_whsec_123')]);

        // Fake Meta Cloud API and Razorpay
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.HBgL...']]], 200),
            'api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_rp_mock_123',
                'amount' => 47800,
                'currency' => 'INR',
                'receipt' => 'VAN-TEST',
            ], 200),
            'api.razorpay.com/v1/payments/*' => Http::response([
                'id' => 'pay_rp_mock_123',
                'order_id' => 'order_rp_mock_123',
                'amount' => 47800,
                'status' => 'captured',
            ], 200),
        ]);
    }

    protected function createProduct(array $overrides = []): Product
    {
        $product = Product::factory()->active()->create(array_merge([
            'name' => 'Organic Neem Oil',
            'sku' => 'VAN-NEEM-100',
            'selling_price' => 399.00,
            'mrp' => 499.00,
            'gst_rate' => 18,
            'stock' => 50,
        ], $overrides));

        Inventory::create([
            'stockable_type' => Product::class,
            'stockable_id' => $product->id,
            'stock_on_hand' => (int) $product->stock,
            'low_stock_threshold' => 5,
        ]);

        return $product;
    }

    public function test_whatsapp_webhook_get_verification_challenge_succeeds_with_correct_token(): void
    {
        $response = $this->get('/whatsapp/webhook?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'vanriti_verify_token_123',
            'hub_challenge' => 'challenge_code_98765',
        ]));

        $response->assertStatus(200);
        $this->assertEquals('challenge_code_98765', $response->getContent());
    }

    public function test_whatsapp_webhook_get_verification_fails_with_invalid_token(): void
    {
        $response = $this->get('/whatsapp/webhook?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong_token',
            'hub_challenge' => 'challenge_code_98765',
        ]));

        $response->assertStatus(403);
    }

    public function test_shipping_rules_under_499_gets_79_and_over_499_is_free(): void
    {
        $shippingService = app(WhatsAppShippingService::class);

        // Under 499 => ₹79
        $calc399 = $shippingService->calculate(399.00);
        $this->assertEquals(79.0, $calc399['charge']);
        $this->assertFalse($calc399['eligible_for_free']);

        $calc498 = $shippingService->calculate(498.99);
        $this->assertEquals(79.0, $calc498['charge']);

        // Exactly 499 => ₹0
        $calc499 = $shippingService->calculate(499.00);
        $this->assertEquals(0.0, $calc499['charge']);
        $this->assertTrue($calc499['eligible_for_free']);

        // Above 499 => ₹0
        $calc999 = $shippingService->calculate(999.00);
        $this->assertEquals(0.0, $calc999['charge']);
        $this->assertTrue($calc999['eligible_for_free']);
    }

    public function test_incoming_catalogue_cart_resolves_products_and_initializes_session(): void
    {
        $product = $this->createProduct(['selling_price' => 399.00, 'sku' => 'VAN-NEEM-100']);
        $phone = '919876543210';

        $cartWebhookPayload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'id' => 'wamid.msg1001',
                                        'from' => $phone,
                                        'timestamp' => (string) time(),
                                        'type' => 'order',
                                        'order' => [
                                            'catalog_id' => '12345678',
                                            'product_items' => [
                                                [
                                                    'product_retailer_id' => 'VAN-NEEM-100',
                                                    'quantity' => '1',
                                                    'item_price' => '99999.00', // tampered client price
                                                    'currency' => 'INR',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $res = $this->postJson('/whatsapp/webhook', $cartWebhookPayload);
        $res->assertStatus(200);

        // Check session created with authoritative price
        $session = WhatsAppCheckoutSession::where('phone', $phone)->latest()->first();
        $this->assertNotNull($session);
        $this->assertEquals(WhatsAppCheckoutSession::STATUS_COLLECTING_NAME, $session->status);
        $this->assertEquals(399.00, (float) $session->subtotal); // Authoritative backend price, NOT 99999.00
        $this->assertEquals(79.00, (float) $session->shipping_charge); // Under 499 gets 79
        $this->assertEquals(478.00, (float) $session->grand_total);
    }

    public function test_incoming_cart_rejects_out_of_stock_or_unknown_products(): void
    {
        $product = $this->createProduct(['sku' => 'VAN-OOS-01', 'stock' => 0]);
        $phone = '919876543210';

        $cartWebhookPayload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'id' => 'wamid.msg1002',
                                        'from' => $phone,
                                        'timestamp' => (string) time(),
                                        'type' => 'order',
                                        'order' => [
                                            'product_items' => [
                                                [
                                                    'product_retailer_id' => 'VAN-OOS-01',
                                                    'quantity' => '2',
                                                ],
                                                [
                                                    'product_retailer_id' => 'NON-EXISTENT-SKU',
                                                    'quantity' => '1',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $res = $this->postJson('/whatsapp/webhook', $cartWebhookPayload);
        $res->assertStatus(200);

        // Session should not advance because all items failed validation
        $session = WhatsAppCheckoutSession::where('phone', $phone)->first();
        $this->assertNull($session);
    }

    public function test_conversation_progression_name_pincode_address_confirmation(): void
    {
        $product = $this->createProduct(['selling_price' => 599.00, 'sku' => 'VAN-CREAM-50']); // >= 499 free delivery
        $phone = '919876543210';

        $checkoutService = app(WhatsAppCheckoutService::class);

        // 1. Send Cart
        $checkoutService->handleIncomingCart($phone, $phone, [
            ['product_retailer_id' => 'VAN-CREAM-50', 'quantity' => 1],
        ], 'msg_step1');

        $session = WhatsAppCheckoutSession::where('phone', $phone)->first();
        $this->assertEquals(WhatsAppCheckoutSession::STATUS_COLLECTING_NAME, $session->status);
        $this->assertEquals(0.00, (float) $session->shipping_charge);
        $this->assertEquals(599.00, (float) $session->grand_total);

        // 2. Customer provides invalid name (e.g., numbers only)
        $checkoutService->handleIncomingMessage($phone, '12345', 'msg_invalid_name');
        $session->refresh();
        $this->assertEquals(WhatsAppCheckoutSession::STATUS_COLLECTING_NAME, $session->status);

        // 3. Customer provides valid name
        $checkoutService->handleIncomingMessage($phone, 'Priya Sharma', 'msg_valid_name');
        $session->refresh();
        $this->assertEquals(WhatsAppCheckoutSession::STATUS_COLLECTING_PINCODE, $session->status);
        $this->assertEquals('Priya Sharma', $session->customer_name);

        // 4. Customer provides invalid PIN code
        $checkoutService->handleIncomingMessage($phone, '1234', 'msg_invalid_pin');
        $session->refresh();
        $this->assertEquals(WhatsAppCheckoutSession::STATUS_COLLECTING_PINCODE, $session->status);

        // 5. Customer provides valid 6-digit PIN code
        $checkoutService->handleIncomingMessage($phone, '110001', 'msg_valid_pin');
        $session->refresh();
        $this->assertEquals(WhatsAppCheckoutSession::STATUS_COLLECTING_ADDRESS, $session->status);
        $this->assertEquals('110001', $session->pincode);

        // 6. Customer provides address
        $checkoutService->handleIncomingMessage($phone, 'Flat 402, Sunshine Heights, Barakhamba Road, Connaught Place', 'msg_address');
        $session->refresh();
        $this->assertEquals(WhatsAppCheckoutSession::STATUS_AWAITING_ORDER_CONFIRMATION, $session->status);
        $this->assertNotEmpty($session->address_data['address_line1']);

        // 7. Customer confirms order via text fallback "CONFIRM"
        $checkoutService->handleIncomingMessage($phone, 'CONFIRM', 'msg_confirm');
        $session->refresh();
        $this->assertEquals(WhatsAppCheckoutSession::STATUS_AWAITING_PAYMENT_METHOD, $session->status);
    }

    public function test_customer_can_cancel_checkout(): void
    {
        $product = $this->createProduct(['sku' => 'VAN-CANCEL-01']);
        $phone = '919876543210';

        $checkoutService = app(WhatsAppCheckoutService::class);
        $checkoutService->handleIncomingCart($phone, $phone, [
            ['product_retailer_id' => 'VAN-CANCEL-01', 'quantity' => 1],
        ], 'msg_cancel_start');

        $session = WhatsAppCheckoutSession::where('phone', $phone)->first();
        $this->assertNotNull($session);

        // Send CANCEL
        $checkoutService->handleIncomingMessage($phone, 'CANCEL', 'msg_cancel_cmd');
        $session->refresh();
        $this->assertEquals(WhatsAppCheckoutSession::STATUS_CANCELLED, $session->status);
    }

    public function test_customer_cod_order_placement_creates_order_with_whatsapp_source_and_reserves_stock(): void
    {
        $product = $this->createProduct(['selling_price' => 399.00, 'sku' => 'VAN-COD-01', 'stock' => 10]);
        $phone = '919876543210';

        $checkoutService = app(WhatsAppCheckoutService::class);

        // Fast-forward session to AWAITING_PAYMENT_METHOD
        $session = WhatsAppCheckoutSession::create([
            'session_token' => 'tok_cod_test_123',
            'phone' => $phone,
            'status' => WhatsAppCheckoutSession::STATUS_AWAITING_PAYMENT_METHOD,
            'customer_name' => 'Rahul Verma',
            'pincode' => '110001',
            'cart_data' => [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'quantity' => 2,
                        'unit_price' => 399.00,
                        'mrp' => 499.00,
                        'gst_rate' => 18,
                        'line_total' => 798.00,
                    ],
                ],
            ],
            'address_data' => [
                'address_line1' => 'Plot 12, Sector 5',
                'city' => 'New Delhi',
                'state' => 'Delhi',
                'pincode' => '110001',
                'country' => 'India',
            ],
            'subtotal' => 798.00,
            'shipping_charge' => 0.00, // > 499
            'grand_total' => 798.00,
            'expires_at' => now()->addHours(24),
        ]);

        // Customer selects COD
        $result = $checkoutService->handleIncomingMessage($phone, 'COD', 'msg_cod_select');
        $this->assertTrue($result['success']);

        $session->refresh();
        $this->assertEquals(WhatsAppCheckoutSession::STATUS_ORDER_CONFIRMED, $session->status);
        $this->assertEquals('cod', $session->payment_method);
        $this->assertNotNull($session->order_id);

        $order = Order::find($session->order_id);
        $this->assertNotNull($order);
        $this->assertEquals('whatsapp', $order->checkout_source);
        $this->assertEquals('cod', $order->payment_method);
        $this->assertEquals('pending', $order->payment_status); // COD is unpaid pending delivery
        $this->assertEquals('confirmed', $order->order_status);
        $this->assertEquals(798.00, (float) $order->grand_total);

        // Check stock was deducted
        $product->refresh();
        $this->assertEquals(8, $product->stock); // 10 - 2 = 8

        // Test duplicate COD request is idempotent and does not create second order
        $initialOrderCount = Order::count();
        $checkoutService->handleIncomingMessage($phone, 'COD', 'msg_cod_repeat');
        $this->assertEquals($initialOrderCount, Order::count());
    }

    public function test_customer_online_payment_creates_razorpay_order_and_renders_payment_page(): void
    {
        $product = $this->createProduct(['selling_price' => 399.00, 'sku' => 'VAN-ONLINE-01']);
        $phone = '919876543210';

        $checkoutService = app(WhatsAppCheckoutService::class);

        $session = WhatsAppCheckoutSession::create([
            'session_token' => 'tok_online_test_456',
            'phone' => $phone,
            'status' => WhatsAppCheckoutSession::STATUS_AWAITING_PAYMENT_METHOD,
            'customer_name' => 'Ananya Roy',
            'pincode' => '400001',
            'cart_data' => [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'quantity' => 1,
                        'unit_price' => 399.00,
                        'mrp' => 499.00,
                        'gst_rate' => 18,
                        'line_total' => 399.00,
                    ],
                ],
            ],
            'address_data' => [
                'address_line1' => '22 Marine Drive',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400001',
                'country' => 'India',
            ],
            'subtotal' => 399.00,
            'shipping_charge' => 79.00, // < 499 gets 79
            'grand_total' => 478.00,
            'expires_at' => now()->addHours(24),
        ]);

        // Customer selects ONLINE
        $result = $checkoutService->handleIncomingMessage($phone, 'ONLINE PAYMENT', 'msg_online_select');
        $this->assertTrue($result['success']);

        $session->refresh();
        $this->assertEquals(WhatsAppCheckoutSession::STATUS_PAYMENT_PENDING, $session->status);
        $this->assertEquals('razorpay', $session->payment_method);
        $this->assertNotNull($session->order_id);
        $this->assertNotEmpty($session->razorpay_order_id);

        $order = Order::find($session->order_id);
        $this->assertEquals(478.00, (float) $order->grand_total);
        $this->assertEquals('pending', $order->payment_status);

        // Customer opens payment page in browser
        $response = $this->get('/checkout/whatsapp-pay/' . $session->session_token);
        $response->assertStatus(200);
        $response->assertSee('Complete Your Payment');
        $response->assertSee('478.00');
    }

    public function test_razorpay_payment_signature_verification_succeeds_and_marks_order_paid(): void
    {
        $product = $this->createProduct(['selling_price' => 399.00]);
        $phone = '919876543210';
        $checkoutService = app(WhatsAppCheckoutService::class);

        $session = WhatsAppCheckoutSession::create([
            'session_token' => 'tok_verify_test_789',
            'phone' => $phone,
            'status' => WhatsAppCheckoutSession::STATUS_AWAITING_PAYMENT_METHOD,
            'customer_name' => 'Meera Nair',
            'pincode' => '600001',
            'cart_data' => [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'quantity' => 1,
                        'unit_price' => 399.00,
                        'mrp' => 499.00,
                        'gst_rate' => 18,
                        'line_total' => 399.00,
                    ],
                ],
            ],
            'address_data' => [
                'address_line1' => '10 Anna Salai',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'pincode' => '600001',
                'country' => 'India',
            ],
            'subtotal' => 399.00,
            'shipping_charge' => 79.00,
            'grand_total' => 478.00,
            'expires_at' => now()->addHours(24),
        ]);

        $checkoutService->handleIncomingMessage($phone, 'ONLINE PAYMENT', 'msg_online_pay');
        $session->refresh();

        $rpOrderId = $session->razorpay_order_id;
        $rpPaymentId = 'pay_rp_mock_123';
        $secret = 'rzp_test_secret_123';
        $validSignature = hash_hmac('sha256', $rpOrderId . '|' . $rpPaymentId, $secret);

        // Post verification callback
        $verifyRes = $this->postJson('/checkout/whatsapp-pay/' . $session->session_token . '/verify', [
            'razorpay_order_id' => $rpOrderId,
            'razorpay_payment_id' => $rpPaymentId,
            'razorpay_signature' => $validSignature,
        ]);

        $verifyRes->assertStatus(200);
        $verifyRes->assertJson(['success' => true]);

        $order = $session->order->fresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('confirmed', $order->order_status);

        $session->refresh();
        $this->assertEquals(WhatsAppCheckoutSession::STATUS_ORDER_CONFIRMED, $session->status);
    }

    public function test_razorpay_payment_invalid_signature_is_rejected(): void
    {
        $product = $this->createProduct(['selling_price' => 399.00]);
        $phone = '919876543210';
        $checkoutService = app(WhatsAppCheckoutService::class);

        $session = WhatsAppCheckoutSession::create([
            'session_token' => 'tok_invalid_sig_123',
            'phone' => $phone,
            'status' => WhatsAppCheckoutSession::STATUS_AWAITING_PAYMENT_METHOD,
            'customer_name' => 'Meera Nair',
            'pincode' => '600001',
            'cart_data' => [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'quantity' => 1,
                        'unit_price' => 399.00,
                        'mrp' => 499.00,
                        'gst_rate' => 18,
                        'line_total' => 399.00,
                    ],
                ],
            ],
            'address_data' => [
                'address_line1' => '10 Anna Salai',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'pincode' => '600001',
                'country' => 'India',
            ],
            'subtotal' => 399.00,
            'shipping_charge' => 79.00,
            'grand_total' => 478.00,
            'expires_at' => now()->addHours(24),
        ]);

        $checkoutService->handleIncomingMessage($phone, 'ONLINE PAYMENT', 'msg_online_pay2');
        $session->refresh();

        $verifyRes = $this->postJson('/checkout/whatsapp-pay/' . $session->session_token . '/verify', [
            'razorpay_order_id' => $session->razorpay_order_id,
            'razorpay_payment_id' => 'pay_rp_mock_123',
            'razorpay_signature' => 'invalid_tampered_signature',
        ]);

        $verifyRes->assertStatus(422);

        $order = $session->order->fresh();
        $this->assertNotEquals('paid', $order->payment_status);
    }

    public function test_webhook_message_deduplication_ignores_repeated_message_ids(): void
    {
        $phone = '919876543210';
        $msgId = 'wamid.dedup_test_999';

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'id' => $msgId,
                                        'from' => $phone,
                                        'timestamp' => (string) time(),
                                        'type' => 'text',
                                        'text' => ['body' => 'Hello VANRITI'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // 1st request
        $res1 = $this->postJson('/whatsapp/webhook', $payload);
        $res1->assertStatus(200);
        $this->assertEquals(1, WhatsAppWebhookLog::where('message_id', $msgId)->count());

        // 2nd replayed request with same message_id
        $res2 = $this->postJson('/whatsapp/webhook', $payload);
        $res2->assertStatus(200);
        $this->assertEquals(1, WhatsAppWebhookLog::where('message_id', $msgId)->count());
    }
}
