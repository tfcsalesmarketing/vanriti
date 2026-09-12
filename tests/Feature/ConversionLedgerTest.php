<?php

namespace Tests\Feature;

use App\Models\AnalyticsConversion;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Analytics\ConversionService;
use App\Services\Analytics\EcommerceDataService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConversionLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_cod_order_creates_exactly_one_purchase_ledger_row(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT101',
            'name' => 'VANRITI Body Lotion',
            'selling_price' => 800.00,
            'mrp' => 800.00,
            'gst_rate' => 0,
            'stock' => 20,
        ]);

        $this->seedCart($user, $product, 2);

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('cod'))->assertRedirect();

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('pending', $order->payment_status);

        $rows = AnalyticsConversion::where('event_type', 'purchase')
            ->where('order_number', $order->order_number)
            ->get();

        $this->assertCount(1, $rows);

        $ecommerce = $rows->first()->payload['ecommerce'];
        $this->assertSame($order->order_number, $ecommerce['transaction_id']);
        $this->assertSame('INR', $ecommerce['currency']);
        $this->assertEqualsWithDelta((float) $order->grand_total, $ecommerce['value'], 0.01);
        $this->assertEqualsWithDelta((float) $order->tax_amount, $ecommerce['tax'], 0.01);
        $this->assertEquals(0, $ecommerce['shipping']);
        $this->assertSame($product->sku, $ecommerce['items'][0]['item_id']);
        $this->assertSame(2, $ecommerce['items'][0]['quantity']);

        // The persisted payload is the canonical EcommerceDataService purchase
        // payload that the browser also renders on the success page.
        $this->assertEquals(
            app(EcommerceDataService::class)->purchase($order->fresh()),
            $rows->first()->payload
        );

        // No PII is ever persisted server-side.
        $json = json_encode($rows->first()->payload);
        $this->assertStringNotContainsString('"email"', $json);
        $this->assertStringNotContainsString('"phone"', $json);
        $this->assertStringNotContainsString('"user_id"', $json);
        $this->assertStringNotContainsString('"product_id"', $json);
    }

    public function test_razorpay_verified_paid_order_creates_exactly_one_purchase_ledger_row(): void
    {
        $this->enableRazorpay();
        $this->fakeRazorpayOrder();

        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT102',
            'name' => 'VANRITI Face Pack',
            'selling_price' => 800.00,
            'mrp' => 800.00,
            'gst_rate' => 0,
            'stock' => 20,
        ]);

        $this->seedCart($user, $product, 2);

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('razorpay'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $order = Order::where('user_id', $user->id)->firstOrFail();

        $signature = hash_hmac('sha256', 'order_EZ6G0001'.'|'.'pay_TESTPAY1', 'rzp_test_secret');

        $this->actingAs($user, 'web')->post(route('checkout.verify'), [
            'razorpay_order_id' => 'order_EZ6G0001',
            'razorpay_payment_id' => 'pay_TESTPAY1',
            'razorpay_signature' => $signature,
            'order_id' => $order->id,
        ])->assertRedirect(route('checkout.success', $order));

        $this->assertSame('paid', $order->fresh()->payment_status);

        $rows = AnalyticsConversion::where('event_type', 'purchase')
            ->where('order_number', $order->order_number)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame(
            app(EcommerceDataService::class)->purchase($order->fresh())['ecommerce']['transaction_id'],
            $rows->first()->payload['ecommerce']['transaction_id']
        );
    }

    public function test_razorpay_unverified_payment_creates_no_ledger_row(): void
    {
        $this->enableRazorpay();
        $this->fakeRazorpayOrder();

        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT103',
            'name' => 'VANRITI Night Cream',
            'selling_price' => 800.00,
            'mrp' => 800.00,
            'gst_rate' => 0,
            'stock' => 20,
        ]);

        $this->seedCart($user, $product, 1);

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('razorpay'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $order = Order::where('user_id', $user->id)->firstOrFail();

        // Order/session created but no server-side verification happened.
        $this->assertNotSame('paid', $order->fresh()->payment_status);
        $this->assertSame(0, AnalyticsConversion::count());
    }

    public function test_failed_payment_creates_no_ledger_row(): void
    {
        $this->enableRazorpay();
        $this->fakeRazorpayOrder();

        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT104',
            'name' => 'VANRITI Moisturiser',
            'selling_price' => 800.00,
            'mrp' => 800.00,
            'gst_rate' => 0,
            'stock' => 20,
        ]);

        $this->seedCart($user, $product, 1);

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('razorpay'))
            ->assertOk();

        $order = Order::where('user_id', $user->id)->firstOrFail();

        // Invalid signature => markFailed => no purchase conversion.
        $this->actingAs($user, 'web')->post(route('checkout.verify'), [
            'razorpay_order_id' => 'order_EZ6G0001',
            'razorpay_payment_id' => 'pay_TESTPAY1',
            'razorpay_signature' => 'not-a-valid-signature',
            'order_id' => $order->id,
        ])->assertRedirect(route('checkout.failed', $order));

        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame(0, AnalyticsConversion::count());
    }

    public function test_cancelled_order_creates_no_ledger_row(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'pending');
        $order->update(['order_status' => 'cancelled']);

        $this->assertNull(app(ConversionService::class)->recordPurchase($order->fresh()));
        $this->assertSame(0, AnalyticsConversion::where('event_type', 'purchase')->count());
    }

    public function test_repeated_razorpay_verify_does_not_create_another_ledger_row(): void
    {
        $this->enableRazorpay();
        $this->fakeRazorpayOrder();

        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT105',
            'name' => 'VANRITI Toner',
            'selling_price' => 800.00,
            'mrp' => 800.00,
            'gst_rate' => 0,
            'stock' => 20,
        ]);

        $this->seedCart($user, $product, 1);

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('razorpay'))
            ->assertOk();

        $order = Order::where('user_id', $user->id)->firstOrFail();

        $signature = hash_hmac('sha256', 'order_EZ6G0001'.'|'.'pay_TESTPAY1', 'rzp_test_secret');
        $verifyParams = [
            'razorpay_order_id' => 'order_EZ6G0001',
            'razorpay_payment_id' => 'pay_TESTPAY1',
            'razorpay_signature' => $signature,
            'order_id' => $order->id,
        ];

        $this->actingAs($user, 'web')->post(route('checkout.verify'), $verifyParams)->assertRedirect(route('checkout.success', $order));
        $this->actingAs($user, 'web')->post(route('checkout.verify'), $verifyParams)->assertRedirect(route('checkout.success', $order));

        $this->assertCount(
            1,
            AnalyticsConversion::where('event_type', 'purchase')->where('order_number', $order->order_number)->get()
        );
    }

    public function test_double_conversion_recording_results_in_exactly_one_ledger_row(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'pending');

        $service = app(ConversionService::class);

        $first = $service->recordPurchase($order->fresh());
        $second = $service->recordPurchase($order->fresh());

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(
            1,
            AnalyticsConversion::where('event_type', 'purchase')->where('order_number', $order->order_number)->count()
        );
    }

    public function test_recording_a_purchase_already_committed_resolves_to_the_existing_row(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'pending');

        // Simulates the race path where a concurrent request already committed
        // the row (the UNIQUE(event_type, order_number) constraint is the arbiter).
        $existing = AnalyticsConversion::create([
            'event_type' => 'purchase',
            'order_number' => $order->order_number,
            'channel' => 'concurrent-writer',
            'payload' => [],
        ]);

        $result = app(ConversionService::class)->recordPurchase($order->fresh());

        $this->assertSame($existing->id, $result->id);
        $this->assertSame(1, AnalyticsConversion::where('event_type', 'purchase')->count());
    }

    public function test_success_page_refresh_does_not_create_another_ledger_row(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT106',
            'name' => 'VANRITI Serum',
            'selling_price' => 800.00,
            'mrp' => 800.00,
            'gst_rate' => 0,
            'stock' => 20,
        ]);

        $this->seedCart($user, $product, 1);

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('cod'))->assertRedirect();

        $order = Order::where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user, 'web')->get(route('checkout.success', $order))->assertOk();
        $this->actingAs($user, 'web')->get(route('checkout.success', $order))->assertOk();

        // Page re-renders (refresh/back-forward) never add ledger rows.
        $this->assertCount(
            1,
            AnalyticsConversion::where('event_type', 'purchase')->where('order_number', $order->order_number)->get()
        );
    }

    public function test_cod_refunded_order_is_no_longer_purchase_eligible(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'pending');
        $order->update(['payment_status' => 'refunded']);

        $service = app(EcommerceDataService::class);

        $this->assertFalse($service->purchaseEligible($order->fresh()));

        $content = $this->actingAs($user, 'web')->get(route('checkout.success', $order->fresh()))->getContent();
        $this->assertSame(0, substr_count($content, '"event":"purchase"'));
        $this->assertStringNotContainsString("fbq('track', 'Purchase'", $content);
    }

    public function test_cod_partially_refunded_order_is_no_longer_purchase_eligible(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'pending');
        $order->update(['payment_status' => 'partially_refunded']);

        $service = app(EcommerceDataService::class);

        $this->assertFalse($service->purchaseEligible($order->fresh()));

        $content = $this->actingAs($user, 'web')->get(route('checkout.success', $order->fresh()))->getContent();
        $this->assertSame(0, substr_count($content, '"event":"purchase"'));
    }

    public function test_razorpay_refunded_order_is_not_purchase_eligible(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'razorpay', 'paid');
        $order->update(['payment_status' => 'refunded']);

        $service = app(EcommerceDataService::class);

        $this->assertFalse($service->purchaseEligible($order->fresh()));

        $content = $this->actingAs($user, 'web')->get(route('checkout.success', $order->fresh()))->getContent();
        $this->assertSame(0, substr_count($content, '"event":"purchase"'));
    }

    public function test_existing_browser_data_layer_purchase_remains_intact_alongside_ledger(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT107',
            'name' => 'VANRITI Cleanser',
            'selling_price' => 500.00,
            'mrp' => 500.00,
            'gst_rate' => 0,
            'stock' => 20,
        ]);

        $this->seedCart($user, $product, 2);

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('cod'))->assertRedirect();

        $order = Order::where('user_id', $user->id)->firstOrFail();

        // Server-side authoritative ledger row exists alongside the browser event.
        $this->assertSame(
            1,
            AnalyticsConversion::where('event_type', 'purchase')->where('order_number', $order->order_number)->count()
        );

        // The browser purchase push is unchanged: exactly one per render.
        $content = $this->actingAs($user, 'web')->get(route('checkout.success', $order))->getContent();
        $this->assertSame(1, substr_count($content, '"event":"purchase"'));
        $this->assertStringContainsString('window.dataLayer.push({', $content);
        $this->assertStringContainsString('"transaction_id":"'.$order->order_number.'"', $content);
    }

    protected function enableRazorpay(): void
    {
        Setting::updateOrCreate(['key' => 'online_payment_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_key_id'], ['value' => 'rzp_test_key']);
        Setting::updateOrCreate(['key' => 'razorpay_key_secret'], ['value' => 'rzp_test_secret']);
    }

    protected function fakeRazorpayOrder(): void
    {
        Http::fake([
            'api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_EZ6G0001',
                'amount' => 160000,
                'currency' => 'INR',
                'receipt' => 'VAN-6G-0001',
            ], 200),
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

    protected function makeOrder(User $user, string $paymentMethod, string $paymentStatus): Order
    {
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT'.str_pad((string) mt_rand(100, 999), 3, '0', STR_PAD_LEFT),
            'name' => 'VANRITI Body Lotion',
            'selling_price' => 800.00,
            'mrp' => 800.00,
            'gst_rate' => 0,
        ]);

        $order = Order::create([
            'order_number' => 'VAN-6G-'.str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT),
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