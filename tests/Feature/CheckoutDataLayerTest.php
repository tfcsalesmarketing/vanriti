<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutDataLayerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_checkout_page_pushes_begin_checkout_once_and_embeds_gdpr_safe_base(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT021',
            'name' => 'VANRITI Wet Wipes',
            'selling_price' => 500.00,
            'mrp' => 500.00,
            'gst_rate' => 0,
            'stock' => 20,
        ]);

        $this->seedCart($user, $product, 2);

        $response = $this->actingAs($user, 'web')->get(route('checkout.index'));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertSame(1, substr_count($content, 'begin_checkout'));
        $this->assertSame(1, substr_count($content, 'window.vrCheckoutAnalytics ='));
        $this->assertStringContainsString('"event":"begin_checkout"', $content);
        $this->assertStringContainsString('"currency":"INR"', $content);
        $this->assertStringContainsString('"value":1000', $content);
        $this->assertStringContainsString('"item_id":"VNRT021"', $content);
        $this->assertStringContainsString('"quantity":2', $content);

        preg_match('/window\.vrCheckoutAnalytics\s*=\s*(\{.*?\});/', $content, $matches);
        $this->assertNotEmpty($matches);
        $base = json_decode($matches[1], true);
        $this->assertArrayHasKey('ecommerce', $base);
        $this->assertSame('INR', $base['ecommerce']['currency']);
        $this->assertEqualsWithDelta(1000.0, $base['ecommerce']['value'], 0.01);
        $this->assertCount(1, $base['ecommerce']['items']);
        $this->assertSame('VNRT021', $base['ecommerce']['items'][0]['item_id']);
        $this->assertSame(2, $base['ecommerce']['items'][0]['quantity']);

        $keys = [];
        array_walk_recursive($base, function ($value, $key) use (&$keys) {
            if (is_string($key)) {
                $keys[] = $key;
            }
        });

        foreach (['email', 'phone', 'mobile', 'address', 'pincode', 'city', 'state', 'user_id', 'product_id'] as $forbidden) {
            $this->assertNotContains($forbidden, $keys);
        }
    }

    public function test_empty_cart_redirects_to_cart_without_begin_checkout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'web')->get(route('checkout.index'));

        $response->assertRedirect(route('cart.index'));
    }

    public function test_cod_success_page_fires_shipping_and_payment_info_exactly_once(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'pending');

        $response = $this->actingAs($user, 'web')->get(route('checkout.success', $order));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertSame(1, substr_count($content, 'add_shipping_info'));
        $this->assertSame(1, substr_count($content, 'add_payment_info'));
        $this->assertStringContainsString('"event":"add_shipping_info"', $content);
        $this->assertStringContainsString('"event":"add_payment_info"', $content);
        $this->assertStringContainsString('"payment_type":"cod"', $content);
        $this->assertStringContainsString('"item_id":"'.$order->items()->first()->sku.'"', $content);
        $this->assertStringContainsString('"value":1600', $content);

        // shipping tier is not persisted for COD, so it must be omitted
        $this->assertStringNotContainsString('shipping_tier', $content);
        // the confirmation page is not the checkout entry point
        $this->assertStringNotContainsString('begin_checkout', $content);
        // the purchase conversion belongs here as well, exactly once (see purchase tests)
        $this->assertSame(1, substr_count($content, '"event":"purchase"'));
        $this->assertStringNotContainsString("fbq('track', 'Purchase'", $content);
    }

    public function test_razorpay_success_page_fires_no_funnel_or_purchase_events(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'razorpay', 'processing');

        $response = $this->actingAs($user, 'web')->get(route('checkout.success', $order));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringNotContainsString('add_shipping_info', $content);
        $this->assertStringNotContainsString('add_payment_info', $content);
        $this->assertStringNotContainsString('begin_checkout', $content);
        $this->assertStringNotContainsString("fbq('track', 'Purchase'", $content);
    }

    public function test_razorpay_order_creation_returns_json_without_any_purchase_event(): void
    {
        Http::fake([
            'api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_EZ8x7y6w5v4u3t2',
                'amount' => 100000,
                'currency' => 'INR',
                'receipt' => 'VAN-DEMO-000001',
            ], 200),
        ]);

        Setting::updateOrCreate(['key' => 'online_payment_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_key_id'], ['value' => 'rzp_test_key']);
        Setting::updateOrCreate(['key' => 'razorpay_key_secret'], ['value' => 'rzp_test_secret']);

        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT022',
            'name' => 'VANRITI Face Pack',
            'selling_price' => 1000.00,
            'mrp' => 1000.00,
            'gst_rate' => 0,
            'stock' => 20,
        ]);

        $this->seedCart($user, $product, 1);

        $response = $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('razorpay'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'razorpay_order_id' => 'order_EZ8x7y6w5v4u3t2',
            'currency' => 'INR',
        ]);

        $content = strtolower($response->getContent());
        $this->assertStringNotContainsString('purchase', $content);
        $this->assertStringNotContainsString('begin_checkout', $content);

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('processing', $order->payment_status);
        $this->assertSame('razorpay', $order->payment_method);
    }

    public function test_cod_success_page_fires_single_purchase_with_correct_values(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT024',
            'name' => 'VANRITI Moisturiser',
            'selling_price' => 800.00,
            'mrp' => 1000.00,
            'gst_rate' => 18,
            'stock' => 25,
        ]);

        $this->seedCart($user, $product, 2);

        $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('cod'))->assertRedirect();

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('cod', $order->payment_method);
        $this->assertEqualsWithDelta(1600.00, (float) $order->grand_total, 0.01);

        $content = $this->actingAs($user, 'web')->get(route('checkout.success', $order))->getContent();

        // exactly one purchase push per render, payload is the real push structure
        $this->assertSame(1, substr_count($content, '"event":"purchase"'));
        $this->assertStringContainsString('window.dataLayer.push({', $content);
        $this->assertStringContainsString('"event":"purchase"', $content);
        $this->assertStringContainsString('"transaction_id":"'.$order->order_number.'"', $content);
        $this->assertStringContainsString('"value":1600', $content);
        $this->assertStringContainsString('"tax":244.07', $content);
        $this->assertStringContainsString('"shipping":0', $content);
        $this->assertStringContainsString('"currency":"INR"', $content);
        $this->assertStringContainsString('"item_id":"VNRT024"', $content);
        $this->assertStringContainsString('"quantity":2', $content);
        $this->assertStringNotContainsString('"mrp"', $content);

        // revisiting the same success URL still produces exactly one push per render
        $revisit = $this->actingAs($user, 'web')->get(route('checkout.success', $order))->getContent();
        $this->assertSame(1, substr_count($revisit, '"event":"purchase"'));

        // no PII keys inside the analytics JSON on this page
        $this->assertStringNotContainsString('"email":', $content);
        $this->assertStringNotContainsString('"phone":', $content);
        $this->assertStringNotContainsString('"user_id":', $content);
    }

    public function test_meta_pixel_purchase_coexists_with_data_layer_purchase_without_duplicate_fbq(): void
    {
        Setting::updateOrCreate(['key' => 'meta_pixel_id'], [
            'value' => 'TEST1234',
            'group' => 'seo',
            'label' => 'Meta Pixel ID',
            'type' => 'text',
        ]);

        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'cod', 'pending');

        $content = $this->actingAs($user, 'web')->get(route('checkout.success', $order))->getContent();

        // both channels fire exactly once each: the existing Pixel purchase is untouched
        $this->assertSame(1, substr_count($content, "fbq('track', 'Purchase'"));
        $this->assertSame(1, substr_count($content, '"event":"purchase"'));
        $this->assertStringContainsString('"transaction_id":"'.$order->order_number.'"', $content);
    }

    public function test_razorpay_server_verified_paid_order_fires_purchase_on_success(): void
    {
        Http::fake([
            'api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_EZPURCH1',
                'amount' => 160000,
                'currency' => 'INR',
                'receipt' => 'VAN-PURCHASE-1',
            ], 200),
        ]);

        Setting::updateOrCreate(['key' => 'online_payment_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'razorpay_key_id'], ['value' => 'rzp_test_key']);
        Setting::updateOrCreate(['key' => 'razorpay_key_secret'], ['value' => 'rzp_test_secret']);

        $user = User::factory()->create();
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT025',
            'name' => 'VANRITI Night Cream',
            'selling_price' => 800.00,
            'mrp' => 1000.00,
            'gst_rate' => 18,
            'stock' => 25,
        ]);

        $this->seedCart($user, $product, 2);

        $create = $this->actingAs($user, 'web')->post(route('checkout.store'), $this->addressData('razorpay'));
        $create->assertOk()->assertJson(['success' => true]);

        $order = Order::where('user_id', $user->id)->firstOrFail();
        // order creation alone is not a purchase (Stage 1 of the Razorpay flow)
        $this->assertStringNotContainsString('purchase', strtolower($create->getContent()));

        $payment = $order->payments()->latest()->firstOrFail();
        $this->assertSame('order_EZPURCH1', $payment->payment_reference);

        // server-side verification with a valid signature => payment becomes paid
        $signature = hash_hmac(
            'sha256',
            'order_EZPURCH1'.'|'.'pay_TESTPAY1',
            'rzp_test_secret'
        );

        $this->actingAs($user, 'web')->post(route('checkout.verify'), [
            'razorpay_order_id' => 'order_EZPURCH1',
            'razorpay_payment_id' => 'pay_TESTPAY1',
            'razorpay_signature' => $signature,
            'order_id' => $order->id,
        ])->assertRedirect(route('checkout.success', $order));

        $this->assertSame('paid', $order->fresh()->payment_status);

        $content = $this->actingAs($user, 'web')->get(route('checkout.success', $order->fresh()))->getContent();

        $this->assertSame(1, substr_count($content, '"event":"purchase"'));
        $this->assertStringContainsString('"transaction_id":"'.$order->order_number.'"', $content);
        $this->assertStringContainsString('"value":1600', $content);
        $this->assertStringContainsString('"currency":"INR"', $content);
    }

    public function test_razorpay_pending_and_processing_orders_never_fire_purchase(): void
    {
        $user = User::factory()->create();

        foreach (['pending', 'processing'] as $paymentStatus) {
            $order = $this->makeOrder($user, 'razorpay', $paymentStatus);

            $content = $this->actingAs($user, 'web')->get(route('checkout.success', $order))->getContent();

            $this->assertSame(0, substr_count($content, '"event":"purchase"'));
        }
    }

    public function test_razorpay_failed_and_cancelled_orders_never_fire_purchase(): void
    {
        $user = User::factory()->create();

        $failed = $this->makeOrder($user, 'razorpay', 'failed');
        $failedContent = $this->actingAs($user, 'web')->get(route('checkout.success', $failed))->getContent();
        $this->assertSame(0, substr_count($failedContent, '"event":"purchase"'));

        $cancelled = $this->makeOrder($user, 'razorpay', 'paid');
        $cancelled->update(['order_status' => 'cancelled']);
        $cancelledContent = $this->actingAs($user, 'web')->get(route('checkout.success', $cancelled->fresh()))->getContent();
        $this->assertSame(0, substr_count($cancelledContent, '"event":"purchase"'));
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
            'order_number' => 'VAN-0001-'.str_pad((string) mt_rand(0, 999999), 6, '0', STR_PAD_LEFT),
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