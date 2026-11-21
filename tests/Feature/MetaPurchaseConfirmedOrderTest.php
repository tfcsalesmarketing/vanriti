<?php

namespace Tests\Feature;

use App\Models\AnalyticsConversion;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Analytics\MetaCapiService;
use App\Services\CodConfirmationService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaPurchaseConfirmedOrderTest extends TestCase
{
    use RefreshDatabase;

    protected const GRAPH_ENDPOINT = 'https://graph.facebook.com/v26.0/TEST_PIXEL_ID/events';

    protected function setUp(): void
    {
        parent::setUp();

        config(['meta.enabled' => true]);
        config(['meta.api_version' => '26.0']);
        config(['meta.max_attempts' => 3]);
        config(['meta.delivery_category' => 'home_delivery']);

        Setting::updateOrCreate(['key' => 'meta_pixel_id'], [
            'value' => 'TEST_PIXEL_ID',
            'group' => 'seo',
            'label' => 'Meta Pixel ID',
            'type' => 'text',
        ]);
        Setting::updateOrCreate(['key' => 'meta_capi_access_token'], [
            'value' => \Illuminate\Support\Facades\Crypt::encryptString('secret_test_token'),
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

    public function test_pending_cod_order_does_not_fire_meta_purchase_until_confirmed(): void
    {
        $this->fakeGraphSuccess();

        $user = User::factory()->create(['email' => 'buyer@example.com', 'phone' => '9876543210']);
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT-LIP01',
            'name' => 'Rose Tint Lip Butter',
            'selling_price' => 599.00,
            'mrp' => 599.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 599.00,
            'mrp' => 599.00,
            'gst_rate' => 0,
        ]);

        // 1. Customer places order with COD
        $this->actingAs($user, 'web')->post(route('checkout.store'), [
            'shipping_name' => 'Ananya Sharma',
            'shipping_mobile' => '9876543210',
            'shipping_address_line1' => 'Flat 401, Lotus Apts',
            'shipping_city' => 'Mumbai',
            'shipping_state' => 'Maharashtra',
            'shipping_pincode' => '400001',
            'shipping_country' => 'India',
            'billing_same' => '1',
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
        ])->assertRedirect();

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('pending', $order->order_status);
        $this->assertSame('cod', $order->payment_method);

        // 2. VERIFY: No Meta CAPI delivery on checkout placement
        $this->assertSame(0, AnalyticsConversion::where('event_type', 'purchase')->where('order_number', $order->order_number)->count());
        Http::assertNothingSent();

        // 3. VERIFY: No Browser Pixel Purchase rendered on success page
        $successHtml = $this->actingAs($user, 'web')->get(route('checkout.success', $order))->getContent();
        $this->assertStringNotContainsString("fbq('track', 'Purchase'", $successHtml);

        // 4. Customer taps [Confirm Order] via WhatsApp
        $confirmed = app(CodConfirmationService::class)->handleCustomerConfirm($order->order_number, '9876543210');
        $this->assertTrue($confirmed);

        $order->refresh();
        $this->assertSame('processing', $order->order_status);

        // 5. VERIFY: Now Meta CAPI Purchase is dispatched and ledger row is recorded
        $this->assertSame(1, AnalyticsConversion::where('event_type', 'purchase')->where('order_number', $order->order_number)->count());
        $ledger = AnalyticsConversion::where('event_type', 'purchase')->where('order_number', $order->order_number)->first();
        $this->assertSame('sent', $ledger->meta_state);

        Http::assertSent(function ($request) use ($order) {
            if ($request->method() !== 'POST' || ! str_starts_with($request->url(), self::GRAPH_ENDPOINT)) {
                return false;
            }
            $data = $request->data()['data'][0];
            return $data['event_name'] === 'Purchase'
                && (float) $data['custom_data']['value'] === (float) $order->grand_total;
        });
    }

    public function test_admin_confirming_cod_order_dispatches_meta_capi_purchase(): void
    {
        $this->fakeGraphSuccess();

        $user = User::factory()->create(['email' => 'adminbuyer@example.com', 'phone' => '9811122233']);
        $product = Product::factory()->active()->create([
            'sku' => 'VNRT-HAIR01',
            'name' => 'Ayurvedic Hair Oil',
            'selling_price' => 799.00,
            'mrp' => 799.00,
            'gst_rate' => 0,
            'stock' => 10,
        ]);

        $cart = Cart::create(['owner_type' => User::class, 'owner_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 799.00,
            'mrp' => 799.00,
            'gst_rate' => 0,
        ]);

        // Place COD order
        $this->actingAs($user, 'web')->post(route('checkout.store'), [
            'shipping_name' => 'Rahul Verma',
            'shipping_mobile' => '9811122233',
            'shipping_address_line1' => 'Sector 15',
            'shipping_city' => 'Noida',
            'shipping_state' => 'Uttar Pradesh',
            'shipping_pincode' => '201301',
            'shipping_country' => 'India',
            'billing_same' => '1',
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
        ])->assertRedirect();

        $order = Order::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('pending', $order->order_status);
        $this->assertSame(0, AnalyticsConversion::where('event_type', 'purchase')->where('order_number', $order->order_number)->count());
        Http::assertNothingSent();

        // Admin updates status to confirmed
        app(OrderService::class)->updateOrderStatus($order, 'confirmed', 'Confirmed after customer call verification');

        $this->assertSame(1, AnalyticsConversion::where('event_type', 'purchase')->where('order_number', $order->order_number)->count());
        $ledger = AnalyticsConversion::where('event_type', 'purchase')->where('order_number', $order->order_number)->first();
        $this->assertSame('sent', $ledger->meta_state);

        Http::assertSent(fn ($request) => $request->method() === 'POST' && str_starts_with($request->url(), self::GRAPH_ENDPOINT));
    }
}
